<?php

namespace Database\Seeders;

use App\Models\Trend\City;
use App\Models\Trend\Region;
use Illuminate\Database\Seeder;

/**
 * Seeder для обновления списка городов на основе данных из TrendAgent
 * 
 * Использование: php artisan db:seed --class=UpdateCitiesFromTrendAgentSeeder
 */
class UpdateCitiesFromTrendAgentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Данные из HTML TrendAgent (только российские города)
        $trendAgentCities = [
            [
                'external_id' => '5a5cb42159042faa9a218d04',
                'name' => 'Москва',
                'guid' => 'msk',
                'region_name' => 'Московская область',
                'region_guid' => 'mo',
            ],
            [
                'external_id' => '58c665588b6aa52311afa01b',
                'name' => 'Санкт-Петербург',
                'guid' => 'spb',
                'region_name' => 'Ленинградская область',
                'region_guid' => 'lo',
            ],
            [
                'external_id' => '604b5243f9760700074ac345',
                'name' => 'Краснодарский край, Сочи, Республика Адыгея',
                'guid' => 'krasnodar', // Используем krasnodar как основной guid
                'region_name' => 'Краснодарский край',
                'region_guid' => 'kk',
                'note' => 'В TrendAgent это объединенный регион',
            ],
            [
                'external_id' => '61926fb5bb267a0008de132b',
                'name' => 'Ростов-на-Дону',
                'guid' => 'rostov',
                'region_name' => 'Ростовская область',
                'region_guid' => 'ro',
            ],
            [
                'external_id' => '682700dd0e7daf77097d0779',
                'name' => 'Крым',
                'guid' => 'crimea',
                'region_name' => 'Республика Крым',
                'region_guid' => 'crimea-region',
            ],
            [
                'external_id' => '642157fca50429d21e3aa14f',
                'name' => 'Казань',
                'guid' => 'kazan',
                'region_name' => 'Республика Татарстан',
                'region_guid' => 'tatarstan',
            ],
            [
                'external_id' => '674eff862307c824cf56ced3',
                'name' => 'Уфа',
                'guid' => 'ufa',
                'region_name' => 'Республика Башкортостан',
                'region_guid' => 'bashkortostan',
            ],
            [
                'external_id' => '650974f78d34c0f790a012a9',
                'name' => 'Екатеринбург',
                'guid' => 'ekb',
                'region_name' => 'Свердловская область',
                'region_guid' => 'so',
            ],
            [
                'external_id' => '618120c1a56997000866c4d8',
                'name' => 'Новосибирск',
                'guid' => 'nsk',
                'region_name' => 'Новосибирская область',
                'region_guid' => 'no',
            ],
        ];

        $this->command->info('🔄 Обновление списка городов на основе данных TrendAgent...');
        $this->command->newLine();

        $report = [
            'added' => [],
            'updated' => [],
            'deactivated' => [],
            'missing_external_id' => [],
        ];

        // Получаем все текущие города (только города, не регионы)
        $currentCities = City::whereNotNull('region_id')->get();

        // Создаем маппинг для быстрого поиска
        $citiesByExternalId = $currentCities->keyBy('external_id');
        $citiesByGuid = $currentCities->keyBy('guid');

        // Обрабатываем города из TrendAgent
        foreach ($trendAgentCities as $cityData) {
            $externalId = $cityData['external_id'];
            $guid = $cityData['guid'];
            $name = $cityData['name'];
            $regionName = $cityData['region_name'];
            $regionGuid = $cityData['region_guid'];

            // Создаем или находим регион
            $region = Region::firstOrCreate(
                ['guid' => $regionGuid],
                [
                    'name' => $regionName,
                    'is_active' => true,
                    'sort_order' => 0,
                ]
            );

            // Ищем город по external_id или guid
            $city = $citiesByExternalId->get($externalId)
                ?? $citiesByGuid->get($guid)
                ?? null;

            if ($city) {
                // Обновляем существующий город
                $updated = false;
                $changes = [];

                if ($city->external_id !== $externalId) {
                    $city->external_id = $externalId;
                    $updated = true;
                    $changes[] = "external_id: {$city->external_id} → {$externalId}";
                }

                if ($city->name !== $name) {
                    $oldName = $city->name;
                    $city->name = $name;
                    $updated = true;
                    $changes[] = "name: {$oldName} → {$name}";
                }

                if ($city->region_id !== $region->id) {
                    $city->region_id = $region->id;
                    $updated = true;
                    $changes[] = "region_id: обновлен";
                }

                if ($city->is_active !== true) {
                    $city->is_active = true;
                    $updated = true;
                    $changes[] = "is_active: false → true";
                }

                if ($updated) {
                    $city->save();
                    $report['updated'][] = [
                        'guid' => $city->guid,
                        'name' => $city->name,
                        'changes' => $changes,
                    ];
                    $this->command->info("✅ Обновлен: {$city->name} ({$city->guid})");
                    foreach ($changes as $change) {
                        $this->command->line("   - {$change}");
                    }
                } else {
                    $this->command->line("⏭️  Без изменений: {$city->name} ({$city->guid})");
                }
            } else {
                // Создаем новый город
                $city = City::create([
                    'region_id' => $region->id,
                    'guid' => $guid,
                    'name' => $name,
                    'external_id' => $externalId,
                    'is_active' => true,
                    'sort_order' => 0,
                ]);

                $report['added'][] = [
                    'guid' => $guid,
                    'name' => $name,
                    'external_id' => $externalId,
                ];
                $this->command->info("➕ Добавлен: {$name} ({$guid}) - external_id: {$externalId}");
            }
        }

        // Деактивируем города, которых нет в списке TrendAgent
        $trendAgentExternalIds = array_column($trendAgentCities, 'external_id');
        $trendAgentGuids = array_column($trendAgentCities, 'guid');

        foreach ($currentCities as $city) {
            // Пропускаем города, которые уже обработаны
            if (in_array($city->external_id, $trendAgentExternalIds) ||
                in_array($city->guid, $trendAgentGuids)) {
                continue;
            }

            // Деактивируем город
            if ($city->is_active) {
                $city->is_active = false;
                $city->save();
                $report['deactivated'][] = [
                    'guid' => $city->guid,
                    'name' => $city->name,
                    'external_id' => $city->external_id,
                ];
                $this->command->warn("🔴 Деактивирован: {$city->name} ({$city->guid})");
            }
        }

        // Проверяем наличие external_id
        $this->command->newLine();
        $this->command->info('📊 Проверка external_id:');
        $citiesWithoutExternalId = City::whereNotNull('region_id')
            ->where('is_active', true)
            ->whereNull('external_id')
            ->get();

        if ($citiesWithoutExternalId->isEmpty()) {
            $this->command->info('✅ У всех активных городов есть external_id');
        } else {
            $this->command->warn('⚠️  Города без external_id:');
            foreach ($citiesWithoutExternalId as $city) {
                $report['missing_external_id'][] = [
                    'guid' => $city->guid,
                    'name' => $city->name,
                ];
                $this->command->line("   - {$city->name} ({$city->guid})");
            }
        }

        // Выводим итоговый отчет
        $this->command->newLine();
        $this->command->line(str_repeat('=', 60));
        $this->command->info('📋 ИТОГОВЫЙ ОТЧЕТ');
        $this->command->line(str_repeat('=', 60));
        $this->command->newLine();

        $this->command->info('➕ Добавлено городов: ' . count($report['added']));
        if (!empty($report['added'])) {
            foreach ($report['added'] as $item) {
                $this->command->line("   - {$item['name']} ({$item['guid']}) - external_id: {$item['external_id']}");
            }
        }

        $this->command->newLine();
        $this->command->info('✏️  Обновлено городов: ' . count($report['updated']));
        if (!empty($report['updated'])) {
            foreach ($report['updated'] as $item) {
                $this->command->line("   - {$item['name']} ({$item['guid']})");
                foreach ($item['changes'] as $change) {
                    $this->command->line("     • {$change}");
                }
            }
        }

        $this->command->newLine();
        $this->command->warn('🔴 Деактивировано городов: ' . count($report['deactivated']));
        if (!empty($report['deactivated'])) {
            foreach ($report['deactivated'] as $item) {
                $line = "   - {$item['name']} ({$item['guid']})";
                if ($item['external_id']) {
                    $line .= " - external_id: {$item['external_id']}";
                }
                $this->command->line($line);
            }
        }

        $this->command->newLine();
        $this->command->warn('⚠️  Города без external_id: ' . count($report['missing_external_id']));
        if (!empty($report['missing_external_id'])) {
            foreach ($report['missing_external_id'] as $item) {
                $this->command->line("   - {$item['name']} ({$item['guid']})");
            }
        }

        $this->command->newLine();
        $this->command->info('✅ Обновление завершено!');
    }
}

