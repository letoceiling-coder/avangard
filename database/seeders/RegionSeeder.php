<?php

namespace Database\Seeders;

use App\Models\Trend\City;
use App\Models\Trend\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Структура: Регионы (области) → Города
     */
    public function run(): void
    {
        // Регионы (области) с их городами
        $regionsData = [
            [
                'name' => 'Московская область',
                'guid' => 'mo',
                'is_active' => true,
                'sort_order' => 1,
                'cities' => [
                    ['name' => 'Москва', 'guid' => 'msk', 'is_active' => true, 'sort_order' => 1],
                    // Можно добавить другие города области: Балашиха, Химки, Подольск и т.д.
                ]
            ],
            [
                'name' => 'Ленинградская область',
                'guid' => 'lo',
                'is_active' => true,
                'sort_order' => 2,
                'cities' => [
                    ['name' => 'Санкт-Петербург', 'guid' => 'spb', 'is_active' => true, 'sort_order' => 1],
                    // Можно добавить другие города области
                ]
            ],
            [
                'name' => 'Ростовская область',
                'guid' => 'ro',
                'is_active' => true,
                'sort_order' => 3,
                'cities' => [
                    ['name' => 'Ростов-на-Дону', 'guid' => 'rostov', 'is_active' => true, 'sort_order' => 1],
                ]
            ],
            [
                'name' => 'Краснодарский край',
                'guid' => 'kk',
                'is_active' => true,
                'sort_order' => 4,
                'cities' => [
                    ['name' => 'Краснодар', 'guid' => 'krasnodar', 'is_active' => true, 'sort_order' => 1],
                    ['name' => 'Сочи', 'guid' => 'sochi', 'is_active' => true, 'sort_order' => 2],
                ]
            ],
            [
                'name' => 'Свердловская область',
                'guid' => 'so',
                'is_active' => true,
                'sort_order' => 5,
                'cities' => [
                    ['name' => 'Екатеринбург', 'guid' => 'ekb', 'is_active' => true, 'sort_order' => 1],
                ]
            ],
            [
                'name' => 'Новосибирская область',
                'guid' => 'no',
                'is_active' => true,
                'sort_order' => 6,
                'cities' => [
                    ['name' => 'Новосибирск', 'guid' => 'nsk', 'is_active' => true, 'sort_order' => 1],
                ]
            ],
            [
                'name' => 'Красноярский край',
                'guid' => 'kr',
                'is_active' => true,
                'sort_order' => 7,
                'cities' => [
                    ['name' => 'Красноярск', 'guid' => 'krasnoyarsk', 'is_active' => true, 'sort_order' => 1],
                ]
            ],
            [
                'name' => 'Белгородская область',
                'guid' => 'bo',
                'is_active' => true,
                'sort_order' => 8,
                'cities' => [
                    ['name' => 'Белгород', 'guid' => 'belgorod', 'is_active' => true, 'sort_order' => 1],
                ]
            ],
        ];

        foreach ($regionsData as $regionData) {
            $cities = $regionData['cities'];
            unset($regionData['cities']);

            // Создаем или обновляем регион (область)
            $region = Region::firstOrCreate(
                ['guid' => $regionData['guid']],
                $regionData
            );

            // Обновляем статус, если регион уже существует
            if (!$region->wasRecentlyCreated) {
                $region->update([
                    'is_active' => $regionData['is_active'],
                    'sort_order' => $regionData['sort_order'],
                ]);
            }

            // Создаем/обновляем города для региона
            foreach ($cities as $cityData) {
                $city = City::firstOrCreate(
                    ['guid' => $cityData['guid']],
                    array_merge($cityData, ['region_id' => $region->id])
                );

                // Обновляем статус, если город уже существует
                if (!$city->wasRecentlyCreated) {
                    $city->update([
                        'region_id' => $region->id,
                        'is_active' => $cityData['is_active'],
                        'sort_order' => $cityData['sort_order'],
                    ]);
                }
            }
        }
    }
}
