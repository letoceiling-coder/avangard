<?php

namespace App\Console\Commands;

use App\Models\Trend\City;
use App\Services\TrendDirectoriesService;
use App\Services\TrendSsoApiAuth;
use Illuminate\Console\Command;

/**
 * Команда для синхронизации справочников TrendAgent API
 * 
 * Синхронизирует регионы, локации и метро из endpoint directories
 */
class SyncTrendDirectories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trend:sync-directories
                            {--city= : GUID города для синхронизации (например: msk, spb)}
                            {--phone= : Телефон для авторизации (если не указан, используется из конфига)}
                            {--password= : Пароль для авторизации (если не указан, используется из конфига)}
                            {--all : Синхронизировать все активные города с external_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Синхронизация справочников (регионы, локации, метро) из TrendAgent API';

    private TrendSsoApiAuth $authService;
    private TrendDirectoriesService $directoriesService;

    public function __construct()
    {
        parent::__construct();
        $this->authService = new TrendSsoApiAuth();
        $this->directoriesService = new TrendDirectoriesService();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 Синхронизация справочников TrendAgent...');
        $this->newLine();

        // Авторизация
        $phone = $this->option('phone') ?: config('trend.phone');
        $password = $this->option('password') ?: config('trend.password');

        if (empty($phone) || empty($password)) {
            $this->error('❌ Не указаны телефон и/или пароль для авторизации');
            $this->info('Используйте опции --phone и --password или настройте config/trend.php');
            return 1;
        }

        try {
            $this->info('🔐 Авторизация через Trend SSO...');
            $authData = $this->authService->authenticate($phone, $password);
            $this->directoriesService->setAuthToken($authData['auth_token']);
            $this->info('✅ Авторизация успешна!');
            $this->newLine();
        } catch (\Exception $e) {
            $this->error('❌ Ошибка авторизации: ' . $e->getMessage());
            return 1;
        }

        // Получаем города для синхронизации
        $cities = $this->getCities();

        if ($cities->isEmpty()) {
            $this->warn('⚠️  Не найдено городов для синхронизации');
            return 0;
        }

        $this->info("✅ Найдено городов: {$cities->count()}");
        $this->newLine();

        $totalStats = [
            'cities' => $cities->count(),
            'success' => 0,
            'failed' => 0,
            'regions' => ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0],
            'locations' => ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0],
            'subways' => ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0],
        ];

        $progressBar = $this->output->createProgressBar($cities->count());
        $progressBar->start();

        foreach ($cities as $city) {
            try {
                $result = $this->directoriesService->syncAll($city);

                if ($result['success']) {
                    $totalStats['success']++;
                    
                    // Суммируем статистику
                    foreach (['regions', 'locations', 'subways'] as $type) {
                        if (isset($result['stats'][$type])) {
                            foreach ($result['stats'][$type] as $key => $value) {
                                $totalStats[$type][$key] += $value;
                            }
                        }
                    }
                } else {
                    $totalStats['failed']++;
                    $this->newLine();
                    $this->warn("⚠️  Ошибка синхронизации для города {$city->name}: " . implode(', ', $result['errors']));
                }

            } catch (\Exception $e) {
                $totalStats['failed']++;
                $this->newLine();
                $this->error("❌ Ошибка синхронизации для города {$city->name}: " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Выводим статистику
        $this->displayStats($totalStats);

        return 0;
    }

    /**
     * Получить города для синхронизации
     */
    protected function getCities()
    {
        $cityGuid = $this->option('city');
        $syncAll = $this->option('all');

        $query = City::where('is_active', true)
            ->whereNotNull('external_id');

        // Если указан конкретный город
        if ($cityGuid && !$syncAll) {
            $query->where('guid', $cityGuid);
        }

        // Исключаем регионы (только города)
        $query->whereNotNull('region_id');

        return $query->get();
    }

    /**
     * Отобразить статистику синхронизации
     */
    protected function displayStats(array $stats): void
    {
        $this->info('📊 Итоговая статистика');
        $this->newLine();

        $headers = ['Тип', 'Создано', 'Обновлено', 'Пропущено', 'Ошибок'];
        $rows = [
            ['Регионы', $stats['regions']['created'], $stats['regions']['updated'], $stats['regions']['skipped'], $stats['regions']['errors']],
            ['Локации', $stats['locations']['created'], $stats['locations']['updated'], $stats['locations']['skipped'], $stats['locations']['errors']],
            ['Метро', $stats['subways']['created'], $stats['subways']['updated'], $stats['subways']['skipped'], $stats['subways']['errors']],
        ];

        $this->table($headers, $rows);
        $this->newLine();

        $this->info("✅ Успешно синхронизировано городов: {$stats['success']}");
        if ($stats['failed'] > 0) {
            $this->warn("❌ Ошибок синхронизации: {$stats['failed']}");
        }
    }
}

