<?php

namespace App\Console\Commands;

use App\Services\TrendSsoParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestTrendSsoAuth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trendsso:test 
                            {--phone=+79996371182 : Телефон для авторизации}
                            {--password=Kucaevivan19 : Пароль для авторизации}
                            {--url= : URL страницы логина}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Тестирование авторизации через Trend SSO';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $phone = $this->option('phone');
        $password = $this->option('password');
        $loginUrl = $this->option('url') ?? 'https://sso.trend.tech/login?return_oauth_url=https%3A%2F%2Ftrendagent.ru%2Foauth&return_url=https%3A%2F%2Ftrendagent.ru%2F&app_id=66d84f584c0168b8ccd281c3';

        $this->info('Начало тестирования авторизации через Trend SSO...');
        $this->line('URL: ' . $loginUrl);
        $this->line('Телефон: ' . $phone);
        $this->line('');

        try {
            $parser = new TrendSsoParser($loginUrl);
            
            $this->info('Выполнение авторизации...');
            $startTime = microtime(true);
            
            $authData = $parser->authenticate($phone, $password);
            
            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            $this->info("✓ Авторизация успешна! (время: {$duration} сек)");
            $this->line('');

            // Выводим информацию о полученных данных
            $this->info('Данные авторизации:');
            $this->line('  ✓ Авторизован: ' . ($authData['authenticated'] ? 'Да' : 'Нет'));
            $this->line('  ✓ Cookies: ' . count($authData['cookies']) . ' шт.');
            $this->line('  ✓ Session ID: ' . ($authData['session_id'] ?? 'не найден'));
            
            if (!empty($authData['tokens'])) {
                $this->line('  ✓ Токены:');
                foreach ($authData['tokens'] as $key => $value) {
                    $this->line('    - ' . $key . ': ' . (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value));
                }
            } else {
                $this->line('  ⚠ Токены: не найдены');
            }

            $this->line('');
            $this->info('Список cookies:');
            foreach ($authData['cookies'] as $name => $cookie) {
                $expires = $cookie['expires'] ? date('Y-m-d H:i:s', $cookie['expires']) : 'не установлено';
                $this->line("  - {$name}: {$cookie['value']} (expires: {$expires})");
            }

            $this->line('');
            $this->info('Заголовки для авторизованных запросов:');
            foreach ($authData['headers'] as $key => $value) {
                if ($key === 'Authorization' && strlen($value) > 50) {
                    $value = substr($value, 0, 50) . '...';
                }
                $this->line("  - {$key}: {$value}");
            }

            $this->line('');
            $this->info('Timestamp: ' . $authData['timestamp']);
            $this->line('');

            // Проверяем возможность выполнения авторизованного запроса
            $this->info('Проверка возможности выполнения авторизованных запросов...');
            if ($parser->isAuthenticated()) {
                $this->info('✓ Парсер готов к выполнению авторизованных запросов');
            } else {
                $this->error('✗ Парсер не авторизован');
            }

            $this->line('');
            $this->info('Тестирование завершено успешно!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Ошибка при авторизации:');
            $this->error($e->getMessage());
            $this->line('');
            $this->error('Трассировка:');
            $this->line($e->getTraceAsString());

            Log::error('Ошибка при тестировании Trend SSO авторизации', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}



