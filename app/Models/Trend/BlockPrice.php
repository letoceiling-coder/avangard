<?php

namespace App\Models\Trend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockPrice extends Model
{
    protected $fillable = [
        'block_id',
        'room_type_id',
        'room_type_name',
        'price',
        'sort_order',
    ];
    
    protected $casts = [
        'price' => 'integer',
        'room_type_id' => 'integer',
        'sort_order' => 'integer',
    ];
    
    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }
    
    public function getFormattedPriceAttribute(): ?string
    {
        return $this->price ? number_format($this->price / 100, 0, '.', ' ') . ' ₽' : null;
    }
}

