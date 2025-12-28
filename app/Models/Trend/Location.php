<?php

namespace App\Models\Trend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'city_id',
        'guid',
        'name',
        'crm_id',
        'external_id',
        'is_active',
        'sort_order',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'crm_id' => 'integer',
        'sort_order' => 'integer',
    ];
    
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
    
    public function blocks(): HasMany
    {
        return $this->hasMany(Block::class);
    }
    
    public function parkings(): HasMany
    {
        return $this->hasMany(Parking::class);
    }
    
    public function commercialBlocks(): HasMany
    {
        return $this->hasMany(CommercialBlock::class);
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

