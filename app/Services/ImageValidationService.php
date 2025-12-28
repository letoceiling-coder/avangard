<?php

namespace App\Services;

use App\Models\Image;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для проверки доступности изображений
 */
class ImageValidationService
{
    /**
     * Таймаут для проверки изображения в секундах
     */
    protected int $timeout = 5;

    /**
     * Проверить доступность одного изображения
     *
     * @param string|Image $urlOrImage URL изображения или модель Image
     * @param int|null $timeout Таймаут в секундах
     * @return array Результат проверки: ['available' => bool, 'status_code' => int|null, 'error' => string|null]
     */
    public function validateImage($urlOrImage, ?int $timeout = null): array
    {
        $timeout = $timeout ?? $this->timeout;
        
        // Если передан объект Image, получаем URL
        if ($urlOrImage instanceof Image) {
            $image = $urlOrImage;
            $url = $image->full_url ?? $image->url_full ?? null;
            
            if (!$url) {
                return [
                    'available' => false,
                    'status_code' => null,
                    'error' => 'URL изображения не найден',
                ];
            }
        } else {
            $url = $urlOrImage;
            $image = null;
        }

        if (empty($url)) {
            return [
                'available' => false,
                'status_code' => null,
                'error' => 'URL не указан',
            ];
        }

        try {
            // Сначала пробуем HEAD запрос (быстрее)
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept' => 'image/*',
                ])
                ->head($url);

            $statusCode = $response->status();
            $available = $statusCode === 200;
            
            // Проверяем Content-Type (если доступен)
            $contentType = $response->header('Content-Type');
            if ($available && $contentType && !str_starts_with($contentType, 'image/')) {
                $available = false;
                $error = "Неправильный Content-Type: {$contentType}";
            } else {
                $error = $available ? null : "HTTP статус: {$statusCode}";
            }

            $result = [
                'available' => $available,
                'status_code' => $statusCode,
                'error' => $error,
            ];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Если HEAD не поддерживается или таймаут, пробуем GET с ограничением
            try {
                $response = Http::timeout($timeout)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                        'Accept' => 'image/*',
                        'Range' => 'bytes=0-1024', // Загружаем только первые 1024 байта
                    ])
                    ->get($url);

                $statusCode = $response->status();
                $available = $statusCode === 200 || $statusCode === 206; // 206 = Partial Content
                
                // Проверяем Content-Type
                $contentType = $response->header('Content-Type');
                if ($available && $contentType && !str_starts_with($contentType, 'image/')) {
                    $available = false;
                    $error = "Неправильный Content-Type: {$contentType}";
                } else {
                    $error = $available ? null : "HTTP статус: {$statusCode}";
                }

                $result = [
                    'available' => $available,
                    'status_code' => $statusCode,
                    'error' => $error,
                ];

            } catch (\Exception $e) {
                $result = [
                    'available' => false,
                    'status_code' => null,
                    'error' => $e->getMessage(),
                ];
            }
        } catch (\Exception $e) {
            $result = [
                'available' => false,
                'status_code' => null,
                'error' => $e->getMessage(),
            ];
        }

        // Сохраняем результат в модель Image, если она была передана
        if ($image instanceof Image) {
            $image->is_available = $result['available'];
            $image->checked_at = now();
            $image->last_error = $result['error'];
            $image->save();
        }

        return $result;
    }

    /**
     * Проверить доступность нескольких изображений
     *
     * @param array $urlsOrImages Массив URL или моделей Image
     * @param int|null $timeout Таймаут в секундах
     * @return array Массив результатов проверки
     */
    public function validateImages(array $urlsOrImages, ?int $timeout = null): array
    {
        $results = [];
        
        foreach ($urlsOrImages as $key => $urlOrImage) {
            $results[$key] = $this->validateImage($urlOrImage, $timeout);
        }

        return $results;
    }

    /**
     * Проверить все изображения объекта Trend
     *
     * @param \App\Models\Trend\BaseTrendModel $object Объект Trend (Block, Parking, Village и т.д.)
     * @param int|null $timeout Таймаут в секундах
     * @return array Статистика проверки
     */
    public function validateTrendObjectImages($object, ?int $timeout = null): array
    {
        if (!method_exists($object, 'images')) {
            Log::warning('Object does not have images relationship', [
                'object_type' => get_class($object),
                'object_id' => $object->id ?? null,
            ]);
            
            return [
                'total' => 0,
                'checked' => 0,
                'available' => 0,
                'unavailable' => 0,
            ];
        }

        $images = $object->images()->get();
        $total = $images->count();
        $available = 0;
        $unavailable = 0;

        foreach ($images as $image) {
            $result = $this->validateImage($image, $timeout);
            
            if ($result['available']) {
                $available++;
            } else {
                $unavailable++;
                Log::debug('Image validation failed', [
                    'image_id' => $image->id,
                    'url' => $image->full_url,
                    'error' => $result['error'],
                    'object_type' => get_class($object),
                    'object_id' => $object->id,
                ]);
            }
        }

        return [
            'total' => $total,
            'checked' => $total,
            'available' => $available,
            'unavailable' => $unavailable,
        ];
    }

    /**
     * Установить таймаут по умолчанию
     *
     * @param int $timeout Таймаут в секундах
     * @return self
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Получить таймаут по умолчанию
     *
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }
}

