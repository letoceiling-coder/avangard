<?php

namespace App\Models\Trend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Builder extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'guid',
        'name',
        'crm_id',
        'external_id',
        'description',
        'website',
        'email',
        'phone',
        'is_active',
        'is_exclusive',
        'sort_order',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'is_exclusive' => 'boolean',
        'crm_id' => 'integer',
        'sort_order' => 'integer',
    ];
    
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
    
    public function scopeExclusive($query)
    {
        return $query->where('is_exclusive', true);
    }
}

