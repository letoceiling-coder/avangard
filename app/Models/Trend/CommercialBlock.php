<?php

namespace App\Models\Trend;

use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CommercialBlock extends BaseTrendModel
{
    use Filterable;
    
    protected $fillable = [
        'city_id', 'builder_id', 'district_id', 'location_id',
        'guid', 'name', 'address', 'external_id',
        'premises_count', 'booked_premises_count',
        'is_new_block', 'is_active',
        'deadlines', 'deadline_date', 'deadline_over_check', 'sales_start_at',
        'reward_label',
        'data_source', 'parsed_at', 'last_synced_at',
        'metadata', 'property_types', 'min_prices',
    ];
    
    protected $casts = [
        'deadlines' => 'array',
        'sales_start_at' => 'array',
        'metadata' => 'array',
        'property_types' => 'array',
        'min_prices' => 'array',
        'is_new_block' => 'boolean',
        'is_active' => 'boolean',
        'deadline_over_check' => 'boolean',
        'deadline_date' => 'datetime',
        'premises_count' => 'integer',
        'booked_premises_count' => 'integer',
    ];
    
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
    
    public function builder(): BelongsTo
    {
        return $this->belongsTo(Builder::class);
    }
    
    public function district(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'district_id');
    }
    
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
    
    public function subways(): BelongsToMany
    {
        return $this->belongsToMany(Subway::class, 'commercial_block_subways')
            ->withPivot(['distance_time', 'distance_type_id', 'priority'])
            ->withTimestamps()
            ->orderByPivot('priority');
    }
    
    public function scopeByCity($query, $cityId)
    {
        return $query->where('city_id', $cityId);
    }
    
    public function scopeNewBlocks($query)
    {
        return $query->where('is_new_block', true);
    }
}

