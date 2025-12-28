<?php

namespace App\Models\Trend;

use App\Models\Image;
use App\Models\DataSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Базовая модель для всех объектов Trend (блоки, паркинг, поселки, коммерция)
 */
abstract class BaseTrendModel extends Model
{
    use SoftDeletes;
    
    protected $dates = ['deleted_at', 'parsed_at', 'last_synced_at'];
    
    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'parsed_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];
    
    /**
     * Полиморфная связь с изображениями
     */
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')->orderBy('sort_order');
    }
    
    /**
     * Главное изображение
     */
    public function mainImage(): MorphOne
    {
        return $this->morphOne(Image::class, 'imageable')
            ->where('is_main', true)
            ->orderBy('sort_order');
    }
    
    /**
     * Связь с источниками данных (полиморфная)
     */
    public function dataSources(): MorphMany
    {
        return $this->morphMany(DataSource::class, 'sourceable')->latest();
    }
    
    /**
     * Scope: только активные записи
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope: фильтр по источнику данных
     */
    public function scopeFromSource($query, string $source)
    {
        return $query->where('data_source', $source);
    }
    
    /**
     * Scope: записи из парсера
     */
    public function scopeFromParser($query)
    {
        return $query->where('data_source', 'parser');
    }
    
    /**
     * Scope: записи созданные вручную
     */
    public function scopeManual($query)
    {
        return $query->where('data_source', 'manual');
    }
    
    /**
     * Scope: записи из feed/файлов
     */
    public function scopeFromFeed($query)
    {
        return $query->where('data_source', 'feed');
    }
    
    /**
     * Обновить время последней синхронизации
     */
    public function markAsSynced(): void
    {
        $this->update(['last_synced_at' => now()]);
    }
    
    /**
     * Пометить как спарсенное
     */
    public function markAsParsed(): void
    {
        $this->update(['parsed_at' => now(), 'data_source' => 'parser']);
    }
}

