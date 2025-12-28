<?php

namespace App\Models\Trend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subway extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'subway_line_id',
        'city_id',
        'guid',
        'name',
        'crm_id',
        'external_id',
        'latitude',
        'longitude',
        'priority',
        'is_active',
        'sort_order',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'crm_id' => 'integer',
        'priority' => 'integer',
        'sort_order' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];
    
    public function subwayLine(): BelongsTo
    {
        return $this->belongsTo(SubwayLine::class);
    }
    
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
    
    public function blocks(): BelongsToMany
    {
        return $this->belongsToMany(Block::class, 'block_subways')
            ->withPivot(['distance_time', 'distance_type_id', 'distance_type', 'priority'])
            ->withTimestamps();
    }
    
    public function parkings(): BelongsToMany
    {
        return $this->belongsToMany(Parking::class, 'parking_subways')
            ->withPivot(['distance_time', 'distance_type_id', 'priority'])
            ->withTimestamps();
    }
    
    public function commercialBlocks(): BelongsToMany
    {
        return $this->belongsToMany(CommercialBlock::class, 'commercial_block_subways')
            ->withPivot(['distance_time', 'distance_type_id', 'priority'])
            ->withTimestamps();
    }
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    public function scopeByCity($query, $cityId)
    {
        return $query->where('city_id', $cityId);
    }
}

