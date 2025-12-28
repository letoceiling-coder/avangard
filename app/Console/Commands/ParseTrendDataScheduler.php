<?php

namespace App\Console\Commands;

use App\Models\ParserSchedule;
use App\Models\Trend\City;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ParseTrendDataScheduler extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trend:parse-scheduler 
                            {--dry-run : Показать какие расписания будут выполнены без запуска}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Планировщик парсинга данных TrendAgent на основе расписаний';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('📅 Проверка расписаний парсера...');
        $this->newLine();

        $dryRun = $this->option('dry-run');

        // Получаем все активные расписания
        $schedules = ParserSchedule::active()->get();

        if ($schedules->isEmpty()) {
            $this->warn('⚠️  Не найдено активных расписаний');
            return 0;
        }

        $this->info("Найдено активных расписаний: {$schedules->count()}");
        $this->newLine();

        $runCount = 0;
        $skippedCount = 0;

        foreach ($schedules as $schedule) {
            if ($schedule->shouldRunNow()) {
                $runCount++;

                if ($dryRun) {
                    $this->line("✅ [DRY-RUN] Запуск: {$schedule->object_type_name} (ID: {$schedule->id})");
                    continue;
                }

                $this->info("▶️  Запуск парсинга: {$schedule->object_type_name} (ID: {$schedule->id})");

                try {
                    $this->runSchedule($schedule);
                    $this->info("✅ Парсинг завершен: {$schedule->object_type_name}");
                } catch (\Exception $e) {
                    $this->error("❌ Ошибка парсинга {$schedule->object_type_name}: " . $e->getMessage());
                    Log::error('ParseTrendDataScheduler: Error running schedule', [
                        'schedule_id' => $schedule->id,
                        'object_type' => $schedule->object_type,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }

                $this->newLine();
            } else {
                $skippedCount++;
                if ($this->option('verbose')) {
                    $this->line("⏭️  Пропущено: {$schedule->object_type_name} (ID: {$schedule->id}) - не время запуска");
                }
            }
        }

        $this->newLine();
        $this->info("📊 Итоги:");
        $this->line("   Запущено: {$runCount}");
        $this->line("   Пропущено: {$skippedCount}");

        return 0;
    }

    /**
     * Запуск парсинга по расписанию
     */
    protected function runSchedule(ParserSchedule $schedule): void
    {
        // Подготовка параметров команды
        $command = 'trend:parse';
        $parameters = [
            '--type' => [$schedule->object_type],
        ];

        // Добавление городов, если указаны
        if ($schedule->city_ids !== null && !empty($schedule->city_ids)) {
            $cityGuids = City::whereIn('id', $schedule->city_ids)
                ->pluck('guid')
                ->toArray();

            if (!empty($cityGuids)) {
                $parameters['--city'] = $cityGuids;
            }
        }

        // Добавление опций парсинга
        if ($schedule->check_images) {
            $parameters['--check-images'] = true;
        }

        if ($schedule->force_update) {
            $parameters['--force'] = true;
        }

        if ($schedule->skip_errors) {
            $parameters['--skip-errors'] = true;
        }

        if ($schedule->limit) {
            $parameters['--limit'] = $schedule->limit;
        }

        if ($schedule->offset) {
            $parameters['--offset'] = $schedule->offset;
        }

        // Запуск команды парсинга
        $exitCode = Artisan::call($command, $parameters);

        if ($exitCode !== 0) {
            throw new \Exception("Команда парсинга завершилась с кодом ошибки: {$exitCode}");
        }

        // Обновление времени последнего запуска
        // Статистика будет обновлена при следующем запуске или можно сохранить базовые значения
        $schedule->update([
            'last_run_at' => now(),
        ]);
    }
    
    /**
     * Парсинг статистики из вывода команды
     * 
     * @param string $output Вывод команды
     * @param string $objectType Тип объекта
     * @return array Статистика
     */
    protected function parseStatsFromOutput(string $output, string $objectType): array
    {
        // Базовая реализация - можно улучшить, если команда ParseTrendData будет возвращать JSON
        // Пока возвращаем базовые значения
        return [
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => 0,
        ];
    }
}
