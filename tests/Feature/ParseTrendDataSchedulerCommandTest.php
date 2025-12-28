<?php

namespace Tests\Feature;

use App\Models\ParserSchedule;
use App\Models\Trend\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Tests\TestCase;

class ParseTrendDataSchedulerCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        City::firstOrCreate(
            ['guid' => 'msk'],
            [
                'name' => 'Москва',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );
    }

    /**
     * Тест: Команда планировщика работает без активных расписаний
     */
    public function test_scheduler_works_without_active_schedules(): void
    {
        $this->artisan('trend:parse-scheduler')
            ->expectsOutput('ℹ️ Нет активных расписаний парсера.')
            ->assertExitCode(0);
    }

    /**
     * Тест: Команда планировщика находит активные расписания
     */
    public function test_scheduler_finds_active_schedules(): void
    {
        $city = City::first();
        
        ParserSchedule::create([
            'object_type' => 'blocks',
            'city_ids' => [$city->id],
            'time_from' => '09:00:00',
            'time_to' => '18:00:00',
            'days_of_week' => [1, 2, 3, 4, 5, 6, 7],
            'is_active' => true,
            'check_images' => false,
            'force_update' => false,
            'limit' => 1000,
            'offset' => 0,
            'skip_errors' => true,
            'description' => 'Тестовое расписание',
        ]);

        $this->artisan('trend:parse-scheduler', ['--dry-run' => true])
            ->expectsOutput('🔍 Найдено 1 активных расписаний.')
            ->assertExitCode(0);
    }

    /**
     * Тест: Команда планировщика в режиме dry-run
     */
    public function test_scheduler_dry_run_mode(): void
    {
        $city = City::first();
        
        $schedule = ParserSchedule::create([
            'object_type' => 'blocks',
            'city_ids' => [$city->id],
            'time_from' => '00:00:00',
            'time_to' => '23:59:59',
            'days_of_week' => [1, 2, 3, 4, 5, 6, 7], // Все дни
            'is_active' => true,
            'check_images' => false,
            'force_update' => false,
            'limit' => 1000,
            'offset' => 0,
            'skip_errors' => true,
            'description' => 'Тестовое расписание',
        ]);

        $this->artisan('trend:parse-scheduler', ['--dry-run' => true])
            ->expectsOutput('✅ Расписание должно быть запущено сейчас.')
            ->expectsOutput('[DRY-RUN]')
            ->assertExitCode(0);
    }

    /**
     * Тест: Команда планировщика пропускает неактивные расписания
     */
    public function test_scheduler_skips_inactive_schedules(): void
    {
        $city = City::first();
        
        ParserSchedule::create([
            'object_type' => 'blocks',
            'city_ids' => [$city->id],
            'time_from' => '09:00:00',
            'time_to' => '18:00:00',
            'days_of_week' => [1, 2, 3, 4, 5, 6, 7],
            'is_active' => false, // Неактивное
            'check_images' => false,
            'force_update' => false,
            'limit' => 1000,
            'offset' => 0,
            'skip_errors' => true,
        ]);

        $this->artisan('trend:parse-scheduler')
            ->expectsOutput('ℹ️ Нет активных расписаний парсера.')
            ->assertExitCode(0);
    }

    /**
     * Тест: Команда планировщика проверяет временные диапазоны
     */
    public function test_scheduler_checks_time_ranges(): void
    {
        $city = City::first();
        
        // Создаем расписание, которое должно запускаться только в определенное время
        // Например, с 22:00 до 23:00 (текущее время обычно не попадает в этот диапазон)
        $schedule = ParserSchedule::create([
            'object_type' => 'blocks',
            'city_ids' => [$city->id],
            'time_from' => '22:00:00',
            'time_to' => '23:00:00',
            'days_of_week' => [1, 2, 3, 4, 5, 6, 7],
            'is_active' => true,
            'check_images' => false,
            'force_update' => false,
            'limit' => 1000,
            'offset' => 0,
            'skip_errors' => true,
        ]);

        // Если текущее время не попадает в диапазон, расписание не должно запускаться
        $currentHour = (int) now()->format('H');
        
        if ($currentHour >= 22 && $currentHour < 23) {
            // Если время попадает в диапазон, расписание должно быть запущено
            $this->artisan('trend:parse-scheduler', ['--dry-run' => true])
                ->expectsOutput('✅ Расписание должно быть запущено сейчас.')
                ->assertExitCode(0);
        } else {
            // Если время не попадает, расписание не должно быть запущено
            $this->artisan('trend:parse-scheduler', ['--dry-run' => true])
                ->expectsOutput('ℹ️ Расписание не должно быть запущено сейчас')
                ->assertExitCode(0);
        }
    }
}

