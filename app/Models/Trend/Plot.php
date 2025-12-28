<?php

namespace App\Models\Trend;

use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель участка
 */
class Plot extends BaseTrendModel
{
    use Filterable;
    
    protected $fillable = [
        'village_id',
        'city_id',
        'builder_id',
        'location_id',
        'guid',
        'name',
        'address',
        'external_id',
        'crm_id',
        'latitude',
        'longitude',
        'min_price',
        'max_price',
        'area_min',
        'area_max',
        'status',
        'is_active',
        'data_source',
        'parsed_at',
        'last_synced_at',
        'metadata',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'min_price' => 'integer',
        'max_price' => 'integer',
        'area_min' => 'decimal:2',
        'area_max' => 'decimal:2',
        'status' => 'integer',
        'metadata' => 'array',
        'parsed_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];
    
    /**
     * Поселок, к которому принадлежит участок
     */
    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }
    
    /**
     * Город
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
    
    /**
     * Застройщик
     */
    public function builder(): BelongsTo
    {
        return $this->belongsTo(Builder::class);
    }
    
    /**
     * Локация
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
