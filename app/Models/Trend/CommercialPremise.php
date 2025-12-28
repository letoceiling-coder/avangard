<?php

namespace App\Models\Trend;

use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель коммерческого помещения
 */
class CommercialPremise extends BaseTrendModel
{
    use Filterable;
    
    protected $fillable = [
        'commercial_block_id',
        'city_id',
        'builder_id',
        'district_id',
        'location_id',
        'guid',
        'name',
        'address',
        'external_id',
        'crm_id',
        'latitude',
        'longitude',
        'price',
        'price_unit',
        'area',
        'premise_type',
        'property_types',
        'status',
        'is_active',
        'is_booked',
        'data_source',
        'parsed_at',
        'last_synced_at',
        'metadata',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'is_booked' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'price' => 'integer',
        'area' => 'decimal:2',
        'status' => 'integer',
        'property_types' => 'array',
        'metadata' => 'array',
        'parsed_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];
    
    /**
     * Коммерческий объект, к которому принадлежит помещение
     */
    public function commercialBlock(): BelongsTo
    {
        return $this->belongsTo(CommercialBlock::class);
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
     * Район (как district)
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'district_id');
    }
    
    /**
     * Локация
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
