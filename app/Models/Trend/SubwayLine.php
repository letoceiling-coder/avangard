<?php

namespace App\Models\Trend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubwayLine extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'city_id',
        'name',
        'color',
        'line_number',
        'external_id',
        'is_active',
        'sort_order',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'line_number' => 'integer',
        'sort_order' => 'integer',
    ];
    
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
    
    public function subways(): HasMany
    {
        return $this->hasMany(Subway::class);
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

