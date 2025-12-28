<?php

namespace App\Models\Trend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Region extends Model
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
    
    /**
     * Город, к которому относится регион (для обратной совместимости)
     * @deprecated Используйте cities() для получения городов региона
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
    
    /**
     * Города, принадлежащие региону
     */
    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }
    
    public function blocks(): HasMany
    {
        return $this->hasMany(Block::class);
    }
    
    public function parkingsAsDistrict(): HasMany
    {
        return $this->hasMany(Parking::class, 'district_id');
    }
    
    public function commercialBlocksAsDistrict(): HasMany
    {
        return $this->hasMany(CommercialBlock::class, 'district_id');
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

