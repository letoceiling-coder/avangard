<?php

namespace App\Models\Trend;

use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Parking extends BaseTrendModel
{
    use HasFactory, Filterable;
    
    protected $fillable = [
        'block_id', 'city_id', 'district_id', 'location_id', 'builder_id',
        'external_id', 'block_guid', 'block_name', 'number', 'floor', 'area',
        'latitude', 'longitude',
        'parking_type', 'place_type', 'property_type', 'status', 'status_label',
        'price', 'reward_label',
        'deadline', 'deadline_date', 'deadline_over_check',
        'is_active',
        'data_source', 'parsed_at', 'last_synced_at',
        'metadata',
    ];
    
    protected $casts = [
        'metadata' => 'array',
        'floor' => 'integer',
        'area' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'price' => 'integer',
        'deadline_over_check' => 'boolean',
        'deadline_date' => 'datetime',
        'is_active' => 'boolean',
    ];
    
    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }
    
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
    
    public function district(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'district_id');
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
        return $this->belongsToMany(Subway::class, 'parking_subways')
            ->withPivot(['distance_time', 'distance_type_id', 'priority'])
            ->withTimestamps()
            ->orderByPivot('priority');
    }
    
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }
    
    public function scopeByCity($query, $cityId)
    {
        return $query->where('city_id', $cityId);
    }
    
    public function getFormattedPriceAttribute(): ?string
    {
        return $this->price ? number_format($this->price / 100, 0, '.', ' ') . ' ₽' : null;
    }
}

