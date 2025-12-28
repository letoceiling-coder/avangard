<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Модель для логирования изменений данных объектов Trend
 */
class DataChange extends Model
{
    use HasFactory;

    protected $fillable = [
        'changeable_type',
        'changeable_id',
        'field_name',
        'old_value',
        'new_value',
        'change_type',
        'source',
        'user_id',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    /**
     * Полиморфная связь с объектом изменения
     */
    public function changeable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Связь с пользователем (кто внес изменение)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: изменения по типу
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('change_type', $type);
    }

    /**
     * Scope: изменения цены
     */
    public function scopePriceChanges($query)
    {
        return $query->where('change_type', 'price');
    }

    /**
     * Scope: изменения статуса
     */
    public function scopeStatusChanges($query)
    {
        return $query->where('change_type', 'status');
    }

    /**
     * Scope: важные изменения
     */
    public function scopeImportantChanges($query)
    {
        return $query->where('change_type', 'important');
    }

    /**
     * Scope: изменения за период
     */
    public function scopeInPeriod($query, $startDate, $endDate = null)
    {
        $query->where('changed_at', '>=', $startDate);
        if ($endDate) {
            $query->where('changed_at', '<=', $endDate);
        }
        return $query;
    }

    /**
     * Получить декодированные значения (для JSON полей)
     */
    public function getOldValueDecoded()
    {
        if (empty($this->old_value)) {
            return null;
        }
        
        $decoded = json_decode($this->old_value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $this->old_value;
    }

    /**
     * Получить декодированные значения (для JSON полей)
     */
    public function getNewValueDecoded()
    {
        if (empty($this->new_value)) {
            return null;
        }
        
        $decoded = json_decode($this->new_value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $this->new_value;
    }
}
