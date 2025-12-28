<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

/**
 * Оптимизированная авторизация через SSO API
 * Использует прямой API запрос вместо браузерной автоматизации
 */
class TrendSsoApiAuth
{
    private Client $client;
    private CookieJar $cookieJar;
    private array $authData = [];
    private string $apiBaseUrl = 'https://sso-api.trend.tech';
    private string $ssoBaseUrl = 'https://sso.trend.tech';
    private string $appId = '66d84f584c0168b8ccd281c3';

    public function __construct()
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
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
                'Accept' => 'application/json, text/plain, */*',
                'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
                'Origin' => 'https://sso.trend.tech',
                'Referer' => 'https://sso.trend.tech/',
            ],
            'verify' => false,
            'timeout' => 30,
        ]);
    }

    /**
     * Авторизация через SSO API
     *
     * @param string $phone Телефон в формате +7 999 637 11 82
     * @param string $password Пароль
     * @param string|null $appId ID приложения (по умолчанию из конструктора)
     * @return array Массив с данными авторизации
     * @throws \Exception
     */
    public function authenticate(string $phone, string $password, ?string $appId = null): array
    {
        try {
            Log::info('Начало авторизации через SSO API', [
                'api_url' => $this->apiBaseUrl,
            ]);

            // Форматируем телефон
            $phoneFormatted = $this->formatPhone($phone);

            // Шаг 1: Получаем начальную страницу /login для получения редиректа с app_id
            Log::info('Получение начальной страницы /login для извлечения app_id...');
            $appId = null;
            try {
                // Включаем автоматические редиректы и отслеживаем историю
                $response = $this->client->get($this->ssoBaseUrl . '/login', [
                    'cookies' => $this->cookieJar,
                    'allow_redirects' => [
                        'max' => 5,
                        'strict' => false,
                        'referer' => true,
                        'protocols' => ['http', 'https'],
                        'track_redirects' => true, // Включаем отслеживание редиректов
                    ],
                    'headers' => [
                        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                        'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                        'Accept-Encoding' => 'gzip, deflate, br',
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
                        'Sec-Ch-Ua' => '"Chromium";v="142", "Google Chrome";v="142", "Not_A Brand";v="99"',
                        'Sec-Ch-Ua-Mobile' => '?0',
                        'Sec-Ch-Ua-Platform' => '"Windows"',
                        'Sec-Fetch-Dest' => 'document',
                        'Sec-Fetch-Mode' => 'navigate',
                        'Sec-Fetch-Site' => 'none',
                        'Sec-Fetch-User' => '?1',
                        'Upgrade-Insecure-Requests' => '1',
                    ],
                ]);
                
                $statusCode = $response->getStatusCode();
                Log::info('Ответ от /login', ['status_code' => $statusCode]);
                
                // Получаем историю редиректов
                $redirectHistory = $response->getHeader('X-Guzzle-Redirect-History');
                $redirectCount = $response->getHeader('X-Guzzle-Redirect-Status-History');
                
                Log::info('История редиректов', [
                    'redirect_count' => count($redirectHistory ?? []),
                    'redirect_urls' => $redirectHistory ?? [],
                    'redirect_statuses' => $redirectCount ?? [],
                ]);
                
                // Берем последний URL из истории редиректов (финальный URL)
                $finalUrl = null;
                if (!empty($redirectHistory)) {
                    $finalUrl = end($redirectHistory);
                } else {
                    // Если редиректов не было, используем исходный URL
                    $finalUrl = $this->ssoBaseUrl . '/login';
                }
                
                Log::info('Финальный URL после редиректов', ['url' => $finalUrl]);
                
                // Извлекаем app_id из финального URL
                $parsedUrl = parse_url($finalUrl);
                if (isset($parsedUrl['query'])) {
                    parse_str($parsedUrl['query'], $queryParams);
                    if (isset($queryParams['app_id'])) {
                        $appId = $queryParams['app_id'];
                        Log::info('app_id извлечен из финального URL', ['app_id' => $appId]);
                    }
                }
                
                // Если редиректов не было, но страница загрузилась (200), 
                // значит редирект происходит через JavaScript
                // В этом случае app_id должен быть в URL после выполнения JavaScript
                // Но так как мы не выполняем JS, пробуем извлечь из HTML
                
                // Если app_id не найден, пробуем извлечь из тела ответа
                if (empty($appId)) {
                    $body = $response->getBody()->getContents();
                    $response->getBody()->rewind();
                    
                    Log::info('Поиск app_id в теле ответа', ['body_length' => strlen($body)]);
                    
                    // Пробуем найти app_id в различных форматах:
                    // 1. В URL: ?app_id=... или /recover?app_id=...
                    // 2. В скриптах: app_id: "..."
                    // 3. В атрибутах: data-app-id="..."
                    // 4. В переменных JavaScript: var appId = "..."
                    // 5. В ссылках: href="/recover?app_id=..."
                    $patterns = [
                        '/[?&]app_id[=:]\s*([a-f0-9]{24})/i',  // В URL параметрах (приоритет)
                        '/href=["\'][^"\']*app_id[=:]([a-f0-9]{24})/i',  // В ссылках (приоритет)
                        '/app_id[=:]\s*["\']?([a-f0-9]{24})["\']?/i',
                        '/appId[=:]\s*["\']?([a-f0-9]{24})["\']?/i',
                        '/app-id[=:]\s*["\']?([a-f0-9]{24})["\']?/i',
                        '/["\']app_id["\']\s*:\s*["\']([a-f0-9]{24})["\']/i',
                    ];
                    
                    foreach ($patterns as $pattern) {
                        if (preg_match($pattern, $body, $matches)) {
                            $appId = $matches[1];
                            Log::info('app_id найден в теле ответа', ['app_id' => $appId, 'pattern' => $pattern]);
                            break;
                        }
                    }
                    
                    // Если не нашли, ищем любые упоминания app_id в HTML
                    if (empty($appId)) {
                        // Ищем все упоминания app_id (может быть разной длины)
                        if (preg_match_all('/app_id[=:]([a-f0-9]{20,30})/i', $body, $allMatches)) {
                            Log::info('Найдены возможные app_id в HTML', [
                                'matches' => $allMatches[1],
                                'count' => count($allMatches[1]),
                            ]);
                            // Берем первый найденный (обычно это правильный)
                            if (!empty($allMatches[1][0])) {
                                $appId = $allMatches[1][0];
                                Log::info('Используется первый найденный app_id', ['app_id' => $appId]);
                            }
                        }
                        
                        if (empty($appId)) {
                            // Сохраняем больше HTML для отладки
                            $bodySample = substr($body, 0, 5000);
                            Log::warning('app_id не найден в теле ответа', [
                                'body_sample' => $bodySample,
                                'body_length' => strlen($body),
                            ]);
                        }
                    }
                }
                
                // Если app_id не найден, это SPA и app_id генерируется через JavaScript
                // В этом случае пробуем использовать app_id из исходного URL авторизации
                // или генерируем новый запрос, который вернет app_id в редиректе
                if (empty($appId)) {
                    Log::info('app_id не найден в HTML (SPA), пробуем получить через редирект...');
                    
                    // Пробуем сделать запрос, который должен вернуть редирект с app_id
                    // Или используем app_id из исходного URL, если он был передан
                    try {
                        // Пробуем запросить страницу с параметрами, которые могут вызвать редирект
                        $testResponse = $this->client->get($this->ssoBaseUrl . '/login', [
                            'cookies' => $this->cookieJar,
                            'allow_redirects' => [
                                'max' => 3,
                                'track_redirects' => true,
                            ],
                            'headers' => [
                                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                                'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
                            ],
                        ]);
                        
                        // Проверяем историю редиректов еще раз
                        $testRedirectHistory = $testResponse->getHeader('X-Guzzle-Redirect-History');
                        if (!empty($testRedirectHistory)) {
                            foreach ($testRedirectHistory as $redirectUrl) {
                                $parsed = parse_url($redirectUrl);
                                if (isset($parsed['query'])) {
                                    parse_str($parsed['query'], $params);
                                    if (isset($params['app_id'])) {
                                        $appId = $params['app_id'];
                                        Log::info('app_id найден в редиректе при повторном запросе', ['app_id' => $appId]);
                                        break;
                                    }
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        Log::warning('Ошибка при попытке получить app_id через редирект', ['error' => $e->getMessage()]);
                    }
                    
                    // Если все еще не найден, используем значение по умолчанию
                    // Но это может не сработать, так как app_id может быть динамическим
                    if (empty($appId)) {
                        $appId = $this->appId;
                        Log::warning('app_id не найден, используется значение по умолчанию. Возможно, нужна браузерная автоматизация для получения динамического app_id', ['app_id' => $appId]);
                    }
                }
                
                // Если app_id все еще не найден, используем значение по умолчанию
                if (empty($appId)) {
                    $appId = $this->appId;
                    Log::warning('app_id не найден в редиректе, используется значение по умолчанию', ['app_id' => $appId]);
                } else {
                    Log::info('Используется app_id из редиректа', ['app_id' => $appId]);
                }
                
                // ВАЖНО: Загружаем страницу с app_id в URL для получения cookies
                // Это может быть необходимо для установки session cookies
                // Также пробуем несколько вариантов app_id, так как он может быть динамическим
                Log::info('Загрузка страницы с app_id для получения cookies...');
                
                // Пробуем разные варианты app_id (из реального запроса и дефолтный)
                // ВАЖНО: В реальном успешном запросе app_id = 66d84ffc4c0168b8ccd281c7
                $appIdVariants = [
                    '66d84ffc4c0168b8ccd281c7', // Из реального успешного запроса (ПРИОРИТЕТ)
                    $appId, // Текущий app_id
                    $this->appId, // Дефолтный
                ];
                $appIdVariants = array_unique($appIdVariants);
                
                $bestAppId = $appId;
                $maxCookies = 0;
                
                foreach ($appIdVariants as $variantAppId) {
                    try {
                        $responseWithAppId = $this->client->get($this->ssoBaseUrl . '/login?app_id=' . urlencode($variantAppId), [
                            'cookies' => $this->cookieJar,
                            'allow_redirects' => true,
                            'headers' => [
                                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                                'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                                'Accept-Encoding' => 'gzip, deflate, br',
                                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
                                'Sec-Ch-Ua' => '"Chromium";v="142", "Google Chrome";v="142", "Not_A Brand";v="99"',
                                'Sec-Ch-Ua-Mobile' => '?0',
                                'Sec-Ch-Ua-Platform' => '"Windows"',
                                'Sec-Fetch-Dest' => 'document',
                                'Sec-Fetch-Mode' => 'navigate',
                                'Sec-Fetch-Site' => 'none',
                                'Sec-Fetch-User' => '?1',
                                'Upgrade-Insecure-Requests' => '1',
                            ],
                        ]);
                        
                        $cookiesAfterAppIdLoad = $this->getCookies();
                        Log::info('Страница с app_id загружена', [
                            'app_id_variant' => $variantAppId,
                            'cookies_count' => count($cookiesAfterAppIdLoad),
                            'cookie_names' => array_keys($cookiesAfterAppIdLoad),
                            'status_code' => $responseWithAppId->getStatusCode(),
                        ]);
                        
                        // Выбираем app_id, который дал больше всего cookies
                        $cookiesCount = count($cookiesAfterAppIdLoad);
                        if ($cookiesCount > $maxCookies) {
                            $maxCookies = $cookiesCount;
                            $bestAppId = $variantAppId;
                        }
                    } catch (\Exception $e) {
                        Log::warning('Ошибка при загрузке страницы с app_id', [
                            'app_id_variant' => $variantAppId,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
                
                // Используем лучший app_id (который дал больше всего cookies)
                if (!empty($bestAppId) && $bestAppId !== $appId) {
                    $appId = $bestAppId;
                    Log::info('Выбран app_id, который дал больше всего cookies', [
                        'new_app_id' => $appId,
                        'cookies_count' => $maxCookies,
                    ]);
                }
                
                Log::info('Начальная страница получена', [
                    'cookies_count' => count($this->getCookies()),
                    'app_id' => $appId,
                ]);
            } catch (\Exception $e) {
                Log::warning('Ошибка при получении начальной страницы', [
                    'error' => $e->getMessage(),
                ]);
                // Используем app_id по умолчанию или переданный параметр
                $appId = $appId ?? $this->appId;
            }

            // Шаг 2: Выполняем авторизацию через API
            // ВАЖНО: Если cookies не получены, пробуем использовать app_id из реального успешного запроса
            // Это может помочь обойти защиту от ботов
            if (count($this->getCookies()) === 0) {
                Log::warning('Cookies не получены, пробуем использовать app_id из реального успешного запроса', [
                    'app_id_from_request' => '66d84ffc4c0168b8ccd281c7',
                    'current_app_id' => $appId,
                ]);
                // Используем app_id из реального успешного запроса
                $appId = '66d84ffc4c0168b8ccd281c7';
            }
            
            Log::info('Отправка запроса авторизации через API...', [
                'app_id' => $appId,
                'phone' => substr($phoneFormatted, 0, 5) . '***',
            ]);
            
            $loginUrl = $this->apiBaseUrl . '/v1/login?app_id=' . urlencode($appId) . '&lang=ru';
            
            // Логируем данные, которые будут отправлены (без пароля)
            Log::info('Данные для отправки', [
                'url' => $loginUrl,
                'phone' => substr($phoneFormatted, 0, 5) . '***',
                'client' => 'web',
                'cookies_count' => count($this->getCookies()),
            ]);
            
            // Отключаем автоматические редиректы для POST, чтобы получить cookies и ответ
            $response = $this->client->post($loginUrl, [
                'cookies' => $this->cookieJar,
                'allow_redirects' => false, // НЕ следуем редиректам автоматически, чтобы получить cookies
                'form_params' => [
                    'phone' => $phoneFormatted,
                    'password' => $password,
                    'client' => 'web',
                ],
                'headers' => [
                    'Accept' => 'application/json, text/plain, */*',
                    'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
                    'Origin' => 'https://sso.trend.tech',
                    'Referer' => $this->ssoBaseUrl . '/login?app_id=' . urlencode($appId), // Используем URL с app_id
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
                    'Sec-Ch-Ua' => '"Chromium";v="142", "Google Chrome";v="142", "Not_A Brand";v="99"',
                    'Sec-Ch-Ua-Mobile' => '?0',
                    'Sec-Ch-Ua-Platform' => '"Windows"',
                    'Sec-Fetch-Dest' => 'empty',
                    'Sec-Fetch-Mode' => 'cors',
                    'Sec-Fetch-Site' => 'same-site',
                    'Priority' => 'u=1, i',
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();
            
            // Сохраняем полный ответ для анализа
            $responseHeaders = $response->getHeaders();
            
            // Логируем полный ответ для анализа
            Log::info('Ответ от API авторизации', [
                'status_code' => $statusCode,
                'content_type' => $responseHeaders['Content-Type'][0] ?? null,
                'location' => $responseHeaders['Location'][0] ?? null,
                'set_cookie_count' => count($responseHeaders['Set-Cookie'] ?? []),
                'body_preview' => substr($body, 0, 1000),
                'body_length' => strlen($body),
            ]);
            
            // Проверяем cookies после запроса
            $cookiesAfterRequest = $this->getCookies();
            Log::info('Cookies после POST запроса', [
                'cookies_count' => count($cookiesAfterRequest),
                'cookie_names' => array_keys($cookiesAfterRequest),
            ]);
            
            // Ищем auth_token в cookies
            $authTokenFromCookie = null;
            foreach ($cookiesAfterRequest as $name => $cookie) {
                if ($name === 'auth_token' || stripos($name, 'auth') !== false) {
                    $authTokenFromCookie = $cookie['value'];
                    Log::info('Найден auth_token в cookie', ['cookie_name' => $name]);
                    break;
                }
            }

            // Обрабатываем редирект (302/301) как успешную авторизацию
            if ($statusCode >= 300 && $statusCode < 400) {
                $location = $responseHeaders['Location'][0] ?? null;
                Log::info('Получен редирект после авторизации', [
                    'status_code' => $statusCode,
                    'location' => $location,
                    'has_auth_token_cookie' => !empty($authTokenFromCookie),
                ]);
                
                // Если есть auth_token в cookies, считаем авторизацию успешной
                if (!empty($authTokenFromCookie)) {
                    Log::info('Авторизация успешна (токен в cookies)');
                    // Продолжаем обработку с токеном из cookies
                } else {
                    // Пробуем извлечь токен из Location
                    if (!empty($location)) {
                        $parsedLocation = parse_url($location);
                        if (isset($parsedLocation['query'])) {
                            parse_str($parsedLocation['query'], $locationParams);
                            if (isset($locationParams['auth_token'])) {
                                $authTokenFromCookie = $locationParams['auth_token'];
                                Log::info('Токен найден в Location редиректа');
                            }
                        }
                    }
                }
            } elseif ($statusCode === 201) {
                // 201 Created - успешная авторизация (как показал пользователь)
                Log::info('Получен 201 Created - авторизация успешна');
            } elseif ($statusCode === 403) {
                // 403 Forbidden - возможно, нужны дополнительные заголовки или cookies
                Log::error('Получен 403 Forbidden', [
                    'response_body' => $body,
                    'cookies_before' => count($this->getCookies()),
                    'set_cookie_headers' => $responseHeaders['Set-Cookie'] ?? [],
                ]);
                
                // Пробуем извлечь токен из cookies, даже если получили 403
                // (возможно, сервер все равно установил cookies)
                if (!empty($authTokenFromCookie)) {
                    Log::info('Несмотря на 403, токен найден в cookies - продолжаем');
                } else {
                    throw new \Exception("API вернул 403 Forbidden. Возможно, требуется дополнительная авторизация или защита от ботов. Ответ: " . substr($body, 0, 200));
                }
            } elseif ($statusCode !== 200) {
                // Если это не редирект, не 200 и не 201, это ошибка
                throw new \Exception("API вернул статус {$statusCode}: " . substr($body, 0, 200));
            }

            // Парсим JSON ответ
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                // Если не JSON, возможно это редирект или другой формат
                Log::warning('Ответ не является JSON', [
                    'content_type' => $responseHeaders['Content-Type'][0] ?? null,
                    'body_preview' => substr($body, 0, 200),
                    'json_error' => json_last_error_msg(),
                ]);
                
                // Проверяем, не является ли это редиректом
                $location = $responseHeaders['Location'][0] ?? null;
                if ($location) {
                    Log::info('Обнаружен редирект в ответе', ['location' => $location]);
                    // Продолжаем обработку редиректа
                    $data = []; // Пустой массив для продолжения
                } else {
                    throw new \Exception('Ошибка парсинга JSON ответа: ' . json_last_error_msg() . '. Тело ответа: ' . substr($body, 0, 200));
                }
            } else {
                // Логируем структуру JSON ответа
                Log::info('Структура JSON ответа', [
                    'keys' => array_keys($data),
                    'has_token' => isset($data['token']),
                    'has_access_token' => isset($data['access_token']),
                    'has_auth_token' => isset($data['auth_token']),
                    'has_user' => isset($data['user']),
                    'has_error' => isset($data['error']) || isset($data['errors']),
                    'has_data' => isset($data['data']),
                ]);
            }

            // Проверяем наличие ошибок
            if (isset($data['error']) || isset($data['errors'])) {
                $errorMessage = $data['error'] ?? (is_array($data['errors']) ? implode(', ', $data['errors']) : 'Неизвестная ошибка');
                Log::error('Ошибка авторизации в ответе API', [
                    'error' => $errorMessage,
                    'full_response' => $data,
                ]);
                throw new \Exception('Ошибка авторизации: ' . $errorMessage);
            }

            // Извлекаем токен из ответа (пробуем все возможные варианты)
            $tokens = [];
            
            // ПРИОРИТЕТ 1: Токен из JSON ответа (как показал пользователь - токены в JSON)
            if (!empty($data)) {
                // Вариант 1: Поле auth_token (как в реальном ответе)
                if (isset($data['auth_token'])) {
                    $tokens['access_token'] = $data['auth_token'];
                    $tokens['auth_token'] = $data['auth_token'];
                    Log::info('Токен найден в поле "auth_token"');
                }
                
                // Вариант 2: Поле refresh_token (тоже сохраняем)
                if (isset($data['refresh_token'])) {
                    $tokens['refresh_token'] = $data['refresh_token'];
                    Log::info('Refresh token найден');
                }
                
                // Вариант 3: Прямое поле token
                if (isset($data['token']) && empty($tokens['auth_token'])) {
                    $tokens['access_token'] = $data['token'];
                    $tokens['auth_token'] = $data['token'];
                    Log::info('Токен найден в поле "token"');
                }
                
                // Вариант 4: Поле access_token
                if (isset($data['access_token']) && empty($tokens['auth_token'])) {
                    $tokens['access_token'] = $data['access_token'];
                    $tokens['auth_token'] = $data['access_token'];
                    Log::info('Токен найден в поле "access_token"');
                }
                
                // Вариант 5: Вложенное поле data.auth_token
                if (isset($data['data']['auth_token']) && empty($tokens['auth_token'])) {
                    $tokens['access_token'] = $data['data']['auth_token'];
                    $tokens['auth_token'] = $data['data']['auth_token'];
                    Log::info('Токен найден в поле "data.auth_token"');
                }
                
                // Вариант 6: Вложенное поле data.token
                if (isset($data['data']['token']) && empty($tokens['auth_token'])) {
                    $tokens['access_token'] = $data['data']['token'];
                    $tokens['auth_token'] = $data['data']['token'];
                    Log::info('Токен найден в поле "data.token"');
                }
            }
            
            // ПРИОРИТЕТ 2: Токен из cookies (fallback)
            if (empty($tokens['auth_token']) && !empty($authTokenFromCookie)) {
                $tokens['access_token'] = $authTokenFromCookie;
                $tokens['auth_token'] = $authTokenFromCookie;
                Log::info('Токен найден в cookies (fallback)', [
                    'token_preview' => substr($authTokenFromCookie, 0, 50) . '...',
                ]);
            }

            // Проверяем редирект в заголовках
            $location = $response->getHeaderLine('Location');
            if (!empty($location)) {
                Log::info('Обнаружен редирект в заголовках', ['location' => $location]);
                
                // Пробуем извлечь токен из URL редиректа
                $parsedUrl = parse_url($location);
                if (isset($parsedUrl['query'])) {
                    parse_str($parsedUrl['query'], $queryParams);
                    Log::info('Параметры редиректа', ['params' => array_keys($queryParams)]);
                    
                    if (isset($queryParams['auth_token']) && empty($tokens['auth_token'])) {
                        $tokens['access_token'] = $queryParams['auth_token'];
                        $tokens['auth_token'] = $queryParams['auth_token'];
                        Log::info('Токен найден в параметре редиректа "auth_token"');
                    }
                    if (isset($queryParams['access_token']) && empty($tokens['access_token'])) {
                        $tokens['access_token'] = $queryParams['access_token'];
                        $tokens['auth_token'] = $queryParams['access_token'];
                        Log::info('Токен найден в параметре редиректа "access_token"');
                    }
                }
                
                // Проверяем fragment (#)
                if (isset($parsedUrl['fragment'])) {
                    parse_str($parsedUrl['fragment'], $fragmentParams);
                    if (isset($fragmentParams['auth_token']) && empty($tokens['auth_token'])) {
                        $tokens['access_token'] = $fragmentParams['auth_token'];
                        $tokens['auth_token'] = $fragmentParams['auth_token'];
                        Log::info('Токен найден в fragment редиректа');
                    }
                }
            }
            
            // Проверяем токен в заголовках ответа
            $authTokenHeader = $response->getHeaderLine('X-Auth-Token');
            if (!empty($authTokenHeader) && empty($tokens['auth_token'])) {
                $tokens['access_token'] = $authTokenHeader;
                $tokens['auth_token'] = $authTokenHeader;
                Log::info('Токен найден в заголовке X-Auth-Token');
            }

            // Получаем cookies
            $cookies = $this->getCookies();

            // Извлекаем данные пользователя из ответа (если есть)
            $userData = null;
            if (isset($data['user']) && is_array($data['user'])) {
                $userData = $data['user'];
                Log::info('Данные пользователя получены', [
                    'user_id' => $userData['id'] ?? null,
                    'user_name' => $userData['name'] ?? null,
                ]);
            }

            // Сохраняем данные авторизации
            $this->authData = [
                'cookies' => $cookies,
                'headers' => $this->getAuthHeaders(),
                'tokens' => $tokens,
                'session_id' => $this->getSessionId(),
                'current_url' => $location ?? null,
                'authenticated' => !empty($tokens['auth_token']), // Авторизован, если есть токен
                'timestamp' => now()->toIso8601String(),
                'api_response' => $data, // Сохраняем полный ответ API для отладки
                'user' => $userData, // Данные пользователя
                'status_code' => $statusCode, // Статус код ответа
            ];

            // Если токен не найден, пробуем извлечь из cookies
            if (empty($tokens['auth_token'])) {
                foreach ($cookies as $name => $cookie) {
                    if (stripos($name, 'token') !== false || stripos($name, 'auth') !== false) {
                        $this->authData['tokens']['access_token'] = $cookie['value'];
                        $this->authData['tokens']['auth_token'] = $cookie['value'];
                        Log::info('Токен найден в cookie', ['cookie_name' => $name]);
                        break;
                    }
                }
            }

            Log::info('Авторизация через API успешна', [
                'has_cookies' => !empty($cookies),
                'has_tokens' => !empty($this->authData['tokens']),
                'tokens_keys' => array_keys($this->authData['tokens']),
            ]);

            return $this->authData;

        } catch (GuzzleException $e) {
            $response = method_exists($e, 'hasResponse') && $e->hasResponse() ? $e->getResponse() : null;
            $responseBody = $response ? $response->getBody()->getContents() : 'N/A';
            $statusCode = $response ? $response->getStatusCode() : 'N/A';
            
            Log::error('Ошибка Guzzle при авторизации через API', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'status_code' => $statusCode,
                'response_body' => substr($responseBody, 0, 500),
            ]);
            throw new \Exception('Ошибка при авторизации через API: ' . $e->getMessage() . (($statusCode !== 'N/A') ? " (HTTP {$statusCode})" : ''));
        } catch (\Exception $e) {
            Log::error('Общая ошибка при авторизации через API', [
                'message' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 1000),
            ]);
            throw $e;
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
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
            'Accept' => 'application/json, text/plain, */*',
            'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
        ];

        $tokens = $this->authData['tokens'] ?? [];
        if (isset($tokens['access_token'])) {
            $headers['Authorization'] = 'Bearer ' . $tokens['access_token'];
        }

        return $headers;
    }

    /**
     * Получение Session ID из cookies
     */
    private function getSessionId(): ?string
    {
        $cookies = $this->getCookies();
        
        foreach ($cookies as $name => $cookie) {
            if (stripos($name, 'session') !== false) {
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

    /**
     * Получение токена для API запросов
     */
    public function getAuthToken(): ?string
    {
        $tokens = $this->authData['tokens'] ?? [];
        return $tokens['auth_token'] ?? $tokens['access_token'] ?? null;
    }

    /**
     * Получение контента защищенной страницы
     *
     * @param string $url URL страницы для получения
     * @param array $options Дополнительные опции для запроса
     * @return array Массив с контентом и метаданными
     * @throws \Exception
     */
    public function getPageContent(string $url, array $options = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            Log::info('Получение контента страницы', ['url' => $url]);

            // Получаем токен авторизации
            $authToken = $this->getAuthToken();
            
            // Формируем заголовки с cookies
            $headers = array_merge($this->getAuthHeaders(), [
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Referer' => 'https://spb.trendagent.ru/',
                'Origin' => 'https://spb.trendagent.ru',
                'Sec-Ch-Ua' => '"Chromium";v="142", "Google Chrome";v="142", "Not_A Brand";v="99"',
                'Sec-Ch-Ua-Mobile' => '?0',
                'Sec-Ch-Ua-Platform' => '"Windows"',
                'Sec-Fetch-Dest' => 'document',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Site' => 'same-origin',
                'Sec-Fetch-User' => '?1',
                'Upgrade-Insecure-Requests' => '1',
            ]);

            // Если есть токен, добавляем его в заголовок Authorization или в URL
            if (!empty($authToken)) {
                // Пробуем добавить токен в URL, если это необходимо
                $parsedUrl = parse_url($url);
                if (!isset($parsedUrl['query']) || strpos($parsedUrl['query'], 'auth_token') === false) {
                    $separator = isset($parsedUrl['query']) ? '&' : '?';
                    $url = $url . $separator . 'auth_token=' . urlencode($authToken);
                }
            }

            $requestOptions = array_merge([
                'cookies' => $this->cookieJar,
                'headers' => $headers,
                'allow_redirects' => [
                    'max' => 5,
                    'strict' => false,
                    'referer' => true,
                ],
                'timeout' => 30,
                'verify' => false,
            ], $options);

            $response = $this->client->get($url, $requestOptions);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();
            $responseHeaders = $response->getHeaders();

            Log::info('Контент страницы получен', [
                'url' => $url,
                'status_code' => $statusCode,
                'content_length' => strlen($body),
            ]);

            if ($statusCode >= 400) {
                throw new \Exception("Ошибка при получении страницы: HTTP {$statusCode}");
            }

            return [
                'url' => $url,
                'status_code' => $statusCode,
                'content' => $body,
                'headers' => $responseHeaders,
                'content_length' => strlen($body),
                'content_type' => $responseHeaders['Content-Type'][0] ?? 'unknown',
            ];

        } catch (GuzzleException $e) {
            Log::error('Ошибка при получении контента страницы', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);
            throw new \Exception('Ошибка при получении контента: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Получение данных объектов через API blocks/search
     * 
     * @param array $params Параметры запроса (show_type, sort, sort_order, count, city, lang и т.д.)
     * @return array Массив с данными объектов
     * @throws \Exception
     */
    public function getBlocksSearch(array $params = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            // Получаем токен авторизации
            $authToken = $this->getAuthToken();

            if (empty($authToken)) {
                throw new \Exception('Токен авторизации не найден. Выполните авторизацию сначала.');
            }

            // Формируем URL API
            $apiUrl = 'https://api.trendagent.ru/v4_29/blocks/search/';
            
            // Параметры по умолчанию
            $defaultParams = [
                'show_type' => 'list',
                'sort' => 'price',
                'sort_order' => 'asc',
                'count' => 20,
                'offset' => 0, // Смещение для пагинации
                'city' => '58c665588b6aa52311afa01b', // Санкт-Петербург
                'lang' => 'ru',
            ];

            // Объединяем параметры
            $queryParams = array_merge($defaultParams, $params);
            $queryParams['auth_token'] = $authToken;

            // Обрабатываем параметр room для правильного формирования URL
            // room может быть массивом [30, 40] и должен стать room=30&room=40
            // room=30 - Коттеджи (дома с участками)
            // room=40 - Таунхаусы (дома с участками)
            $roomParams = [];
            if (isset($queryParams['room']) && is_array($queryParams['room'])) {
                $roomParams = $queryParams['room'];
                unset($queryParams['room']); // Удаляем из основных параметров
            } elseif (isset($queryParams['room'])) {
                // Если одно значение, делаем массив
                $roomParams = [$queryParams['room']];
                unset($queryParams['room']);
            }
            
            // Формируем базовый query string
            $queryString = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);
            
            // Добавляем параметры room отдельно (room=30&room=40)
            if (!empty($roomParams)) {
                $roomQuery = [];
                foreach ($roomParams as $room) {
                    $roomQuery[] = 'room=' . urlencode($room);
                }
                if (!empty($queryString)) {
                    $queryString .= '&' . implode('&', $roomQuery);
                } else {
                    $queryString = implode('&', $roomQuery);
                }
            }
            
            // Формируем полный URL
            $fullUrl = $apiUrl . '?' . $queryString;

            Log::info('Запрос к API blocks/search', [
                'url' => $apiUrl,
                'has_token' => !empty($authToken),
                'params' => array_merge($queryParams, ['auth_token' => '***']), // Не логируем токен
            ]);

            // Выполняем запрос к API с правильными заголовками
            $response = $this->client->get($fullUrl, [
                'headers' => [
                    'Accept' => 'application/json, text/plain, */*',
                    'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Accept-Encoding' => 'gzip, deflate, br, zstd',
                    'Origin' => 'https://spb.trendagent.ru',
                    'Referer' => 'https://spb.trendagent.ru/',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
                    'Sec-Ch-Ua' => '"Chromium";v="142", "Google Chrome";v="142", "Not_A Brand";v="99"',
                    'Sec-Ch-Ua-Mobile' => '?0',
                    'Sec-Ch-Ua-Platform' => '"Windows"',
                    'Sec-Fetch-Dest' => 'empty',
                    'Sec-Fetch-Mode' => 'cors',
                    'Sec-Fetch-Site' => 'same-site',
                    'Priority' => 'u=1, i',
                ],
                'timeout' => 30,
                'verify' => false,
                'allow_redirects' => true,
            ]);

            $statusCode = $response->getStatusCode();
            
            // 304 Not Modified - данные не изменились, используем кэш
            if ($statusCode === 304) {
                Log::info('API вернул 304 Not Modified - данные не изменились');
                return [
                    'success' => true,
                    'data' => [],
                    'total' => 0,
                    'source' => 'api',
                    'cached' => true,
                ];
            }
            
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                Log::error('API вернул ошибку', [
                    'status_code' => $statusCode,
                    'body_preview' => substr($body, 0, 500),
                ]);
                throw new \Exception("API вернул статус {$statusCode}: " . substr($body, 0, 200));
            }

            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка парсинга JSON ответа: ' . json_last_error_msg());
            }

            if (isset($data['errors']) && !empty($data['errors'])) {
                throw new \Exception('API вернул ошибки: ' . json_encode($data['errors']));
            }

            Log::info('Данные получены через API blocks/search', [
                'results_count' => count($data['data']['results'] ?? []),
                'blocks_count' => $data['data']['blocksCount'] ?? 0,
                'prelaunches_count' => $data['data']['prelaunchesCount'] ?? 0,
            ]);

            // Обрабатываем результаты: добавляем полные URL для изображений
            $results = $data['data']['results'] ?? [];
            $processedResults = array_map(function($item) {
                // Обрабатываем изображение, если оно есть
                if (isset($item['image']) && is_array($item['image'])) {
                    $image = $item['image'];
                    if (isset($image['path']) && isset($image['file_name'])) {
                        // Формируем полный URL изображения
                        // Формат: https://selcdn.trendagent.ru/images/{path}m_{file_name}
                        $path = rtrim($image['path'], '/');
                        $fileName = $image['file_name'];
                        
                        // Убираем слэш в начале path, если есть
                        $path = ltrim($path, '/');
                        
                        // Формируем URL с префиксом "m_" для миниатюры
                        $item['image']['url'] = "https://selcdn.trendagent.ru/images/{$path}/m_{$fileName}";
                        // Полный размер изображения (без префикса "m_")
                        $item['image']['url_full'] = "https://selcdn.trendagent.ru/images/{$path}/{$fileName}";
                        
                        Log::debug('Сформирован URL изображения', [
                            'path' => $path,
                            'file_name' => $fileName,
                            'url' => $item['image']['url'],
                            'url_full' => $item['image']['url_full'],
                        ]);
                    } else {
                        Log::debug('Изображение без path или file_name', [
                            'image_structure' => $image,
                        ]);
                    }
                }
                return $item;
            }, $results);
            
            // Логируем структуру первого объекта для отладки
            if (!empty($processedResults)) {
                $firstObject = $processedResults[0];
                Log::info('Структура первого объекта из API', [
                    'has_image' => isset($firstObject['image']),
                    'image_structure' => $firstObject['image'] ?? null,
                    'object_keys' => array_keys($firstObject),
                ]);
            }

            return [
                'success' => true,
                'data' => $processedResults,
                'total' => count($processedResults),
                'blocks_count' => $data['data']['blocksCount'] ?? 0,
                'prelaunches_count' => $data['data']['prelaunchesCount'] ?? 0,
                'apartments_count' => $data['data']['apartmentsCount'] ?? 0,
                'booked_apartments_count' => $data['data']['bookedApartmentsCount'] ?? 0,
                'view_apartments_count' => $data['data']['viewApartmentsCount'] ?? 0,
                'source' => 'api',
                'raw_response' => $data,
            ];

        } catch (GuzzleException $e) {
            Log::error('Ошибка при запросе к API blocks/search', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw new \Exception('Ошибка при запросе к API: ' . $e->getMessage());
        }
    }

    /**
     * Получение данных паркинга через API parkings.trendagent.ru/search/blocks
     * 
     * @param array $params Параметры запроса (count, offset, sort, sort_order, city, lang и т.д.)
     * @return array Массив с данными паркинга
     * @throws \Exception
     */
    public function getParkingsSearch(array $params = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            // Получаем токен авторизации
            $authToken = $this->getAuthToken();

            if (empty($authToken)) {
                throw new \Exception('Токен авторизации не найден. Выполните авторизацию сначала.');
            }

            // Формируем URL API для паркинга
            $apiUrl = 'https://parkings.trendagent.ru/search/blocks';
            
            // Параметры по умолчанию
            $defaultParams = [
                'sort' => 'price',
                'sort_order' => 'asc',
                'count' => 20,
                'offset' => 0,
                'city' => '58c665588b6aa52311afa01b',
                'lang' => 'ru',
            ];

            // Объединяем параметры
            $queryParams = array_merge($defaultParams, $params);
            $queryParams['auth_token'] = $authToken;
            
            // Формируем полный URL
            $fullUrl = $apiUrl . '?' . http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

            Log::info('Запрос к API parkings/search/blocks', [
                'url' => $apiUrl,
                'has_token' => !empty($authToken),
            ]);

            // Выполняем запрос к API
            $response = $this->client->get($fullUrl, [
                'headers' => [
                    'Accept' => 'application/json, text/plain, */*',
                    'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'Origin' => 'https://parkings.trendagent.ru',
                    'Referer' => 'https://parkings.trendagent.ru/',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
                    'Sec-Ch-Ua' => '"Chromium";v="142", "Google Chrome";v="142", "Not_A Brand";v="99"',
                    'Sec-Ch-Ua-Mobile' => '?0',
                    'Sec-Ch-Ua-Platform' => '"Windows"',
                    'Sec-Fetch-Dest' => 'empty',
                    'Sec-Fetch-Mode' => 'cors',
                    'Sec-Fetch-Site' => 'same-site',
                    'Priority' => 'u=1, i',
                ],
                'timeout' => 30,
                'verify' => false,
                'allow_redirects' => true,
            ]);

            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 304) {
                Log::info('API паркинга вернул 304 Not Modified');
                return [
                    'success' => true,
                    'data' => [],
                    'total' => 0,
                    'source' => 'parkings_api',
                    'cached' => true,
                ];
            }
            
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                Log::error('Ошибка при запросе к API паркинга', [
                    'status_code' => $statusCode,
                    'response_body' => substr($body, 0, 500),
                ]);
                throw new \Exception("Ошибка при запросе к API паркинга: HTTP {$statusCode}");
            }

            // Декодируем JSON ответ
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка декодирования JSON ответа: ' . json_last_error_msg());
            }

            // Обрабатываем структуру ответа паркинга
            $results = $data['results'] ?? [];
            $blocksCount = $data['blocksCount'] ?? 0;
            $placesCount = $data['placesCount'] ?? 0;
            $bookedPlacesCount = $data['bookedPlacesCount'] ?? 0;

            // Обрабатываем каждый результат паркинга
            $processedResults = [];
            foreach ($results as $item) {
                // Обрабатываем изображения (массив вместо объекта)
                $images = [];
                if (isset($item['images']) && is_array($item['images'])) {
                    foreach ($item['images'] as $img) {
                        if (isset($img['path']) && isset($img['file_name'])) {
                            $path = rtrim($img['path'], '/');
                            $path = ltrim($path, '/');
                            $fileName = $img['file_name'];
                            $images[] = [
                                'thumbnail' => "https://selcdn.trendagent.ru/images/{$path}/m_{$fileName}",
                                'full' => "https://selcdn.trendagent.ru/images/{$path}/{$fileName}",
                                'path' => $img['path'],
                                'file_name' => $img['file_name'],
                            ];
                        }
                    }
                }

                // Обрабатываем минимальные цены
                $minPrice = null;
                $minPrices = [];
                if (isset($item['min_prices']) && is_array($item['min_prices'])) {
                    foreach ($item['min_prices'] as $priceItem) {
                        $price = $priceItem['price'] ?? null;
                        $placeType = $priceItem['place_type']['value'] ?? null;
                        if ($price !== null) {
                            $minPrices[] = [
                                'price' => $price,
                                'place_type' => $placeType,
                            ];
                            if ($minPrice === null || $price < $minPrice) {
                                $minPrice = $price;
                            }
                        }
                    }
                }

                // Обрабатываем reward (объект вместо массива)
                $reward = null;
                if (isset($item['reward']['label'])) {
                    $reward = $item['reward']['label'];
                }

                // Обрабатываем parking_types
                $parkingTypes = [];
                if (isset($item['parking_types']) && is_array($item['parking_types'])) {
                    foreach ($item['parking_types'] as $type) {
                        $parkingTypes[] = $type['value'] ?? $type['label'] ?? null;
                    }
                }
                $parkingTypes = array_filter($parkingTypes);

                $processedResults[] = [
                    'id' => $item['block_id'] ?? null,
                    'guid' => $item['block_guid'] ?? null,
                    'name' => $item['block_name'] ?? null,
                    'address' => $item['address'] ?? [],
                    'places_count' => $item['places_count'] ?? 0,
                    'builder' => $item['builder'] ?? null,
                    'city' => $item['city'] ?? null,
                    'region' => $item['region'] ?? null,
                    'location' => $item['location'] ?? [],
                    'deadline' => $item['deadline'] ?? null,
                    'min_price' => $minPrice,
                    'min_prices' => $minPrices,
                    'reward' => $reward,
                    'parking_types' => $parkingTypes,
                    'images' => $images,
                    'subways' => $item['subways'] ?? [],
                    'geometry' => $item['geometry'] ?? null,
                    'sales_start_at' => $item['sales_start_at'] ?? [],
                    'property_types' => $item['property_types'] ?? [],
                ];
            }

            Log::info('Данные паркинга получены', [
                'blocks_count' => $blocksCount,
                'places_count' => $placesCount,
                'booked_places_count' => $bookedPlacesCount,
                'results_count' => count($processedResults),
            ]);

            return [
                'success' => true,
                'data' => $processedResults,
                'total' => count($processedResults),
                'blocks_count' => $blocksCount,
                'places_count' => $placesCount,
                'booked_places_count' => $bookedPlacesCount,
                'source' => 'parkings_api',
                'raw_response' => $data,
            ];

        } catch (GuzzleException $e) {
            Log::error('Ошибка Guzzle при получении данных паркинга', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw new \Exception('Ошибка при получении данных паркинга: ' . $e->getMessage(), $e->getCode(), $e);
        } catch (\Exception $e) {
            Log::error('Ошибка при получении данных паркинга', [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Получение данных участков через API house-api.trendagent.ru/v1/search/villages
     * 
     * @param array $params Параметры запроса (count, offset, sort_type, sort_order, city, lang и т.д.)
     * @return array Массив с данными участков
     * @throws \Exception
     */
    public function getPlotsSearch(array $params = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            // Получаем токен авторизации
            $authToken = $this->getAuthToken();

            if (empty($authToken)) {
                throw new \Exception('Токен авторизации не найден. Выполните авторизацию сначала.');
            }

            // Формируем URL API для участков
            $apiUrl = 'https://house-api.trendagent.ru/v1/search/villages';
            
            // Параметры по умолчанию
            $defaultParams = [
                'sort_type' => 'price', // Для участков используется sort_type вместо sort
                'sort_order' => 'asc',
                'count' => 20,
                'offset' => 0,
                'city' => '58c665588b6aa52311afa01b',
                'lang' => 'ru',
            ];

            // Объединяем параметры
            $queryParams = array_merge($defaultParams, $params);
            
            // Преобразуем sort в sort_type для участков
            if (isset($queryParams['sort']) && !isset($queryParams['sort_type'])) {
                $queryParams['sort_type'] = $queryParams['sort'];
                unset($queryParams['sort']);
            }
            
            $queryParams['auth_token'] = $authToken;
            
            // Формируем полный URL
            $fullUrl = $apiUrl . '?' . http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

            Log::info('Запрос к API plots/search/villages', [
                'url' => $apiUrl,
                'has_token' => !empty($authToken),
            ]);

            // Выполняем запрос к API
            $response = $this->client->get($fullUrl, [
                'headers' => [
                    'Accept' => 'application/json, text/plain, */*',
                    'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'Origin' => 'https://house.trendagent.ru',
                    'Referer' => 'https://house.trendagent.ru/',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
                    'Sec-Ch-Ua' => '"Chromium";v="142", "Google Chrome";v="142", "Not_A Brand";v="99"',
                    'Sec-Ch-Ua-Mobile' => '?0',
                    'Sec-Ch-Ua-Platform' => '"Windows"',
                    'Sec-Fetch-Dest' => 'empty',
                    'Sec-Fetch-Mode' => 'cors',
                    'Sec-Fetch-Site' => 'same-site',
                    'Priority' => 'u=1, i',
                ],
                'timeout' => 30,
                'verify' => false,
                'allow_redirects' => true,
            ]);

            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 304) {
                Log::info('API участков вернул 304 Not Modified');
                return [
                    'success' => true,
                    'data' => [],
                    'total' => 0,
                    'source' => 'plots_api',
                    'cached' => true,
                ];
            }
            
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                Log::error('Ошибка при запросе к API участков', [
                    'status_code' => $statusCode,
                    'response_body' => substr($body, 0, 500),
                ]);
                throw new \Exception("Ошибка при запросе к API участков: HTTP {$statusCode}");
            }

            // Декодируем JSON ответ
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка декодирования JSON ответа: ' . json_last_error_msg());
            }

            // Обрабатываем структуру ответа участков
            $results = $data['list'] ?? [];
            $totalCount = $data['total_count'] ?? 0;
            $resultCount = $data['result_count'] ?? 0;
            $plotsCount = $data['plots_count'] ?? 0;

            // Обрабатываем каждый результат участков
            $processedResults = [];
            foreach ($results as $item) {
                // Обрабатываем изображения
                $images = [];
                if (isset($item['images']) && is_array($item['images'])) {
                    foreach ($item['images'] as $img) {
                        if (isset($img['path']) && isset($img['file_name'])) {
                            $path = rtrim($img['path'], '/');
                            $path = ltrim($path, '/');
                            $fileName = $img['file_name'];
                            $images[] = [
                                'thumbnail' => "https://selcdn.trendagent.ru/images/{$path}/m_{$fileName}",
                                'full' => "https://selcdn.trendagent.ru/images/{$path}/{$fileName}",
                                'path' => $img['path'],
                                'file_name' => $img['file_name'],
                            ];
                        }
                    }
                }

                // Обрабатываем минимальные цены
                $minPrices = [];
                if (isset($item['min_prices']) && is_array($item['min_prices'])) {
                    foreach ($item['min_prices'] as $priceItem) {
                        $minPrices[] = [
                            'label' => $priceItem['label'] ?? null,
                            'value' => $priceItem['value'] ?? null,
                            'unit' => $priceItem['unit'] ?? '₽',
                        ];
                    }
                }

                // Обрабатываем reward
                $reward = null;
                $rewardHint = null;
                if (isset($item['reward'])) {
                    $reward = $item['reward']['label'] ?? null;
                    $rewardHint = $item['reward']['hint'] ?? null;
                }

                // Обрабатываем distance
                $distance = null;
                if (isset($item['distance'])) {
                    $distance = [
                        'center' => $item['distance']['center'] ?? null,
                        'railway' => $item['distance']['railway'] ?? null,
                        'highway' => $item['distance']['highway'] ?? null,
                    ];
                }

                $processedResults[] = [
                    'id' => $item['_id'] ?? null,
                    'guid' => $item['guid'] ?? null,
                    'name' => $item['name'] ?? null,
                    'address' => $item['address'] ?? null,
                    'plots_count' => $item['plots_count'] ?? 0,
                    'view_plots_count' => $item['view_plots_count'] ?? 0,
                    'builder' => $item['builder'] ?? null,
                    'distance' => $distance,
                    'deadline' => $item['deadline']['value'] ?? null,
                    'min_prices' => $minPrices,
                    'reward' => $reward,
                    'reward_hint' => $rewardHint,
                    'sales_start' => $item['sales_start'] ?? null,
                    'images' => $images,
                    'is_new_village' => $item['is_new_village'] ?? false,
                    'property_types' => $item['property_types'] ?? [],
                ];
            }

            Log::info('Данные участков получены', [
                'total_count' => $totalCount,
                'result_count' => $resultCount,
                'plots_count' => $plotsCount,
                'results_count' => count($processedResults),
            ]);

            return [
                'success' => true,
                'data' => $processedResults,
                'total' => count($processedResults),
                'total_count' => $totalCount,
                'result_count' => $resultCount,
                'plots_count' => $plotsCount,
                'source' => 'plots_api',
                'raw_response' => $data,
            ];

        } catch (GuzzleException $e) {
            Log::error('Ошибка Guzzle при получении данных участков', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw new \Exception('Ошибка при получении данных участков: ' . $e->getMessage(), $e->getCode(), $e);
        } catch (\Exception $e) {
            Log::error('Ошибка при получении данных участков', [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Получение данных коммерции через API commerce.trendagent.ru/search/blocks
     * 
     * @param array $params Параметры запроса (count, offset, sort, sort_order, city, lang и т.д.)
     * @return array Массив с данными коммерции
     * @throws \Exception
     */
    public function getCommercialSearch(array $params = []): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception('Необходимо сначала выполнить авторизацию');
        }

        try {
            // Получаем токен авторизации
            $authToken = $this->getAuthToken();

            if (empty($authToken)) {
                throw new \Exception('Токен авторизации не найден. Выполните авторизацию сначала.');
            }

            // Формируем URL API для коммерции
            $apiUrl = 'https://commerce.trendagent.ru/search/blocks';
            
            // Параметры по умолчанию
            $defaultParams = [
                'sort' => 'price',
                'sort_order' => 'asc',
                'count' => 20,
                'offset' => 0,
                'city' => '58c665588b6aa52311afa01b',
                'lang' => 'ru',
            ];

            // Объединяем параметры
            $queryParams = array_merge($defaultParams, $params);
            $queryParams['auth_token'] = $authToken;
            
            // Формируем полный URL
            $fullUrl = $apiUrl . '?' . http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

            Log::info('Запрос к API commerce/search/blocks', [
                'url' => $apiUrl,
                'has_token' => !empty($authToken),
            ]);

            // Выполняем запрос к API
            $response = $this->client->get($fullUrl, [
                'headers' => [
                    'Accept' => 'application/json, text/plain, */*',
                    'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'Origin' => 'https://commerce.trendagent.ru',
                    'Referer' => 'https://commerce.trendagent.ru/',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
                    'Sec-Ch-Ua' => '"Chromium";v="142", "Google Chrome";v="142", "Not_A Brand";v="99"',
                    'Sec-Ch-Ua-Mobile' => '?0',
                    'Sec-Ch-Ua-Platform' => '"Windows"',
                    'Sec-Fetch-Dest' => 'empty',
                    'Sec-Fetch-Mode' => 'cors',
                    'Sec-Fetch-Site' => 'same-site',
                    'Priority' => 'u=1, i',
                ],
                'timeout' => 30,
                'verify' => false,
                'allow_redirects' => true,
            ]);

            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 304) {
                Log::info('API коммерции вернул 304 Not Modified');
                return [
                    'success' => true,
                    'data' => [],
                    'total' => 0,
                    'source' => 'commercial_api',
                    'cached' => true,
                ];
            }
            
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                Log::error('Ошибка при запросе к API коммерции', [
                    'status_code' => $statusCode,
                    'response_body' => substr($body, 0, 500),
                ]);
                throw new \Exception("Ошибка при запросе к API коммерции: HTTP {$statusCode}");
            }

            // Декодируем JSON ответ
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка декодирования JSON ответа: ' . json_last_error_msg());
            }

            // Обрабатываем структуру ответа коммерции
            $results = $data['result'] ?? [];
            $premisesCount = $data['premises_count'] ?? 0;
            $blocksCount = $data['blocks_count'] ?? 0;
            $bookedPremisesCount = $data['booked_premises_count'] ?? 0;

            // Обрабатываем каждый результат коммерции
            $processedResults = [];
            foreach ($results as $item) {
                // Обрабатываем изображения
                $images = [];
                if (isset($item['images']) && is_array($item['images'])) {
                    foreach ($item['images'] as $img) {
                        if (isset($img['path']) && isset($img['file_name'])) {
                            $path = rtrim($img['path'], '/');
                            $path = ltrim($path, '/');
                            $fileName = $img['file_name'];
                            $images[] = [
                                'thumbnail' => "https://selcdn.trendagent.ru/images/{$path}/m_{$fileName}",
                                'full' => "https://selcdn.trendagent.ru/images/{$path}/{$fileName}",
                                'path' => $img['path'],
                                'file_name' => $img['file_name'],
                            ];
                        }
                    }
                }

                // Обрабатываем минимальные цены (с purpose)
                $minPrices = [];
                $minPrice = null;
                if (isset($item['min_prices']) && is_array($item['min_prices'])) {
                    foreach ($item['min_prices'] as $priceItem) {
                        $price = $priceItem['price'] ?? null;
                        $purpose = $priceItem['purpose'] ?? null;
                        if ($price !== null) {
                            $minPrices[] = [
                                'price' => $price,
                                'purpose' => $purpose,
                                'value' => $priceItem['value'] ?? null,
                            ];
                            if ($minPrice === null || $price < $minPrice) {
                                $minPrice = $price;
                            }
                        }
                    }
                }

                // Обрабатываем deadline (массив)
                $deadline = null;
                $deadlineOverCheck = false;
                if (isset($item['deadline']) && is_array($item['deadline']) && !empty($item['deadline'])) {
                    // Берем первый deadline
                    $firstDeadline = $item['deadline'][0];
                    if (isset($firstDeadline['deadline'])) {
                        $deadline = $firstDeadline['deadline'];
                    }
                    $deadlineOverCheck = $firstDeadline['deadline_over_check'] ?? false;
                }

                // Обрабатываем reward
                $reward = null;
                if (isset($item['reward']['label'])) {
                    $reward = $item['reward']['label'];
                }

                $processedResults[] = [
                    'id' => $item['block_id'] ?? null,
                    'guid' => $item['guid'] ?? null,
                    'name' => $item['block_name'] ?? null,
                    'address' => $item['address'] ?? null,
                    'premises_count' => $item['premises_count'] ?? 0,
                    'builder' => $item['builder'] ?? null,
                    'district' => $item['district'] ?? null,
                    'location' => $item['location'] ?? [],
                    'deadline' => $deadline,
                    'deadline_over_check' => $deadlineOverCheck,
                    'min_price' => $minPrice,
                    'min_prices' => $minPrices,
                    'reward' => $reward,
                    'images' => $images,
                    'subways' => $item['subways'] ?? [],
                    'sales_start_at' => $item['sales_start_at'] ?? [],
                    'property_types' => $item['property_types'] ?? [],
                    'new_block' => $item['new_block'] ?? false,
                ];
            }

            Log::info('Данные коммерции получены', [
                'premises_count' => $premisesCount,
                'blocks_count' => $blocksCount,
                'booked_premises_count' => $bookedPremisesCount,
                'results_count' => count($processedResults),
            ]);

            return [
                'success' => true,
                'data' => $processedResults,
                'total' => count($processedResults),
                'premises_count' => $premisesCount,
                'blocks_count' => $blocksCount,
                'booked_premises_count' => $bookedPremisesCount,
                'source' => 'commercial_api',
                'raw_response' => $data,
            ];

        } catch (GuzzleException $e) {
            Log::error('Ошибка Guzzle при получении данных коммерции', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw new \Exception('Ошибка при получении данных коммерции: ' . $e->getMessage(), $e->getCode(), $e);
        } catch (\Exception $e) {
            Log::error('Ошибка при получении данных коммерции', [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

