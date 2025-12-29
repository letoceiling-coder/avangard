<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Trend\City;

echo "🔍 Проверка идентификаторов городов\n";
echo str_repeat("=", 100) . "\n\n";

$cities = City::where('is_active', true)
    ->whereNotNull('region_id')
    ->orderBy('name')
    ->get();

if ($cities->isEmpty()) {
    echo "❌ Активные города не найдены\n";
    exit(1);
}

echo "📊 Найдено городов: {$cities->count()}\n\n";

echo str_pad("ID", 8) . " | " . 
     str_pad("GUID", 20) . " | " . 
     str_pad("Название", 25) . " | " . 
     str_pad("CRM_ID", 12) . " | " . 
     str_pad("External_ID", 26) . "\n";
echo str_repeat("-", 100) . "\n";

$hasExternalId = 0;
$hasCrmId = 0;
$missingBoth = 0;

foreach ($cities as $city) {
    $id = str_pad($city->id, 8);
    $guid = str_pad($city->guid ?? 'null', 20);
    $name = str_pad(mb_substr($city->name, 0, 23), 25);
    $crmId = str_pad($city->crm_id ?? 'null', 12);
    $externalId = str_pad($city->external_id ?? 'null', 26);
    
    echo "{$id} | {$guid} | {$name} | {$crmId} | {$externalId}\n";
    
    if (!empty($city->external_id)) {
        $hasExternalId++;
    }
    if (!empty($city->crm_id)) {
        $hasCrmId++;
    }
    if (empty($city->external_id) && empty($city->crm_id)) {
        $missingBoth++;
    }
}

echo str_repeat("=", 100) . "\n";
echo "📈 Статистика:\n";
echo "   Всего городов: {$cities->count()}\n";
echo "   С External_ID (MongoDB ObjectId): {$hasExternalId}\n";
echo "   С CRM_ID: {$hasCrmId}\n";
echo "   Без идентификаторов: {$missingBoth}\n";

echo "\n💡 Примечание:\n";
echo "   - GUID: уникальный идентификатор (slug) для использования в API\n";
echo "   - CRM_ID: идентификатор в CRM системе\n";
echo "   - External_ID: MongoDB ObjectId для использования в blocks API\n";
echo "   - ID: внутренний идентификатор в нашей БД\n";

