<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Модель для истории изменений цен объектов Trend
 */
class PriceHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'priceable_type',
        'priceable_id',
        'price_type',
        'old_price',
        'new_price',
        'change_percent',
        'change_amount',
        'source',
        'user_id',
        'changed_at',
    ];

    protected $casts = [
        'old_price' => 'integer',
        'new_price' => 'integer',
        'change_percent' => 'decimal:2',
        'change_amount' => 'integer',
        'changed_at' => 'datetime',
    ];

    /**
     * Полиморфная связь с объектом цены
     */
    public function priceable(): MorphTo
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
     * Scope: изменения по типу цены
     */
    public function scopeByPriceType($query, string $type)
    {
        return $query->where('price_type', $type);
    }

    /**
     * Scope: изменения минимальной цены
     */
    public function scopeMinPrice($query)
    {
        return $query->where('price_type', 'min');
    }

    /**
     * Scope: изменения максимальной цены
     */
    public function scopeMaxPrice($query)
    {
        return $query->where('price_type', 'max');
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
     * Scope: только повышения цен
     */
    public function scopePriceIncreases($query)
    {
        return $query->whereNotNull('change_amount')->where('change_amount', '>', 0);
    }

    /**
     * Scope: только снижения цен
     */
    public function scopePriceDecreases($query)
    {
        return $query->whereNotNull('change_amount')->where('change_amount', '<', 0);
    }

    /**
     * Accessor: форматированная старая цена
     */
    public function getFormattedOldPriceAttribute(): ?string
    {
        return $this->old_price ? number_format($this->old_price / 100, 0, '.', ' ') . ' ₽' : null;
    }

    /**
     * Accessor: форматированная новая цена
     */
    public function getFormattedNewPriceAttribute(): ?string
    {
        return $this->new_price ? number_format($this->new_price / 100, 0, '.', ' ') . ' ₽' : null;
    }

    /**
     * Accessor: форматированное изменение цены
     */
    public function getFormattedChangeAmountAttribute(): ?string
    {
        if (!$this->change_amount) {
            return null;
        }
        $sign = $this->change_amount > 0 ? '+' : '';
        return $sign . number_format($this->change_amount / 100, 0, '.', ' ') . ' ₽';
    }

    /**
     * Вычислить процент изменения перед сохранением
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($priceHistory) {
            if ($priceHistory->old_price && $priceHistory->new_price) {
                // Вычисляем изменение в абсолютном выражении
                $priceHistory->change_amount = $priceHistory->new_price - $priceHistory->old_price;
                
                // Вычисляем процент изменения
                if ($priceHistory->old_price > 0) {
                    $priceHistory->change_percent = (($priceHistory->new_price - $priceHistory->old_price) / $priceHistory->old_price) * 100;
                }
            }
        });
    }
}
