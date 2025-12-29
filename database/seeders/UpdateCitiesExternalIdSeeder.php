<?php

namespace Database\Seeders;

use App\Models\Trend\City;
use Illuminate\Database\Seeder;

/**
 * Seeder для обновления external_id (MongoDB ObjectId) для городов
 * 
 * Использует известные ObjectId для основных городов
 * Эти значения можно получить из ответов API или документации TrendAgent
 */
class UpdateCitiesExternalIdSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Известные ObjectId для городов (MongoDB _id из TrendAgent)
        // Получено из HTML списка городов на сайте TrendAgent (28.12.2025)
        $citiesExternalIds = [
            'msk' => '5a5cb42159042faa9a218d04',      // Москва
            'spb' => '58c665588b6aa52311afa01b',      // Санкт-Петербург
            'rostov' => '61926fb5bb267a0008de132b',   // Ростов-на-Дону
            'sochi' => '604b5243f9760700074ac345',    // Краснодарский край, Сочи, Республика Адыгея (используем для Сочи)
            'krasnodar' => '604b5243f9760700074ac345', // Краснодарский край, Сочи, Республика Адыгея (используем для Краснодара)
            'ekb' => '650974f78d34c0f790a012a9',      // Екатеринбург
            'nsk' => '618120c1a56997000866c4d8',      // Новосибирск
            // 'krasnoyarsk' => null,                  // Красноярск - нет в списке TrendAgent
            // 'belgorod' => null,                     // Белгород - нет в списке TrendAgent
        ];

        $updated = 0;
        $skipped = 0;

        foreach ($citiesExternalIds as $guid => $externalId) {
            $city = City::where('guid', $guid)->first();
            
            if (!$city) {
                $this->command->warn("Город с guid '{$guid}' не найден");
                continue;
            }

            if (empty($externalId)) {
                $this->command->info("⚠️  Пропущен {$city->name} (guid: {$guid}) - ObjectId не указан");
                $skipped++;
                continue;
            }

            // Обновляем только если external_id пустой или отличается
            if ($city->external_id !== $externalId) {
                $city->update(['external_id' => $externalId]);
                $this->command->info("✅ Обновлен {$city->name} (guid: {$guid}) → external_id: {$externalId}");
                $updated++;
            } else {
                $this->command->info("⏭️  Пропущен {$city->name} (guid: {$guid}) - external_id уже установлен");
                $skipped++;
            }
        }

        $this->command->newLine();
        $this->command->info("📊 Статистика:");
        $this->command->info("   Обновлено: {$updated}");
        $this->command->info("   Пропущено: {$skipped}");
    }
}

