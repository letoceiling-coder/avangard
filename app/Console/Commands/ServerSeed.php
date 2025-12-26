<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ServerSeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'server-seed 
                            {--class= : Выполнить конкретный seeder (например: DatabaseSeeder)}
                            {--all : Выполнить все seeders (db:seed)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Отправить запрос на сервер для выполнения seeders';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🌱 Отправка запроса на сервер для выполнения seeders...');
        $this->newLine();

        $class = $this->option('class');
        $all = $this->option('all');

        // Проверяем, что указан либо конкретный seeder, либо все
        if (!$class && !$all) {
            $this->error('❌ Необходимо указать либо --class=<SeederClass>, либо --all');
            $this->newLine();
            $this->info('Примеры:');
            $this->line('  php artisan server-seed --class=RoleSeeder');
            $this->line('  php artisan server-seed --all');
            return 1;
        }

        try {
            $serverUrl = env('DEPLOY_SERVER_URL');
            $deployToken = env('DEPLOY_TOKEN');

            if (!$serverUrl || !$deployToken) {
                $this->error('❌ Не настроены переменные окружения:');
                if (!$serverUrl) {
                    $this->error('   - DEPLOY_SERVER_URL');
                }
                if (!$deployToken) {
                    $this->error('   - DEPLOY_TOKEN');
                }
                $this->newLine();
                $this->info('Добавьте эти переменные в файл .env');
                return 1;
            }

            // Формируем URL endpoint
            $url = rtrim($serverUrl, '/') . '/api/seed';

            // Формируем данные запроса
            $data = [
                'class' => $class,
                'all' => $all,
            ];

            // Удаляем null значения
            $data = array_filter($data, function ($value) {
                return $value !== null && $value !== false;
            });

            $this->info("📤 Отправка запроса на: {$url}");
            if ($class) {
                $this->line("   Seeder: {$class}");
            } else {
                $this->line("   Все seeders (--all)");
            }
            $this->newLine();

            // Настраиваем HTTP клиент с отключенной проверкой SSL по умолчанию
            $client = Http::withHeaders([
                'Authorization' => 'Bearer ' . $deployToken,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->timeout(600) // 10 минут таймаут
              ->withoutVerifying(); // Отключаем проверку SSL сертификата

            // Отправляем запрос
            $response = $client->post($url, $data);

            if ($response->successful()) {
                $result = $response->json();

                if (isset($result['success']) && $result['success']) {
                    $this->info('✅ Seeders успешно выполнены на сервере!');
                    $this->newLine();

                    // Выводим детальную информацию
                    if (isset($result['data'])) {
                        $data = $result['data'];

                        if (isset($data['status'])) {
                            $this->line("Статус: {$data['status']}");
                        }

                        if (isset($data['message'])) {
                            $this->line("Сообщение: {$data['message']}");
                        }

                        if (isset($data['results'])) {
                            $this->newLine();
                            $this->info('Результаты выполнения:');
                            foreach ($data['results'] as $seeder => $status) {
                                if ($status === 'success') {
                                    $this->line("  ✅ {$seeder}");
                                } else {
                                    $this->error("  ❌ {$seeder}: {$status}");
                                }
                            }
                        }

                        if (isset($data['duration_seconds'])) {
                            $this->newLine();
                            $this->line("Время выполнения: {$data['duration_seconds']} сек");
                        }
                    }

                    return 0;
                } else {
                    $this->error('❌ Ошибка выполнения seeders на сервере');
                    $message = $result['message'] ?? 'Неизвестная ошибка';
                    $this->error("   {$message}");

                    if (isset($result['data']['error'])) {
                        $this->error("   Детали: {$result['data']['error']}");
                    }

                    return 1;
                }
            } else {
                $statusCode = $response->status();
                $body = $response->body();

                $this->error("❌ Ошибка HTTP запроса: {$statusCode}");
                
                // Пытаемся распарсить JSON ошибку
                try {
                    $errorData = json_decode($body, true);
                    if (isset($errorData['message'])) {
                        $this->error("   {$errorData['message']}");
                    }
                    if (isset($errorData['error'])) {
                        $this->error("   {$errorData['error']}");
                    }
                } catch (\Exception $e) {
                    $this->error("   Ответ сервера: " . substr($body, 0, 200));
                }

                return 1;
            }

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Ошибка: ' . $e->getMessage());
            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }
            return 1;
        }
    }
}

