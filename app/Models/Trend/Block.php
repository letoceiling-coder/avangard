<?php

namespace App\Models\Trend;

use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Block extends BaseTrendModel
{
    use HasFactory, Filterable;
    
    protected $fillable = [
        'city_id', 'region_id', 'location_id', 'builder_id',
        'guid', 'name', 'address', 'crm_id', 'external_id',
        'latitude', 'longitude',
        'status', 'edit_mode', 'is_suite', 'is_exclusive', 'is_marked', 'is_active',
        'min_price', 'max_price',
        'apartments_count', 'view_apartments_count', 'exclusive_apartments_count',
        'deadline', 'deadline_date', 'deadline_over_check', 'finishing',
        'data_source', 'parsed_at', 'last_synced_at',
        'metadata', 'advantages', 'payment_types', 'contract_types', 'installments',
    ];
    
    protected $casts = [
        'metadata' => 'array',
        'advantages' => 'array',
        'payment_types' => 'array',
        'contract_types' => 'array',
        'installments' => 'array',
        'is_suite' => 'boolean',
        'is_exclusive' => 'boolean',
        'is_marked' => 'boolean',
        'is_active' => 'boolean',
        'deadline_over_check' => 'boolean',
        'deadline_date' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'min_price' => 'integer',
        'max_price' => 'integer',
        'status' => 'integer',
        'edit_mode' => 'integer',
        'crm_id' => 'integer',
        'apartments_count' => 'integer',
        'view_apartments_count' => 'integer',
        'exclusive_apartments_count' => 'integer',
    ];
    
    // Отношения
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
    
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
    
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
    
    public function builder(): BelongsTo
    {
        return $this->belongsTo(Builder::class);
    }
    
    public function subways(): BelongsToMany
    {
        return $this->belongsToMany(Subway::class, 'block_subways')
            ->withPivot(['distance_time', 'distance_type_id', 'distance_type', 'priority'])
            ->withTimestamps()
            ->orderByPivot('priority');
    }
    
    public function prices(): HasMany
    {
        return $this->hasMany(BlockPrice::class)->orderBy('sort_order');
    }
    
    // Scopes
    public function scopeExclusive($query)
    {
        return $query->where('is_exclusive', true);
    }
    
    public function scopeByCity($query, $cityId)
    {
        return $query->where('city_id', $cityId);
    }
    
    public function scopeByBuilder($query, $builderId)
    {
        return $query->where('builder_id', $builderId);
    }
    
    // Accessors
    public function getFormattedMinPriceAttribute(): ?string
    {
        return $this->min_price ? number_format($this->min_price / 100, 0, '.', ' ') . ' ₽' : null;
    }
    
    public function getFormattedMaxPriceAttribute(): ?string
    {
        return $this->max_price ? number_format($this->max_price / 100, 0, '.', ' ') . ' ₽' : null;
    }
}

