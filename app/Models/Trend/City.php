<?php

namespace App\Models\Trend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class City extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
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
    
    public function regions(): HasMany
    {
        return $this->hasMany(Region::class);
    }
    
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }
    
    public function subways(): HasMany
    {
        return $this->hasMany(Subway::class);
    }
    
    public function subwayLines(): HasMany
    {
        return $this->hasMany(SubwayLine::class);
    }
    
    public function blocks(): HasMany
    {
        return $this->hasMany(Block::class);
    }
    
    public function parkings(): HasMany
    {
        return $this->hasMany(Parking::class);
    }
    
    public function villages(): HasMany
    {
        return $this->hasMany(Village::class);
    }
    
    public function commercialBlocks(): HasMany
    {
        return $this->hasMany(CommercialBlock::class);
    }
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

