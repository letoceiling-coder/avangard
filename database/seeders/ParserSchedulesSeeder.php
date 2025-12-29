<?php

namespace Database\Seeders;

use App\Models\ParserSchedule;
use Illuminate\Database\Seeder;

/**
 * Seeder для создания расписаний парсера
 * 
 * Создает расписание для каждого типа объекта: запуск каждый день в 5:00 утра
 * 
 * Использование: php artisan db:seed --class=ParserSchedulesSeeder
 */
class ParserSchedulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Типы объектов для парсинга
        $objectTypes = [
            'blocks' => 'Блоки (Квартиры)',
            'parkings' => 'Паркинги',
            'villages' => 'Поселки (Дома с участками)',
            'plots' => 'Участки',
            'commercial-blocks' => 'Коммерческие объекты',
            'commercial-premises' => 'Коммерческие помещения',
        ];

        $this->command->info('🔄 Создание расписаний парсера...');
        $this->command->newLine();

        $created = 0;
        $updated = 0;

        foreach ($objectTypes as $objectType => $objectTypeName) {
            // Проверяем, существует ли уже расписание для этого типа
            $schedule = ParserSchedule::where('object_type', $objectType)->first();

            if ($schedule) {
                // Обновляем существующее расписание
                $schedule->update([
                    'time_from' => '05:00',
                    'time_to' => '05:30', // Даем 30 минут на выполнение
                    'days_of_week' => null, // null = все дни недели
                    'is_active' => true,
                    'check_images' => true,
                    'force_update' => false,
                    'limit' => 1000,
                    'offset' => 0,
                    'skip_errors' => false,
                    'description' => "Автоматический парсинг {$objectTypeName} каждый день в 5:00 утра",
                ]);
                $updated++;
                $this->command->info("✅ Обновлено расписание для: {$objectTypeName}");
            } else {
                // Создаем новое расписание
                ParserSchedule::create([
                    'object_type' => $objectType,
                    'city_ids' => null, // null = все активные города
                    'time_from' => '05:00',
                    'time_to' => '05:30', // Даем 30 минут на выполнение
                    'days_of_week' => null, // null = все дни недели
                    'is_active' => true,
                    'check_images' => true,
                    'force_update' => false,
                    'limit' => 1000,
                    'offset' => 0,
                    'skip_errors' => false,
                    'description' => "Автоматический парсинг {$objectTypeName} каждый день в 5:00 утра",
                ]);
                $created++;
                $this->command->info("➕ Создано расписание для: {$objectTypeName}");
            }
        }

        $this->command->newLine();
        $this->command->line(str_repeat('=', 60));
        $this->command->info('📋 ИТОГОВЫЙ ОТЧЕТ');
        $this->command->line(str_repeat('=', 60));
        $this->command->newLine();

        $this->command->info("➕ Создано расписаний: {$created}");
        $this->command->info("✏️  Обновлено расписаний: {$updated}");
        $this->command->newLine();

        $this->command->info('📅 Настройки расписаний:');
        $this->command->line('   • Время запуска: 05:00 - 05:30');
        $this->command->line('   • Дни недели: Все дни (ежедневно)');
        $this->command->line('   • Города: Все активные города');
        $this->command->line('   • Проверка изображений: Включена');
        $this->command->line('   • Лимит объектов: 1000 на тип');
        $this->command->newLine();

        $this->command->info('✅ Расписания парсера успешно созданы!');
    }
}

