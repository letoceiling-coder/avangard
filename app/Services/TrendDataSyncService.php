<?php

namespace App\Services;

use App\Exceptions\TrendApiException;
use App\Exceptions\TrendParserException;
use App\Models\DataChange;
use App\Models\PriceHistory;
use App\Models\ParserError;
use App\Models\Trend\Block;
use App\Models\Trend\City;
use App\Models\Trend\Builder;
use App\Models\Trend\Subway;
use App\Models\Trend\Region;
use App\Models\Trend\Location;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Сервис для синхронизации данных из TrendAgent API в БД
 */
class TrendDataSyncService
{
    /**
     * Синхронизация блока из API данных
     *
     * @param array $apiData Данные из API
     * @param array $options Опции синхронизации
     * @return Block
     * @throws TrendParserException
     */
    public function syncBlock(array $apiData, array $options = []): Block
    {
        $options = array_merge([
            'skip_errors' => false,
            'log_errors' => true,
            'update_existing' => true,
            'create_missing_references' => true,
            'track_changes' => true, // Отслеживать изменения
            'log_price_changes' => true, // Логировать изменения цен
        ], $options);
        
        try {
            DB::beginTransaction();
            
            // Найти или создать справочники
            $city = $this->findOrCreateCity($apiData['city'] ?? null, $options);
            $builder = $this->findOrCreateBuilder($apiData['builder'] ?? null, $options);
            $region = $this->findOrCreateRegion($apiData['region'] ?? null, $city, $options);
            $location = $this->findOrCreateLocation($apiData['location'] ?? null, $city, $options);
            
            // Подготовка данных блока
            $blockData = $this->prepareBlockData($apiData, [
                'city_id' => $city->id,
                'builder_id' => $builder->id,
                'region_id' => $region?->id,
                'location_id' => $location?->id,
            ]);
            
            // Поиск существующего блока
            $block = null;
            if (!empty($blockData['external_id'])) {
                $block = Block::where('external_id', $blockData['external_id'])->first();
            }
            
            if (!$block && !empty($blockData['guid']) && !empty($blockData['city_id'])) {
                $block = Block::where('guid', $blockData['guid'])
                    ->where('city_id', $blockData['city_id'])
                    ->first();
            }
            
            // Создание или обновление
            if ($block && $options['update_existing']) {
                // Сохраняем старые значения для сравнения
                $oldValues = $block->getAttributes();
                
                // Обновляем блок
                $block->update($blockData);
                
                // Обнаруживаем и логируем изменения
                if ($options['track_changes']) {
                    $changes = $this->detectChanges($oldValues, $blockData, [
                        'critical_fields' => ['min_price', 'max_price', 'status', 'is_active', 'deadline_date'],
                        'important_fields' => ['name', 'address', 'finishing', 'deadline'],
                    ]);
                    
                    if (!empty($changes)) {
                        $this->logChanges($block, $changes, 'parser');
                    }
                    
                    // Логируем изменения цен отдельно
                    if ($options['log_price_changes']) {
                        $this->logPriceChanges($block, $oldValues, $blockData, 'parser');
                    }
                }
            } elseif (!$block) {
                $block = Block::create($blockData);
            }
            
            // Синхронизация связей
            $this->syncBlockSubways($block, $apiData['subways'] ?? [], $options);
            $this->syncBlockPrices($block, $apiData['min_prices'] ?? [], $options);
            
            // Пометить как спарсенное
            $block->markAsParsed();
            $block->markAsSynced();
            
            // Логировать источник
            $block->dataSources()->create([
                'source_type' => 'parser',
                'source_name' => 'TrendAgent API',
                'processed_at' => now(),
            ]);
            
            DB::commit();
            
            return $block->fresh(['city', 'builder', 'region', 'location']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Логирование ошибки
            if ($options['log_errors']) {
                $this->logError('parsing', 'block', [
                    'external_id' => $apiData['_id'] ?? null,
                    'guid' => $apiData['guid'] ?? null,
                    'error' => $e->getMessage(),
                    'data' => $apiData,
                ], $e);
            }
            
            if (!$options['skip_errors']) {
                throw new TrendParserException(
                    'Ошибка синхронизации блока: ' . $e->getMessage(),
                    0,
                    $e,
                    ['api_data' => $apiData]
                );
            }
            
            throw $e;
        }
    }
    
    /**
     * Подготовка данных блока из API
     */
    protected function prepareBlockData(array $apiData, array $additionalData = []): array
    {
        return array_merge([
            'guid' => $apiData['guid'] ?? null,
            'name' => $apiData['name'] ?? null,
            'address' => is_array($apiData['address'] ?? null) 
                ? implode(', ', $apiData['address']) 
                : ($apiData['address'] ?? null),
            'external_id' => $apiData['_id'] ?? null,
            'crm_id' => $apiData['crm_id'] ?? null,
            'latitude' => $apiData['latitude'] ?? null,
            'longitude' => $apiData['longitude'] ?? null,
            'status' => $apiData['status'] ?? 1,
            'edit_mode' => $apiData['edit_mode'] ?? null,
            'is_suite' => $apiData['is_suite'] ?? false,
            'is_exclusive' => $apiData['exclusive'] ?? false,
            'is_marked' => $apiData['marked'] ?? false,
            'is_active' => ($apiData['status'] ?? 1) === 1,
            'min_price' => $this->convertPriceToKopecks($apiData['min_price'] ?? null),
            'max_price' => $this->convertPriceToKopecks($apiData['max_price'] ?? null),
            'apartments_count' => $apiData['apartmentsCount'] ?? 0,
            'view_apartments_count' => $apiData['viewApartmentsCount'] ?? 0,
            'exclusive_apartments_count' => $apiData['exclusiveApartmentsCount'] ?? 0,
            'deadline' => $apiData['deadline'] ?? null,
            'deadline_date' => $this->parseDate($apiData['deadline_date'] ?? null),
            'deadline_over_check' => $apiData['deadline_over_check'] ?? false,
            'finishing' => $apiData['finishing'] ?? null,
            'data_source' => 'parser',
            'metadata' => $apiData['metadata'] ?? null,
            'advantages' => $apiData['advantages'] ?? null,
            'payment_types' => $apiData['payment_types'] ?? null,
            'contract_types' => $apiData['contract_types'] ?? null,
        ], $additionalData);
    }
    
    /**
     * Найти или создать город
     */
    protected function findOrCreateCity(?array $cityData, array $options): ?City
    {
        if (!$cityData) {
            return null;
        }
        
        $city = City::where('guid', $cityData['guid'] ?? '')->first();
        
        if (!$city && $options['create_missing_references']) {
            $city = City::create([
                'guid' => $cityData['guid'] ?? Str::slug($cityData['name'] ?? ''),
                'name' => $cityData['name'] ?? '',
                'crm_id' => $cityData['crm_id'] ?? null,
                'external_id' => $cityData['_id'] ?? null,
                'is_active' => true,
            ]);
        }
        
        return $city;
    }
    
    /**
     * Найти или создать застройщика
     */
    protected function findOrCreateBuilder(?array $builderData, array $options): ?Builder
    {
        if (!$builderData) {
            return null;
        }
        
        $builder = Builder::where('guid', $builderData['guid'] ?? '')->first();
        
        if (!$builder && $options['create_missing_references']) {
            $builder = Builder::create([
                'guid' => $builderData['guid'] ?? Str::slug($builderData['name'] ?? ''),
                'name' => $builderData['name'] ?? '',
                'crm_id' => $builderData['crm_id'] ?? null,
                'external_id' => $builderData['_id'] ?? null,
                'is_active' => true,
            ]);
        }
        
        return $builder;
    }
    
    /**
     * Найти или создать район
     */
    protected function findOrCreateRegion(?array $regionData, ?City $city, array $options): ?Region
    {
        if (!$regionData || !$city) {
            return null;
        }
        
        $region = Region::where('guid', $regionData['guid'] ?? '')
            ->where('city_id', $city->id)
            ->first();
        
        if (!$region && $options['create_missing_references']) {
            $region = Region::create([
                'city_id' => $city->id,
                'guid' => $regionData['guid'] ?? Str::slug($regionData['name'] ?? ''),
                'name' => $regionData['name'] ?? '',
                'crm_id' => $regionData['crm_id'] ?? null,
                'external_id' => $regionData['_id'] ?? null,
                'is_active' => true,
            ]);
        }
        
        return $region;
    }
    
    /**
     * Найти или создать локацию
     */
    protected function findOrCreateLocation(?array $locationData, ?City $city, array $options): ?Location
    {
        if (!$locationData || !$city) {
            return null;
        }
        
        $location = Location::where('guid', $locationData['guid'] ?? '')
            ->where('city_id', $city->id)
            ->first();
        
        if (!$location && $options['create_missing_references']) {
            $location = Location::create([
                'city_id' => $city->id,
                'guid' => $locationData['guid'] ?? Str::slug($locationData['name'] ?? ''),
                'name' => $locationData['name'] ?? '',
                'crm_id' => $locationData['crm_id'] ?? null,
                'external_id' => $locationData['_id'] ?? null,
                'is_active' => true,
            ]);
        }
        
        return $location;
    }
    
    /**
     * Синхронизация станций метро
     */
    protected function syncBlockSubways(Block $block, array $subways, array $options): void
    {
        if (empty($subways)) {
            return;
        }
        
        $syncData = [];
        foreach ($subways as $subwayData) {
            $subway = Subway::where('guid', $subwayData['guid'] ?? '')->first();
            
            if ($subway) {
                $syncData[$subway->id] = [
                    'distance_time' => $subwayData['distance_timing'] ?? null,
                    'distance_type_id' => $subwayData['distance_type_id'] ?? 1,
                    'distance_type' => $subwayData['distance_type'] ?? 'пешком',
                    'priority' => $subwayData['priority'] ?? 500,
                ];
            }
        }
        
        if (!empty($syncData)) {
            $block->subways()->sync($syncData);
        }
    }
    
    /**
     * Синхронизация цен
     */
    protected function syncBlockPrices(Block $block, array $prices, array $options): void
    {
        // Реализация синхронизации цен по типам квартир
        // Можно добавить позже при необходимости
    }
    
    /**
     * Конвертация цены в копейки
     */
    protected function convertPriceToKopecks($price): ?int
    {
        if ($price === null || $price === '') {
            return null;
        }
        
        // Если цена уже в копейках (больше 1000000), возвращаем как есть
        if (is_numeric($price) && $price > 1000000) {
            return (int)$price;
        }
        
        // Иначе считаем что цена в рублях и конвертируем
        return is_numeric($price) ? (int)($price * 100) : null;
    }
    
    /**
     * Парсинг даты
     */
    protected function parseDate($date): ?\Carbon\Carbon
    {
        if (!$date) {
            return null;
        }
        
        try {
            return \Carbon\Carbon::parse($date);
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Логирование ошибки
     */
    protected function logError(
        string $errorType,
        ?string $objectType,
        array $context,
        \Throwable $exception
    ): void {
        try {
            ParserError::create([
                'error_type' => $errorType,
                'object_type' => $objectType,
                'source_type' => 'parser',
                'object_class' => $context['object_class'] ?? null,
                'object_id' => $context['object_id'] ?? null,
                'external_id' => $context['external_id'] ?? null,
                'error_code' => $exception->getCode(),
                'error_message' => $exception->getMessage(),
                'error_details' => $exception->getTraceAsString(),
                'context' => $context,
                'api_url' => $context['api_url'] ?? null,
                'http_status_code' => $context['http_status_code'] ?? null,
                'response_body' => $context['response_body'] ?? null,
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            // Если не удалось записать в БД, логируем в файл
            Log::error('Failed to log parser error', [
                'original_error' => $exception->getMessage(),
                'logging_error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Обнаружить изменения между старыми и новыми значениями
     *
     * @param array $oldValues Старые значения
     * @param array $newValues Новые значения
     * @param array $fieldPriorities Приоритеты полей
     * @return array Массив изменений
     */
    protected function detectChanges(array $oldValues, array $newValues, array $fieldPriorities): array
    {
        $changes = [];
        
        $criticalFields = $fieldPriorities['critical_fields'] ?? [];
        $importantFields = $fieldPriorities['important_fields'] ?? [];
        
        foreach ($newValues as $field => $newValue) {
            // Пропускаем служебные поля и связи
            if (in_array($field, ['id', 'created_at', 'updated_at', 'deleted_at', 'city_id', 'builder_id', 'region_id', 'location_id'])) {
                continue;
            }
            
            $oldValue = $oldValues[$field] ?? null;
            
            // Нормализуем значения для сравнения
            $oldValueNormalized = $this->normalizeValue($oldValue);
            $newValueNormalized = $this->normalizeValue($newValue);
            
            // Проверяем изменение
            if ($oldValueNormalized !== $newValueNormalized) {
                $changeType = $this->getChangeType($field, $criticalFields, $importantFields);
                
                $changes[] = [
                    'field' => $field,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                    'type' => $changeType,
                ];
            }
        }
        
        return $changes;
    }
    
    /**
     * Нормализовать значение для сравнения
     */
    protected function normalizeValue($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        
        if (is_numeric($value)) {
            return (string) $value;
        }
        
        return $value;
    }
    
    /**
     * Определить тип изменения поля
     */
    protected function getChangeType(string $field, array $criticalFields, array $importantFields): string
    {
        if (in_array($field, $criticalFields)) {
            if (str_contains($field, 'price')) {
                return 'price';
            }
            if (str_contains($field, 'status') || $field === 'is_active') {
                return 'status';
            }
            return 'important';
        }
        
        if (in_array($field, $importantFields)) {
            return 'important';
        }
        
        return 'other';
    }
    
    /**
     * Логировать изменения в таблицу data_changes
     */
    protected function logChanges($model, array $changes, string $source): void
    {
        try {
            foreach ($changes as $change) {
                DataChange::create([
                    'changeable_type' => get_class($model),
                    'changeable_id' => $model->id,
                    'field_name' => $change['field'],
                    'old_value' => $this->serializeValue($change['old_value']),
                    'new_value' => $this->serializeValue($change['new_value']),
                    'change_type' => $change['type'],
                    'source' => $source,
                    'changed_at' => now(),
                    'user_id' => auth()->id(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to log data changes', [
                'model' => get_class($model),
                'model_id' => $model->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Логировать изменения цен в таблицу price_history
     */
    protected function logPriceChanges($model, array $oldValues, array $newValues, string $source): void
    {
        try {
            // Проверяем изменения минимальной цены
            if (isset($newValues['min_price']) && isset($oldValues['min_price'])) {
                $oldPrice = $oldValues['min_price'];
                $newPrice = $newValues['min_price'];
                
                if ($oldPrice != $newPrice) {
                    PriceHistory::create([
                        'priceable_type' => get_class($model),
                        'priceable_id' => $model->id,
                        'price_type' => 'min',
                        'old_price' => $oldPrice,
                        'new_price' => $newPrice,
                        'source' => $source,
                        'changed_at' => now(),
                        'user_id' => auth()->id(),
                    ]);
                }
            }
            
            // Проверяем изменения максимальной цены
            if (isset($newValues['max_price']) && isset($oldValues['max_price'])) {
                $oldPrice = $oldValues['max_price'];
                $newPrice = $newValues['max_price'];
                
                if ($oldPrice != $newPrice) {
                    PriceHistory::create([
                        'priceable_type' => get_class($model),
                        'priceable_id' => $model->id,
                        'price_type' => 'max',
                        'old_price' => $oldPrice,
                        'new_price' => $newPrice,
                        'source' => $source,
                        'changed_at' => now(),
                        'user_id' => auth()->id(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to log price changes', [
                'model' => get_class($model),
                'model_id' => $model->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Сериализовать значение для хранения
     */
    protected function serializeValue($value): ?string
    {
        if ($value === null) {
            return null;
        }
        
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        
        return (string) $value;
    }
}

