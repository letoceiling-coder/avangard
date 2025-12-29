<?php

namespace App\Services;

use App\Exceptions\TrendApiException;
use App\Exceptions\TrendParserException;
use App\Models\DataChange;
use App\Models\Image;
use App\Models\PriceHistory;
use App\Models\ParserError;
use App\Models\Trend\Block;
use App\Models\Trend\City;
use App\Models\Trend\Builder;
use App\Models\Trend\Subway;
use App\Models\Trend\Region;
use App\Models\Trend\Location;
use App\Models\Trend\Village;
use App\Models\Trend\Plot;
use App\Models\Trend\CommercialBlock;
use App\Models\Trend\CommercialPremise;
use App\Services\ImageValidationService;
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
            
            // Синхронизация изображений
            if (isset($apiData['images']) && is_array($apiData['images'])) {
                $this->syncImages($block, $apiData['images'], $options);
            }
            
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
            'metadata' => $this->serializeJsonField($apiData['metadata'] ?? null),
            'advantages' => $this->serializeJsonField($apiData['advantages'] ?? null),
            'payment_types' => $this->serializeJsonField($apiData['payment_types'] ?? null),
            'contract_types' => $this->serializeJsonField($apiData['contract_types'] ?? null),
        ], $additionalData);
    }
    
    /**
     * Найти или создать город
     */
    protected function findOrCreateCity(?array $cityData, array $options): ?City
    {
        // Если город передан напрямую в опциях (из парсера), используем его
        if (isset($options['city']) && $options['city'] instanceof City) {
            return $options['city'];
        }
        
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
        
        $guid = $locationData['guid'] ?? null;
        $externalId = $locationData['_id'] ?? $locationData['id'] ?? null;
        
        if (empty($guid)) {
            return null;
        }
        
        // Ищем локацию по guid и city_id (guid должен быть уникальным в рамках города)
        $location = Location::where('guid', $guid)
            ->where('city_id', $city->id)
            ->first();
        
        if ($location) {
            return $location;
        }
        
        // Если не найдена, пытаемся найти по external_id в рамках города
        if ($externalId) {
            $location = Location::where('external_id', $externalId)
                ->where('city_id', $city->id)
                ->first();
            
            if ($location) {
                // Обновляем guid, если он отличается
                if ($location->guid !== $guid) {
                    $location->update(['guid' => $guid]);
                }
                return $location;
            }
        }
        
        // Создаем новую локацию
        if ($options['create_missing_references']) {
            try {
                $location = Location::create([
                    'city_id' => $city->id,
                    'guid' => $guid,
                    'name' => $locationData['name'] ?? '',
                    'crm_id' => $locationData['crm_id'] ?? null,
                    'external_id' => $externalId ? (string) $externalId : null,
                    'is_active' => true,
                ]);
                return $location;
            } catch (\Illuminate\Database\QueryException $e) {
                // Если произошла ошибка дублирования (возможно, параллельное создание),
                // пытаемся найти существующую запись
                if ($e->getCode() == 23000) {
                    $location = Location::where('guid', $guid)
                        ->where('city_id', $city->id)
                        ->first();
                    
                    if ($location) {
                        return $location;
                    }
                    
                    // Если все еще не найдена, проверяем по external_id
                    if ($externalId) {
                        $location = Location::where('external_id', $externalId)
                            ->where('city_id', $city->id)
                            ->first();
                        
                        if ($location) {
                            if ($location->guid !== $guid) {
                                $location->update(['guid' => $guid]);
                            }
                            return $location;
                        }
                    }
                }
                throw $e;
            }
        }
        
        return null;
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
    
    /**
     * Подготовить JSON поле для сохранения в БД
     * 
     * Для полей с cast 'array' в модели Laravel автоматически сериализует массивы в JSON при сохранении,
     * поэтому мы должны передавать массив, а не JSON строку или объект.
     * 
     * Если значение - объект, конвертируем в массив.
     * Если значение - JSON строка, декодируем в массив.
     * Если значение - массив, возвращаем как есть.
     */
    protected function serializeJsonField($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        // Если массив, возвращаем как есть (Laravel сериализует автоматически для cast 'array')
        if (is_array($value)) {
            return $value;
        }
        
        // Если объект (stdClass), конвертируем в массив
        if (is_object($value)) {
            return json_decode(json_encode($value), true);
        }
        
        // Если строка, пытаемся декодировать JSON в массив
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
            // Если не JSON, возвращаем null (нельзя сохранить строку в поле с cast 'array')
            return null;
        }
        
        return null;
    }

    /**
     * Синхронизация изображений для объекта Trend
     *
     * @param \App\Models\Trend\BaseTrendModel $object Объект Trend
     * @param array $imagesData Массив данных изображений из API
     * @param array $options Опции синхронизации
     * @return void
     */
    protected function syncImages($object, array $imagesData, array $options = []): void
    {
        $options = array_merge([
            'check_images' => false,
            'image_validation_timeout' => 5,
        ], $options);

        if (empty($imagesData)) {
            return;
        }

        $imageValidationService = null;
        if ($options['check_images']) {
            $imageValidationService = new ImageValidationService();
        }

        $existingImages = $object->images()->get()->keyBy('external_id');
        $apiImageIds = [];

        foreach ($imagesData as $index => $imageData) {
            $externalId = $imageData['_id'] ?? $imageData['id'] ?? null;
            $apiImageIds[] = $externalId;

            // Подготовка данных изображения
            $imageAttributes = [
                'external_id' => $externalId,
                'file_name' => $imageData['file_name'] ?? $imageData['name'] ?? null,
                'path' => $imageData['path'] ?? null,
                'url_thumbnail' => $imageData['url_thumbnail'] ?? $imageData['thumbnail'] ?? null,
                'url_full' => $imageData['url_full'] ?? $imageData['url'] ?? null,
                'alt' => $imageData['alt'] ?? null,
                'title' => $imageData['title'] ?? null,
                'description' => $imageData['description'] ?? null,
                'width' => $imageData['width'] ?? null,
                'height' => $imageData['height'] ?? null,
                'size' => $imageData['size'] ?? null,
                'mime_type' => $imageData['mime_type'] ?? null,
                'sort_order' => $imageData['sort_order'] ?? $index,
                'is_main' => $imageData['is_main'] ?? ($index === 0),
            ];

            // Поиск существующего изображения
            $image = $existingImages->get($externalId);

            if ($image) {
                // Обновляем существующее изображение
                $image->update($imageAttributes);
            } else {
                // Создаем новое изображение
                $imageAttributes['imageable_type'] = get_class($object);
                $imageAttributes['imageable_id'] = $object->id;
                $image = Image::create($imageAttributes);
            }

            // Проверка доступности изображения, если указано
            if ($options['check_images'] && $imageValidationService) {
                try {
                    $imageValidationService->validateImage($image);
                } catch (\Exception $e) {
                    Log::warning('Image validation error', [
                        'image_id' => $image->id,
                        'url' => $image->full_url,
                        'error' => $e->getMessage(),
                        'object_type' => get_class($object),
                        'object_id' => $object->id,
                    ]);
                }
            }
        }

        // Удаляем изображения, которых нет в API данных
        $imagesToDelete = $existingImages->filter(function ($image) use ($apiImageIds) {
            return !in_array($image->external_id, $apiImageIds);
        });

        foreach ($imagesToDelete as $image) {
            $image->delete(); // Soft delete
        }
    }

    /**
     * Синхронизация поселка (дома с участками)
     *
     * @param array $apiData Данные из API
     * @param array $options Опции синхронизации
     * @return Village
     * @throws TrendParserException
     */
    public function syncVillage(array $apiData, array $options = []): Village
    {
        $options = array_merge([
            'skip_errors' => false,
            'log_errors' => true,
            'update_existing' => true,
            'create_missing_references' => true,
            'track_changes' => true,
            'log_price_changes' => true,
            'check_images' => false,
        ], $options);

        try {
            DB::beginTransaction();

            // Найти или создать справочники
            $city = $this->findOrCreateCity($apiData['city'] ?? null, $options);
            $builder = $this->findOrCreateBuilder($apiData['builder'] ?? null, $options);

            if (!$city) {
                throw new TrendParserException('Город обязателен для поселка');
            }

            // Подготовка данных поселка
            $villageData = $this->prepareVillageData($apiData, [
                'city_id' => $city->id,
                'builder_id' => $builder?->id,
            ]);

            // Поиск существующего поселка
            $village = null;
            if (!empty($villageData['external_id'])) {
                $village = Village::where('external_id', $villageData['external_id'])->first();
            }

            if (!$village && !empty($villageData['guid'])) {
                $village = Village::where('guid', $villageData['guid'])
                    ->where('city_id', $villageData['city_id'])
                    ->first();
            }

            // Создание или обновление
            if ($village && $options['update_existing']) {
                $oldValues = $village->getAttributes();
                $village->update($villageData);

                if ($options['track_changes']) {
                    $changes = $this->detectChanges($oldValues, $villageData, [
                        'critical_fields' => ['is_active'],
                        'important_fields' => ['name', 'address', 'deadline_date', 'sales_start_date'],
                    ]);

                    if (!empty($changes)) {
                        $this->logChanges($village, $changes, 'parser');
                    }
                }
            } elseif (!$village) {
                $village = Village::create($villageData);
            }

            // Синхронизация изображений
            if (isset($apiData['images']) && is_array($apiData['images'])) {
                $this->syncImages($village, $apiData['images'], $options);
            }

            // Пометить как спарсенное
            $village->markAsParsed();
            $village->markAsSynced();

            // Логировать источник
            $village->dataSources()->create([
                'source_type' => 'parser',
                'source_name' => 'TrendAgent API',
                'processed_at' => now(),
            ]);

            DB::commit();

            return $village->fresh(['city', 'builder']);

        } catch (\Exception $e) {
            DB::rollBack();

            if ($options['log_errors']) {
                $this->logError('parsing', 'village', [
                    'external_id' => $apiData['_id'] ?? null,
                    'guid' => $apiData['guid'] ?? null,
                    'error' => $e->getMessage(),
                    'data' => $apiData,
                ], $e);
            }

            if (!$options['skip_errors']) {
                throw new TrendParserException(
                    'Ошибка синхронизации поселка: ' . $e->getMessage(),
                    0,
                    $e,
                    ['api_data' => $apiData]
                );
            }

            throw $e;
        }
    }

    /**
     * Подготовка данных поселка из API
     */
    protected function prepareVillageData(array $apiData, array $additionalData = []): array
    {
        return array_merge([
            'guid' => $apiData['guid'] ?? null,
            'name' => $apiData['name'] ?? null,
            'address' => is_array($apiData['address'] ?? null)
                ? implode(', ', $apiData['address'])
                : ($apiData['address'] ?? null),
            'external_id' => $apiData['_id'] ?? null,
            'plots_count' => $apiData['plots_count'] ?? $apiData['plotsCount'] ?? 0,
            'view_plots_count' => $apiData['view_plots_count'] ?? $apiData['viewPlotsCount'] ?? 0,
            'distance' => $this->serializeJsonField($apiData['distance'] ?? null),
            'deadline' => $apiData['deadline'] ?? null,
            'deadline_date' => $this->parseDate($apiData['deadline_date'] ?? null),
            'sales_start' => $apiData['sales_start'] ?? null,
            'sales_start_date' => $this->parseDate($apiData['sales_start_date'] ?? null),
            'reward_label' => $apiData['reward_label'] ?? null,
            'is_new_village' => $apiData['is_new_village'] ?? $apiData['isNewVillage'] ?? false,
            'is_active' => ($apiData['status'] ?? 1) === 1,
            'data_source' => 'parser',
            'metadata' => $this->serializeJsonField($apiData['metadata'] ?? null),
            'property_types' => $this->serializeJsonField($apiData['property_types'] ?? $apiData['propertyTypes'] ?? null),
        ], $additionalData);
    }

    /**
     * Синхронизация участка
     *
     * @param array $apiData Данные из API
     * @param array $options Опции синхронизации
     * @return Plot
     * @throws TrendParserException
     */
    public function syncPlot(array $apiData, array $options = []): Plot
    {
        $options = array_merge([
            'skip_errors' => false,
            'log_errors' => true,
            'update_existing' => true,
            'create_missing_references' => true,
            'track_changes' => true,
            'log_price_changes' => true,
            'check_images' => false,
        ], $options);

        try {
            DB::beginTransaction();

            // Найти или создать справочники
            $city = $this->findOrCreateCity($apiData['city'] ?? null, $options);
            $builder = $this->findOrCreateBuilder($apiData['builder'] ?? null, $options);
            $location = $this->findOrCreateLocation($apiData['location'] ?? null, $city, $options);
            
            // Поиск поселка, если указан
            $village = null;
            if (isset($apiData['village']) && !empty($apiData['village']['guid'])) {
                $village = Village::where('guid', $apiData['village']['guid'])->first();
            }

            if (!$city) {
                throw new TrendParserException('Город обязателен для участка');
            }

            // Подготовка данных участка
            $plotData = $this->preparePlotData($apiData, [
                'city_id' => $city->id,
                'village_id' => $village?->id,
                'builder_id' => $builder?->id,
                'location_id' => $location?->id,
            ]);

            // Поиск существующего участка
            $plot = null;
            if (!empty($plotData['external_id'])) {
                $plot = Plot::where('external_id', $plotData['external_id'])->first();
            }

            if (!$plot && !empty($plotData['guid'])) {
                $plot = Plot::where('guid', $plotData['guid'])
                    ->where('city_id', $plotData['city_id'])
                    ->first();
            }

            // Создание или обновление
            if ($plot && $options['update_existing']) {
                $oldValues = $plot->getAttributes();
                $plot->update($plotData);

                if ($options['track_changes']) {
                    $changes = $this->detectChanges($oldValues, $plotData, [
                        'critical_fields' => ['min_price', 'max_price', 'is_active'],
                        'important_fields' => ['name', 'address', 'area_min', 'area_max'],
                    ]);

                    if (!empty($changes)) {
                        $this->logChanges($plot, $changes, 'parser');
                    }

                    if ($options['log_price_changes']) {
                        $this->logPriceChanges($plot, $oldValues, $plotData, 'parser');
                    }
                }
            } elseif (!$plot) {
                $plot = Plot::create($plotData);
            }

            // Синхронизация изображений
            if (isset($apiData['images']) && is_array($apiData['images'])) {
                $this->syncImages($plot, $apiData['images'], $options);
            }

            // Пометить как спарсенное
            $plot->markAsParsed();
            $plot->markAsSynced();

            // Логировать источник
            $plot->dataSources()->create([
                'source_type' => 'parser',
                'source_name' => 'TrendAgent API',
                'processed_at' => now(),
            ]);

            DB::commit();

            return $plot->fresh(['city', 'village', 'builder', 'location']);

        } catch (\Exception $e) {
            DB::rollBack();

            if ($options['log_errors']) {
                $this->logError('parsing', 'plot', [
                    'external_id' => $apiData['_id'] ?? null,
                    'guid' => $apiData['guid'] ?? null,
                    'error' => $e->getMessage(),
                    'data' => $apiData,
                ], $e);
            }

            if (!$options['skip_errors']) {
                throw new TrendParserException(
                    'Ошибка синхронизации участка: ' . $e->getMessage(),
                    0,
                    $e,
                    ['api_data' => $apiData]
                );
            }

            throw $e;
        }
    }

    /**
     * Подготовка данных участка из API
     */
    protected function preparePlotData(array $apiData, array $additionalData = []): array
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
            'min_price' => $this->convertPriceToKopecks($apiData['min_price'] ?? null),
            'max_price' => $this->convertPriceToKopecks($apiData['max_price'] ?? null),
            'area_min' => $apiData['area_min'] ?? $apiData['areaMin'] ?? null,
            'area_max' => $apiData['area_max'] ?? $apiData['areaMax'] ?? null,
            'status' => $apiData['status'] ?? 1,
            'is_active' => ($apiData['status'] ?? 1) === 1,
            'data_source' => 'parser',
            'metadata' => $this->serializeJsonField($apiData['metadata'] ?? null),
        ], $additionalData);
    }

    /**
     * Синхронизация коммерческого объекта
     *
     * @param array $apiData Данные из API
     * @param array $options Опции синхронизации
     * @return CommercialBlock
     * @throws TrendParserException
     */
    public function syncCommercialBlock(array $apiData, array $options = []): CommercialBlock
    {
        $options = array_merge([
            'skip_errors' => false,
            'log_errors' => true,
            'update_existing' => true,
            'create_missing_references' => true,
            'track_changes' => true,
            'check_images' => false,
        ], $options);

        try {
            DB::beginTransaction();

            // Найти или создать справочники
            $city = $this->findOrCreateCity($apiData['city'] ?? null, $options);
            $builder = $this->findOrCreateBuilder($apiData['builder'] ?? null, $options);
            $region = $this->findOrCreateRegion($apiData['district'] ?? $apiData['region'] ?? null, $city, $options);
            $location = $this->findOrCreateLocation($apiData['location'] ?? null, $city, $options);

            if (!$city) {
                throw new TrendParserException('Город обязателен для коммерческого объекта');
            }

            // Подготовка данных коммерческого объекта
            $blockData = $this->prepareCommercialBlockData($apiData, [
                'city_id' => $city->id,
                'builder_id' => $builder?->id,
                'district_id' => $region?->id,
                'location_id' => $location?->id,
            ]);

            // Поиск существующего объекта
            $block = null;
            if (!empty($blockData['external_id'])) {
                $block = CommercialBlock::where('external_id', $blockData['external_id'])->first();
            }

            if (!$block && !empty($blockData['guid'])) {
                $block = CommercialBlock::where('guid', $blockData['guid'])
                    ->where('city_id', $blockData['city_id'])
                    ->first();
            }

            // Создание или обновление
            if ($block && $options['update_existing']) {
                $oldValues = $block->getAttributes();
                $block->update($blockData);

                if ($options['track_changes']) {
                    $changes = $this->detectChanges($oldValues, $blockData, [
                        'critical_fields' => ['is_active'],
                        'important_fields' => ['name', 'address', 'deadline_date'],
                    ]);

                    if (!empty($changes)) {
                        $this->logChanges($block, $changes, 'parser');
                    }
                }
            } elseif (!$block) {
                $block = CommercialBlock::create($blockData);
            }

            // Синхронизация изображений
            if (isset($apiData['images']) && is_array($apiData['images'])) {
                $this->syncImages($block, $apiData['images'], $options);
            }

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

            return $block->fresh(['city', 'builder', 'district', 'location']);

        } catch (\Exception $e) {
            DB::rollBack();

            if ($options['log_errors']) {
                $this->logError('parsing', 'commercial_block', [
                    'external_id' => $apiData['_id'] ?? null,
                    'guid' => $apiData['guid'] ?? null,
                    'error' => $e->getMessage(),
                    'data' => $apiData,
                ], $e);
            }

            if (!$options['skip_errors']) {
                throw new TrendParserException(
                    'Ошибка синхронизации коммерческого объекта: ' . $e->getMessage(),
                    0,
                    $e,
                    ['api_data' => $apiData]
                );
            }

            throw $e;
        }
    }

    /**
     * Подготовка данных коммерческого объекта из API
     */
    protected function prepareCommercialBlockData(array $apiData, array $additionalData = []): array
    {
        return array_merge([
            'guid' => $apiData['guid'] ?? null,
            'name' => $apiData['name'] ?? null,
            'address' => is_array($apiData['address'] ?? null)
                ? implode(', ', $apiData['address'])
                : ($apiData['address'] ?? null),
            'external_id' => $apiData['_id'] ?? null,
            'premises_count' => $apiData['premises_count'] ?? $apiData['premisesCount'] ?? 0,
            'booked_premises_count' => $apiData['booked_premises_count'] ?? $apiData['bookedPremisesCount'] ?? 0,
            'is_new_block' => $apiData['is_new_block'] ?? $apiData['isNewBlock'] ?? false,
            'is_active' => ($apiData['status'] ?? 1) === 1,
            'deadlines' => $apiData['deadlines'] ?? null,
            'deadline_date' => $this->parseDate($apiData['deadline_date'] ?? null),
            'deadline_over_check' => $apiData['deadline_over_check'] ?? false,
            'sales_start_at' => $apiData['sales_start_at'] ?? null,
            'reward_label' => $apiData['reward_label'] ?? null,
            'data_source' => 'parser',
            'metadata' => $this->serializeJsonField($apiData['metadata'] ?? null),
            'property_types' => $this->serializeJsonField($apiData['property_types'] ?? $apiData['propertyTypes'] ?? null),
            'min_prices' => $this->serializeJsonField($apiData['min_prices'] ?? $apiData['minPrices'] ?? null),
        ], $additionalData);
    }

    /**
     * Синхронизация коммерческого помещения
     *
     * @param array $apiData Данные из API
     * @param array $options Опции синхронизации
     * @return CommercialPremise
     * @throws TrendParserException
     */
    public function syncCommercialPremise(array $apiData, array $options = []): CommercialPremise
    {
        $options = array_merge([
            'skip_errors' => false,
            'log_errors' => true,
            'update_existing' => true,
            'create_missing_references' => true,
            'track_changes' => true,
            'log_price_changes' => true,
            'check_images' => false,
        ], $options);

        try {
            DB::beginTransaction();

            // Найти или создать справочники
            $city = $this->findOrCreateCity($apiData['city'] ?? null, $options);
            $builder = $this->findOrCreateBuilder($apiData['builder'] ?? null, $options);
            $region = $this->findOrCreateRegion($apiData['district'] ?? $apiData['region'] ?? null, $city, $options);
            $location = $this->findOrCreateLocation($apiData['location'] ?? null, $city, $options);
            
            // Поиск коммерческого объекта, если указан
            $commercialBlock = null;
            if (isset($apiData['block']) && !empty($apiData['block']['guid'])) {
                $commercialBlock = CommercialBlock::where('guid', $apiData['block']['guid'])->first();
            }

            if (!$city) {
                throw new TrendParserException('Город обязателен для коммерческого помещения');
            }

            // Подготовка данных помещения
            $premiseData = $this->prepareCommercialPremiseData($apiData, [
                'city_id' => $city->id,
                'commercial_block_id' => $commercialBlock?->id,
                'builder_id' => $builder?->id,
                'district_id' => $region?->id,
                'location_id' => $location?->id,
            ]);

            // Поиск существующего помещения
            $premise = null;
            if (!empty($premiseData['external_id'])) {
                $premise = CommercialPremise::where('external_id', $premiseData['external_id'])->first();
            }

            if (!$premise && !empty($premiseData['guid'])) {
                $premise = CommercialPremise::where('guid', $premiseData['guid'])
                    ->where('city_id', $premiseData['city_id'])
                    ->first();
            }

            // Создание или обновление
            if ($premise && $options['update_existing']) {
                $oldValues = $premise->getAttributes();
                $premise->update($premiseData);

                if ($options['track_changes']) {
                    $changes = $this->detectChanges($oldValues, $premiseData, [
                        'critical_fields' => ['price', 'is_active', 'is_booked'],
                        'important_fields' => ['name', 'address', 'area'],
                    ]);

                    if (!empty($changes)) {
                        $this->logChanges($premise, $changes, 'parser');
                    }

                    // Логируем изменения цены
                    if ($options['log_price_changes'] && isset($oldValues['price']) && isset($premiseData['price'])) {
                        if ($oldValues['price'] != $premiseData['price']) {
                            PriceHistory::create([
                                'priceable_type' => get_class($premise),
                                'priceable_id' => $premise->id,
                                'price_type' => 'single',
                                'old_price' => $oldValues['price'],
                                'new_price' => $premiseData['price'],
                                'source' => 'parser',
                                'changed_at' => now(),
                                'user_id' => auth()->id(),
                            ]);
                        }
                    }
                }
            } elseif (!$premise) {
                $premise = CommercialPremise::create($premiseData);
            }

            // Синхронизация изображений
            if (isset($apiData['images']) && is_array($apiData['images'])) {
                $this->syncImages($premise, $apiData['images'], $options);
            }

            // Пометить как спарсенное
            $premise->markAsParsed();
            $premise->markAsSynced();

            // Логировать источник
            $premise->dataSources()->create([
                'source_type' => 'parser',
                'source_name' => 'TrendAgent API',
                'processed_at' => now(),
            ]);

            DB::commit();

            return $premise->fresh(['city', 'commercialBlock', 'builder', 'district', 'location']);

        } catch (\Exception $e) {
            DB::rollBack();

            if ($options['log_errors']) {
                $this->logError('parsing', 'commercial_premise', [
                    'external_id' => $apiData['_id'] ?? null,
                    'guid' => $apiData['guid'] ?? null,
                    'error' => $e->getMessage(),
                    'data' => $apiData,
                ], $e);
            }

            if (!$options['skip_errors']) {
                throw new TrendParserException(
                    'Ошибка синхронизации коммерческого помещения: ' . $e->getMessage(),
                    0,
                    $e,
                    ['api_data' => $apiData]
                );
            }

            throw $e;
        }
    }

    /**
     * Подготовка данных коммерческого помещения из API
     */
    protected function prepareCommercialPremiseData(array $apiData, array $additionalData = []): array
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
            'price' => $this->convertPriceToKopecks($apiData['price'] ?? null),
            'price_unit' => $apiData['price_unit'] ?? $apiData['priceUnit'] ?? null,
            'area' => $apiData['area'] ?? null,
            'premise_type' => $apiData['premise_type'] ?? $apiData['premiseType'] ?? null,
            'property_types' => $this->serializeJsonField($apiData['property_types'] ?? $apiData['propertyTypes'] ?? null),
            'status' => $apiData['status'] ?? 1,
            'is_active' => ($apiData['status'] ?? 1) === 1,
            'is_booked' => $apiData['is_booked'] ?? $apiData['isBooked'] ?? false,
            'data_source' => 'parser',
            'metadata' => $this->serializeJsonField($apiData['metadata'] ?? null),
        ], $additionalData);
    }
    
    /**
     * Проверка актуальности конкретного объекта
     * 
     * @param \App\Models\Trend\BaseTrendModel $object Объект для проверки
     * @param string $authToken Токен авторизации
     * @param array $options Опции проверки
     * @return array Результат проверки ['actual' => bool, 'updated' => bool, 'changes' => array]
     * @throws TrendParserException
     */
    public function checkDataActuality($object, string $authToken, array $options = []): array
    {
        $options = array_merge([
            'update_if_changed' => true,
            'track_changes' => true,
            'log_price_changes' => true,
        ], $options);
        
        try {
            // Определяем тип объекта и endpoint
            $typeMapping = $this->getObjectTypeMapping($object);
            if (!$typeMapping) {
                throw new TrendParserException('Неизвестный тип объекта для проверки актуальности');
            }
            
            // Получаем данные из API
            $apiData = $this->fetchObjectFromApi($object, $typeMapping, $authToken);
            
            if (!$apiData) {
                return [
                    'actual' => false,
                    'updated' => false,
                    'changes' => [],
                    'error' => 'Объект не найден в API',
                ];
            }
            
            // Подготавливаем данные для сравнения через соответствующий метод prepare*Data
            $newData = $this->prepareDataForComparison($object, $apiData, $typeMapping);
            
            // Сравниваем данные
            $oldValues = $object->getAttributes();
            
            // Обнаруживаем изменения
            $changes = $this->detectChanges($oldValues, $newData, [
                'critical_fields' => ['min_price', 'max_price', 'price', 'status', 'is_active', 'deadline_date'],
                'important_fields' => ['name', 'address', 'finishing', 'deadline'],
            ]);
            
            $hasChanges = !empty($changes);
            
            // Обновляем, если есть изменения и включено обновление
            if ($hasChanges && $options['update_if_changed']) {
                // Используем соответствующий метод синхронизации
                $syncMethod = $typeMapping['sync_method'];
                $syncedObject = $this->$syncMethod($apiData, [
                    'update_existing' => true,
                    'track_changes' => $options['track_changes'],
                    'log_price_changes' => $options['log_price_changes'],
                ]);
                
                return [
                    'actual' => false,
                    'updated' => true,
                    'changes' => $changes,
                    'object' => $syncedObject,
                ];
            }
            
            return [
                'actual' => !$hasChanges,
                'updated' => false,
                'changes' => $changes,
            ];
            
        } catch (\Exception $e) {
            Log::error('Error checking data actuality', [
                'object_type' => get_class($object),
                'object_id' => $object->id,
                'error' => $e->getMessage(),
            ]);
            
            throw new TrendParserException(
                'Ошибка проверки актуальности: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
    
    /**
     * Массовая проверка актуальности объектов
     * 
     * @param string $objectType Тип объекта (blocks, parkings, villages, etc.)
     * @param int|null $cityId ID города (null для всех городов)
     * @param string $authToken Токен авторизации
     * @param array $options Опции проверки
     * @return array Статистика проверки
     */
    public function checkBatchActuality(string $objectType, ?int $cityId, string $authToken, array $options = []): array
    {
        $options = array_merge([
            'limit' => 100,
            'days' => 7, // Проверять объекты, не обновлявшиеся более N дней
            'update_if_changed' => true,
        ], $options);
        
        $stats = [
            'checked' => 0,
            'updated' => 0,
            'actual' => 0,
            'errors' => 0,
        ];
        
        try {
            // Получаем модель для типа объекта
            $model = $this->getModelForType($objectType);
            if (!$model) {
                throw new TrendParserException("Неизвестный тип объекта: {$objectType}");
            }
            
            // Получаем список объектов для проверки
            $query = $model::outdated($options['days']);
            if ($cityId) {
                $query->where('city_id', $cityId);
            }
            
            $objects = $query->limit($options['limit'])->get();
            
            foreach ($objects as $object) {
                try {
                    $result = $this->checkDataActuality($object, $authToken, $options);
                    $stats['checked']++;
                    
                    if ($result['updated']) {
                        $stats['updated']++;
                    } elseif ($result['actual']) {
                        $stats['actual']++;
                    }
                } catch (\Exception $e) {
                    $stats['errors']++;
                    Log::warning('Error checking object actuality', [
                        'object_type' => $objectType,
                        'object_id' => $object->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            return $stats;
            
        } catch (\Exception $e) {
            Log::error('Error in batch actuality check', [
                'object_type' => $objectType,
                'city_id' => $cityId,
                'error' => $e->getMessage(),
            ]);
            
            throw new TrendParserException(
                'Ошибка массовой проверки актуальности: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
    
    /**
     * Получить маппинг типа объекта для API
     */
    protected function getObjectTypeMapping($object): ?array
    {
        $mappings = [
            Block::class => [
                'type' => 'blocks',
                'endpoint' => 'https://api.trendagent.ru/v4_29/blocks/search/',
                'sync_method' => 'syncBlock',
                'id_field' => 'external_id',
                'prepare_method' => 'prepareBlockData',
            ],
            // Parking синхронизация пока не реализована полностью
            // Parking::class => [
            //     'type' => 'parkings',
            //     'endpoint' => 'https://parkings.trendagent.ru/search/places/',
            //     'sync_method' => 'syncParking',
            //     'id_field' => 'external_id',
            //     'prepare_method' => 'prepareBlockData',
            // ],
            Village::class => [
                'type' => 'villages',
                'endpoint' => 'https://house-api.trendagent.ru/v1/search/villages',
                'sync_method' => 'syncVillage',
                'id_field' => 'external_id',
                'prepare_method' => 'prepareVillageData',
            ],
            Plot::class => [
                'type' => 'plots',
                'endpoint' => 'https://house-api.trendagent.ru/v1/search/plots',
                'sync_method' => 'syncPlot',
                'id_field' => 'external_id',
                'prepare_method' => 'preparePlotData',
            ],
            CommercialBlock::class => [
                'type' => 'commercial-blocks',
                'endpoint' => 'https://commerce.trendagent.ru/search/blocks/',
                'sync_method' => 'syncCommercialBlock',
                'id_field' => 'external_id',
                'prepare_method' => 'prepareCommercialBlockData',
            ],
            CommercialPremise::class => [
                'type' => 'commercial-premises',
                'endpoint' => 'https://commerce.trendagent.ru/search/premises',
                'sync_method' => 'syncCommercialPremise',
                'id_field' => 'external_id',
                'prepare_method' => 'prepareCommercialPremiseData',
            ],
        ];
        
        $class = get_class($object);
        return $mappings[$class] ?? null;
    }
    
    /**
     * Получить модель для типа объекта
     */
    protected function getModelForType(string $type): ?string
    {
        $mappings = [
            'blocks' => Block::class,
            'parkings' => Parking::class,
            'villages' => Village::class,
            'plots' => Plot::class,
            'commercial-blocks' => CommercialBlock::class,
            'commercial-premises' => CommercialPremise::class,
        ];
        
        return $mappings[$type] ?? null;
    }
    
    /**
     * Получить данные объекта из API
     */
    protected function fetchObjectFromApi($object, array $typeMapping, string $authToken): ?array
    {
        try {
            $httpClient = new \GuzzleHttp\Client(['timeout' => 30, 'verify' => false]);
            
            // Пробуем найти объект по external_id или guid
            $params = [
                'city' => $object->city->guid ?? null,
                'lang' => 'ru',
                'count' => 1000, // Достаточно большое число для поиска
            ];
            
            // Если есть guid, добавляем его (если API поддерживает)
            if ($object->guid) {
                $params['guid'] = $object->guid;
            }
            
            $response = $httpClient->get($typeMapping['endpoint'], [
                'query' => $params,
                'headers' => [
                    'Authorization' => 'Bearer ' . $authToken,
                    'Accept' => 'application/json',
                ],
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            if (!isset($data['data']) || !is_array($data['data'])) {
                return null;
            }
            
            // Ищем нужный объект в результатах
            foreach ($data['data'] as $item) {
                if (($object->external_id && ($item['_id'] ?? null) === $object->external_id) ||
                    ($object->guid && ($item['guid'] ?? null) === $object->guid)) {
                    return $item;
                }
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Error fetching object from API', [
                'object_type' => get_class($object),
                'object_id' => $object->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
    
    /**
     * Подготовить данные объекта для сравнения
     */
    protected function prepareDataForComparison($object, array $apiData, array $typeMapping): array
    {
        // Используем соответствующий метод prepare*Data
        $prepareMethod = $typeMapping['prepare_method'] ?? null;
        
        if ($prepareMethod && method_exists($this, $prepareMethod)) {
            return $this->$prepareMethod($apiData, [
                'city_id' => $object->city_id,
                'builder_id' => $object->builder_id ?? null,
                'region_id' => $object->region_id ?? null,
                'location_id' => $object->location_id ?? null,
            ]);
        }
        
        // Fallback: возвращаем исходные данные API
        return $apiData;
    }
}

