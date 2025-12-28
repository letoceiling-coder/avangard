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
        // Известные ObjectId для городов (MongoDB _id из API TrendAgent)
        // Значения можно получить из ответов API или документации
        $citiesExternalIds = [
            'msk' => '5a5cb42159042faa9a218d04',      // Москва (из документации TREND_API_DATA_STRUCTURES.md)
            'spb' => null,                             // Санкт-Петербург - нужно получить
            'rostov' => null,                          // Ростов-на-Дону - нужно получить
            'krasnodar' => null,                       // Краснодар - нужно получить
            'sochi' => null,                           // Сочи - нужно получить
            'ekb' => null,                             // Екатеринбург - нужно получить
            'nsk' => null,                             // Новосибирск - нужно получить
            'krasnoyarsk' => null,                     // Красноярск - нужно получить
            'belgorod' => null,                        // Белгород - нужно получить
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

