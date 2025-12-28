<?php

namespace Database\Seeders;

use App\Models\Trend\City;
use App\Models\Trend\Region;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Города и регионы TrendAgent
        $citiesData = [
            [
                'name' => 'Москва',
                'guid' => 'msk',
                'is_active' => true,
                'sort_order' => 1,
                'regions' => [
                    ['name' => 'Москва', 'guid' => 'msk-city', 'is_active' => true, 'sort_order' => 1],
                ]
            ],
            [
                'name' => 'Московская область',
                'guid' => 'mo',
                'is_active' => true,
                'sort_order' => 2,
                'regions' => [
                    ['name' => 'Московская область', 'guid' => 'mo-oblast', 'is_active' => true, 'sort_order' => 1],
                ]
            ],
            [
                'name' => 'Санкт-Петербург',
                'guid' => 'spb',
                'is_active' => true,
                'sort_order' => 3,
                'regions' => [
                    ['name' => 'Санкт-Петербург', 'guid' => 'spb-city', 'is_active' => true, 'sort_order' => 1],
                ]
            ],
            [
                'name' => 'Ленинградская область',
                'guid' => 'lo',
                'is_active' => true,
                'sort_order' => 4,
                'regions' => [
                    ['name' => 'Ленинградская область', 'guid' => 'lo-oblast', 'is_active' => true, 'sort_order' => 1],
                ]
            ],
            [
                'name' => 'Ростов-на-Дону',
                'guid' => 'rostov',
                'is_active' => true,
                'sort_order' => 5,
                'regions' => [
                    ['name' => 'Ростов', 'guid' => 'rostov-city', 'is_active' => true, 'sort_order' => 1],
                ]
            ],
            [
                'name' => 'Краснодар',
                'guid' => 'krasnodar',
                'is_active' => true,
                'sort_order' => 6,
                'regions' => [
                    ['name' => 'Краснодар', 'guid' => 'krasnodar-city', 'is_active' => true, 'sort_order' => 1],
                ]
            ],
            [
                'name' => 'Сочи',
                'guid' => 'sochi',
                'is_active' => true,
                'sort_order' => 7,
                'regions' => [
                    ['name' => 'Сочи', 'guid' => 'sochi-city', 'is_active' => true, 'sort_order' => 1],
                ]
            ],
            [
                'name' => 'Екатеринбург',
                'guid' => 'ekb',
                'is_active' => true,
                'sort_order' => 8,
                'regions' => [
                    ['name' => 'Екатеринбург', 'guid' => 'ekb-city', 'is_active' => true, 'sort_order' => 1],
                ]
            ],
            [
                'name' => 'Новосибирск',
                'guid' => 'nsk',
                'is_active' => true,
                'sort_order' => 9,
                'regions' => [
                    ['name' => 'Новосибирск', 'guid' => 'nsk-city', 'is_active' => true, 'sort_order' => 1],
                ]
            ],
            [
                'name' => 'Красноярск',
                'guid' => 'krasnoyarsk',
                'is_active' => true,
                'sort_order' => 10,
                'regions' => [
                    ['name' => 'Красноярск', 'guid' => 'krasnoyarsk-city', 'is_active' => true, 'sort_order' => 1],
                ]
            ],
            [
                'name' => 'Белгород',
                'guid' => 'belgorod',
                'is_active' => true,
                'sort_order' => 11,
                'regions' => [
                    ['name' => 'Белгород', 'guid' => 'belgorod-city', 'is_active' => true, 'sort_order' => 1],
                ]
            ],
        ];

        foreach ($citiesData as $cityData) {
            $regions = $cityData['regions'];
            unset($cityData['regions']);

            $city = City::firstOrCreate(
                ['guid' => $cityData['guid']],
                $cityData
            );

            // Обновляем статус, если город уже существует
            if ($city->wasRecentlyCreated === false) {
                $city->update([
                    'is_active' => $cityData['is_active'],
                    'sort_order' => $cityData['sort_order'],
                ]);
            }

            // Создаем/обновляем регионы для города
            foreach ($regions as $regionData) {
                Region::firstOrCreate(
                    [
                        'city_id' => $city->id,
                        'guid' => $regionData['guid'],
                    ],
                    array_merge($regionData, ['city_id' => $city->id])
                );

                // Обновляем статус, если регион уже существует
                $region = Region::where('city_id', $city->id)
                    ->where('guid', $regionData['guid'])
                    ->first();
                
                if ($region) {
                    $region->update([
                        'is_active' => $regionData['is_active'],
                        'sort_order' => $regionData['sort_order'],
                    ]);
                }
            }
        }
    }
}
