<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class TrendSsoParser
{
    private Client $client;
    private CookieJar $cookieJar;
    private array $authData = [];
    private string $baseUrl = 'https://sso.trend.tech';
    private string $loginUrl;
    private ?string $loginPostUrl = null;

    public function __construct(string $loginUrl = null)
    {
        $this->cookieJar = new CookieJar();
        $this->client = new Client([
            'cookies' => $this->cookieJar,
            'allow_redirects' => [
                'max' => 10,
                'strict' => false,
                'referer' => true,
                'protocols' => ['http', 'https'],
                'track_redirects' => true
            ],
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
            ],
            'verify' => false, // Отключаем проверку SSL для тестирования
            'timeout' => 30,
        ]);

        $this->loginUrl = $loginUrl ?? 'https://sso.trend.tech/login?return_oauth_url=https%3A%2F%2Ftrendagent.ru%2Foauth&return_url=https%3A%2F%2Ftrendagent.ru%2F&app_id=66d84f584c0168b8ccd281c3';
    }

    /**
     * Авторизация через SSO с использованием браузерного автоматизатора
     * 
     * ВАЖНО: Сначала пробует использовать прямой API запрос (быстро),
     * если не получается - использует браузерную автоматизацию (медленно)
     *
     * @param string $phone Телефон в формате +7 999 637 11 82
     * @param string $password Пароль
     * @return array Массив с данными авторизации
     * @throws \Exception
     */
    public function authenticate(string $phone, string $password): array
    {
        // Сначала пробуем использовать прямой API запрос (быстро и надежно)
        try {
            Log::info('Попытка авторизации через прямой API запрос...', [
                'phone' => substr($phone, 0, 5) . '***', // Логируем только начало телефона
            ]);
            $apiAuth = new \App\Services\TrendSsoApiAuth();
            $authData = $apiAuth->authenticate($phone, $password);
            
            // Копируем данные в текущий объект
            $this->authData = $authData;
            $this->cookieJar = $apiAuth->cookieJar ?? $this->cookieJar;
            
            Log::info('Авторизация через API успешна, используем полученные данные', [
                'has_tokens' => !empty($authData['tokens']),
                'has_cookies' => !empty($authData['cookies']),
            ]);
            return $this->authData;
            
        } catch (\Exception $apiError) {
            Log::error('Авторизация через API не удалась', [
                'error' => $apiError->getMessage(),
                'error_class' => get_class($apiError),
                'file' => $apiError->getFile(),
                'line' => $apiError->getLine(),
            ]);
            
            // НЕ переходим к браузерному методу, выбрасываем исключение
            // Браузерный метод слишком медленный и ненадежный
            throw new \Exception('Ошибка авторизации через API: ' . $apiError->getMessage(), 0, $apiError);
        }
    }

    /**
     * Форматирование телефона
     */
    private function formatPhone(string $phone): string
    {
        // Убираем все пробелы и символы, оставляем только цифры и +
        $phone = preg_replace('/[^\d+]/', '', $phone);
        
        // Если начинается с +7, оставляем как есть
        if (strpos($phone, '+7') === 0) {
            return $phone;
        }
        
        // Если начинается с 7, добавляем +
        if (strpos($phone, '7') === 0) {
            return '+' . $phone;
        }
        
        // Если начинается с 8, заменяем на +7
        if (strpos($phone, '8') === 0) {
            return '+7' . substr($phone, 1);
        }
        
        return $phone;
    }

    /**
     * Получение всех cookies в виде массива
     */
    public function getCookies(): array
    {
        $cookies = [];
        foreach ($this->cookieJar as $cookie) {
            $cookies[$cookie->getName()] = [
                'value' => $cookie->getValue(),
                'domain' => $cookie->getDomain(),
                'path' => $cookie->getPath(),
                'expires' => $cookie->getExpires(),
            ];
        }
        return $cookies;
    }

    /**
     * Получение заголовков для авторизованных запросов
     */
    public function getAuthHeaders(): array
    {
        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept' => 'application/json, text/html, */*',
            'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
        ];

        if (isset($this->authData['access_token'])) {
            $headers['Authorization'] = 'Bearer ' . $this->authData['access_token'];
        }

        return $headers;
    }

    /**
     * Получение Session ID из cookies
     */
    private function getSessionId(): ?string
    {
        $cookies = $this->getCookies();
        
        // Ищем session cookie
        foreach ($cookies as $name => $cookie) {
            if (stripos($name, 'session') !== false || stripos($name, 'laravel_session') !== false) {
                return $cookie['value'];
            }
        }

        return null;
    }

    /**
     * Получение всех данных авторизации
     */
    public function getAuthData(): array
    {
        return $this->authData;
    }

    /**
     * Проверка, авторизован ли пользователь
     */
    public function isAuthenticated(): bool
    {
        return !empty($this->authData) && ($this->authData['authenticated'] ?? false);
    }
}


