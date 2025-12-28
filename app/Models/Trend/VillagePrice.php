<?php

namespace App\Models\Trend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VillagePrice extends Model
{
    protected $fillable = [
        'village_id',
        'label',
        'price',
        'unformatted_price',
        'unit',
        'sort_order',
    ];
    
    protected $casts = [
        'price' => 'integer',
        'unformatted_price' => 'integer',
        'sort_order' => 'integer',
    ];
    
    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }
    
    public function getFormattedPriceAttribute(): ?string
    {
        return $this->price ? number_format($this->price / 100, 0, '.', ' ') . ' ' . ($this->unit ?? '₽') : null;
    }
}

