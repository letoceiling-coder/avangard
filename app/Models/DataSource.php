<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Логи источников данных
 * Отслеживает, откуда пришли данные (parser, manual, feed, import)
 */
class DataSource extends Model
{
    protected $fillable = [
        'source_type',
        'source_name',
        'source_file',
        'sourceable_type',
        'sourceable_id',
        'user_id',
        'metadata',
        'processed_at',
    ];
    
    protected $casts = [
        'metadata' => 'array',
        'processed_at' => 'datetime',
    ];
    
    /**
     * Полиморфная связь с объектом источника
     */
    public function sourceable(): MorphTo
    {
        return $this->morphTo();
    }
    
    /**
     * Связь с пользователем (кто создал/обновил)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Scope: по типу источника
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('source_type', $type);
    }
}

