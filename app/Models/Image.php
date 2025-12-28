<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Полиморфная модель изображений
 * Используется для блоков, паркинга, поселков, коммерции и других сущностей
 */
class Image extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'imageable_type',
        'imageable_id',
        'external_id',
        'path',
        'file_name',
        'url_thumbnail',
        'url_full',
        'alt',
        'title',
        'description',
        'width',
        'height',
        'size',
        'mime_type',
        'local_path',
        'disk',
        'sort_order',
        'is_main',
        'is_available',
        'checked_at',
        'last_error',
    ];
    
    protected $casts = [
        'is_main' => 'boolean',
        'is_available' => 'boolean',
        'width' => 'integer',
        'height' => 'integer',
        'size' => 'integer',
        'sort_order' => 'integer',
        'checked_at' => 'datetime',
    ];
    
    /**
     * Полиморфная связь
     */
    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }
    
    /**
     * Scope: главные изображения
     */
    public function scopeMain($query)
    {
        return $query->where('is_main', true);
    }
    
    /**
     * Scope: отсортированные
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
    
    /**
     * Получить полный URL изображения
     */
    public function getFullUrlAttribute(): ?string
    {
        if ($this->url_full) {
            return $this->url_full;
        }
        
        if ($this->path && $this->file_name) {
            $path = trim($this->path, '/');
            return "https://selcdn.trendagent.ru/images/{$path}/{$this->file_name}";
        }
        
        return null;
    }
    
    /**
     * Получить URL миниатюры
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if ($this->url_thumbnail) {
            return $this->url_thumbnail;
        }
        
        if ($this->path && $this->file_name) {
            $path = trim($this->path, '/');
            return "https://selcdn.trendagent.ru/images/{$path}/m_{$this->file_name}";
        }
        
        return null;
    }
    
    /**
     * Accessor для полного URL (алиас для совместимости)
     */
    public function getUrlFullAttribute(): ?string
    {
        return $this->full_url;
    }
    
    /**
     * Accessor для миниатюры (алиас для совместимости)
     */
    public function getUrlThumbnailAttribute(): ?string
    {
        return $this->thumbnail_url;
    }
}

