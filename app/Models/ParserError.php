<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Модель для логирования ошибок парсера
 */
class ParserError extends Model
{
    use HasFactory;
    protected $fillable = [
        'error_type',
        'object_type',
        'source_type',
        'object_class',
        'object_id',
        'external_id',
        'error_code',
        'error_message',
        'error_details',
        'context',
        'api_url',
        'http_status_code',
        'response_body',
        'request_method',
        'request_params',
        'is_resolved',
        'resolved_at',
        'resolved_by',
        'resolution_notes',
        'attempts_count',
        'last_attempt_at',
        'user_id',
        'ip_address',
        'user_agent',
    ];
    
    protected $casts = [
        'context' => 'array',
        'request_params' => 'array',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'last_attempt_at' => 'datetime',
        'http_status_code' => 'integer',
        'attempts_count' => 'integer',
    ];
    
    /**
     * Связь с пользователем, который вызвал ошибку
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Связь с пользователем, который решил проблему
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
    
    /**
     * Полиморфная связь с объектом
     */
    public function errorable(): MorphTo
    {
        return $this->morphTo('object', 'object_class', 'object_id');
    }
    
    /**
     * Scope: нерешенные ошибки
     */
    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }
    
    /**
     * Scope: по типу ошибки
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('error_type', $type);
    }
    
    /**
     * Scope: по типу объекта
     */
    public function scopeByObjectType($query, string $objectType)
    {
        return $query->where('object_type', $objectType);
    }
    
    /**
     * Пометить как решенное
     */
    public function markAsResolved(?int $userId = null, ?string $notes = null): void
    {
        $this->update([
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolved_by' => $userId,
            'resolution_notes' => $notes,
        ]);
    }
    
    /**
     * Увеличить счетчик попыток
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempts_count');
        $this->update(['last_attempt_at' => now()]);
    }
}

