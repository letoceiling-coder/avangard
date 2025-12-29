<?php

/**
 * Скрипт для обновления списка городов на основе данных из TrendAgent
 * 
 * Использование: php update_cities_from_trendagent_list.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Trend\City;
use App\Models\Trend\Region;
use Illuminate\Support\Facades\DB;

// Данные из HTML TrendAgent
$trendAgentCities = [
    [
        'external_id' => '5a5cb42159042faa9a218d04',
        'name' => 'Москва',
        'guid' => 'msk',
    ],
    [
        'external_id' => '58c665588b6aa52311afa01b',
        'name' => 'Санкт-Петербург',
        'guid' => 'spb',
    ],
    [
        'external_id' => '604b5243f9760700074ac345',
        'name' => 'Краснодарский край, Сочи, Республика Адыгея',
        'guid' => 'krasnodar', // или sochi - нужно проверить
        'note' => 'Может быть несколько городов в одном регионе',
    ],
    [
        'external_id' => '61926fb5bb267a0008de132b',
        'name' => 'Ростов-на-Дону',
        'guid' => 'rostov',
    ],
    [
        'external_id' => '682700dd0e7daf77097d0779',
        'name' => 'Крым',
        'guid' => 'crimea',
        'note' => 'New',
    ],
    [
        'external_id' => '642157fca50429d21e3aa14f',
        'name' => 'Казань',
        'guid' => 'kazan',
    ],
    [
        'external_id' => '674eff862307c824cf56ced3',
        'name' => 'Уфа',
        'guid' => 'ufa',
        'note' => 'New',
    ],
    [
        'external_id' => '650974f78d34c0f790a012a9',
        'name' => 'Екатеринбург',
        'guid' => 'ekb',
    ],
    [
        'external_id' => '618120c1a56997000866c4d8',
        'name' => 'Новосибирск',
        'guid' => 'nsk',
    ],
];

echo "🔄 Обновление списка городов на основе данных TrendAgent...\n\n";

$report = [
    'added' => [],
    'updated' => [],
    'deactivated' => [],
    'missing_external_id' => [],
];

// Получаем все текущие города (только города, не регионы)
$currentCities = City::whereNotNull('region_id')->get();

// Создаем маппинг по external_id для быстрого поиска
$citiesByExternalId = $currentCities->keyBy('external_id');
$citiesByGuid = $currentCities->keyBy('guid');

// Обрабатываем города из TrendAgent
foreach ($trendAgentCities as $cityData) {
    $externalId = $cityData['external_id'];
    $guid = $cityData['guid'];
    $name = $cityData['name'];
    
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
            echo "✅ Обновлен: {$city->name} ({$city->guid})\n";
            foreach ($changes as $change) {
                echo "   - {$change}\n";
            }
        } else {
            echo "⏭️  Без изменений: {$city->name} ({$city->guid})\n";
        }
    } else {
        // Создаем новый город
        // Нужно найти или создать регион
        $region = Region::where('name', 'Россия')->first();
        if (!$region) {
            // Создаем регион "Россия" если его нет
            $region = Region::create([
                'name' => 'Россия',
                'guid' => 'russia',
                'is_active' => true,
            ]);
        }
        
        $city = City::create([
            'region_id' => $region->id,
            'guid' => $guid,
            'name' => $name,
            'external_id' => $externalId,
            'is_active' => true,
        ]);
        
        $report['added'][] = [
            'guid' => $guid,
            'name' => $name,
            'external_id' => $externalId,
        ];
        echo "➕ Добавлен: {$name} ({$guid}) - external_id: {$externalId}\n";
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
        echo "🔴 Деактивирован: {$city->name} ({$city->guid})\n";
    }
}

// Проверяем наличие external_id
echo "\n📊 Проверка external_id:\n";
$citiesWithoutExternalId = City::whereNotNull('region_id')
    ->where('is_active', true)
    ->whereNull('external_id')
    ->get();

if ($citiesWithoutExternalId->isEmpty()) {
    echo "✅ У всех активных городов есть external_id\n";
} else {
    echo "⚠️  Города без external_id:\n";
    foreach ($citiesWithoutExternalId as $city) {
        $report['missing_external_id'][] = [
            'guid' => $city->guid,
            'name' => $city->name,
        ];
        echo "   - {$city->name} ({$city->guid})\n";
    }
}

// Выводим итоговый отчет
echo "\n" . str_repeat("=", 60) . "\n";
echo "📋 ИТОГОВЫЙ ОТЧЕТ\n";
echo str_repeat("=", 60) . "\n\n";

echo "➕ Добавлено городов: " . count($report['added']) . "\n";
if (!empty($report['added'])) {
    foreach ($report['added'] as $item) {
        echo "   - {$item['name']} ({$item['guid']}) - external_id: {$item['external_id']}\n";
    }
}

echo "\n✏️  Обновлено городов: " . count($report['updated']) . "\n";
if (!empty($report['updated'])) {
    foreach ($report['updated'] as $item) {
        echo "   - {$item['name']} ({$item['guid']})\n";
        foreach ($item['changes'] as $change) {
            echo "     • {$change}\n";
        }
    }
}

echo "\n🔴 Деактивировано городов: " . count($report['deactivated']) . "\n";
if (!empty($report['deactivated'])) {
    foreach ($report['deactivated'] as $item) {
        echo "   - {$item['name']} ({$item['guid']})";
        if ($item['external_id']) {
            echo " - external_id: {$item['external_id']}";
        }
        echo "\n";
    }
}

echo "\n⚠️  Города без external_id: " . count($report['missing_external_id']) . "\n";
if (!empty($report['missing_external_id'])) {
    foreach ($report['missing_external_id'] as $item) {
        echo "   - {$item['name']} ({$item['guid']})\n";
    }
}

echo "\n✅ Обновление завершено!\n";

