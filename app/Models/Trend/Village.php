<?php

namespace App\Models\Trend;

use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Village extends BaseTrendModel
{
    use Filterable;
    
    protected $fillable = [
        'city_id', 'builder_id',
        'guid', 'name', 'address', 'external_id',
        'plots_count', 'view_plots_count',
        'distance',
        'deadline', 'deadline_date', 'sales_start', 'sales_start_date',
        'reward_label',
        'is_new_village', 'is_active',
        'data_source', 'parsed_at', 'last_synced_at',
        'metadata', 'property_types',
    ];
    
    protected $casts = [
        'distance' => 'array',
        'metadata' => 'array',
        'property_types' => 'array',
        'is_new_village' => 'boolean',
        'is_active' => 'boolean',
        'deadline_date' => 'datetime',
        'sales_start_date' => 'datetime',
        'plots_count' => 'integer',
        'view_plots_count' => 'integer',
    ];
    
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
    
    public function builder(): BelongsTo
    {
        return $this->belongsTo(Builder::class);
    }
    
    public function prices(): HasMany
    {
        return $this->hasMany(VillagePrice::class)->orderBy('sort_order');
    }
    
    public function scopeByCity($query, $cityId)
    {
        return $query->where('city_id', $cityId);
    }
    
    public function scopeNewVillages($query)
    {
        return $query->where('is_new_village', true);
    }
}

