<?php

namespace App\Http\Filters;

use Illuminate\Database\Eloquent\Builder;

class ParkingFilter extends AbstractFilter
{
    protected function getCallbacks(): array
    {
        return [
            'city_id' => [$this, 'cityId'],
            'district_id' => [$this, 'districtId'],
            'location_id' => [$this, 'locationId'],
            'builder_id' => [$this, 'builderId'],
            'block_id' => [$this, 'blockId'],
            'status' => [$this, 'status'],
            'is_active' => [$this, 'active'],
            'min_price' => [$this, 'minPrice'],
            'max_price' => [$this, 'maxPrice'],
            'parking_type' => [$this, 'parkingType'],
            'place_type' => [$this, 'placeType'],
            'data_source' => [$this, 'dataSource'],
            'search' => [$this, 'search'],
            'subway_id' => [$this, 'subway'],
            'sort' => [$this, 'sort'],
        ];
    }
    
    protected function cityId(Builder $builder, $value): void
    {
        $builder->where('city_id', $value);
    }
    
    protected function districtId(Builder $builder, $value): void
    {
        $builder->where('district_id', $value);
    }
    
    protected function locationId(Builder $builder, $value): void
    {
        $builder->where('location_id', $value);
    }
    
    protected function builderId(Builder $builder, $value): void
    {
        $builder->where('builder_id', $value);
    }
    
    protected function blockId(Builder $builder, $value): void
    {
        $builder->where('block_id', $value);
    }
    
    protected function status(Builder $builder, $value): void
    {
        $builder->where('status', $value);
    }
    
    protected function active(Builder $builder, $value): void
    {
        $builder->where('is_active', filter_var($value, FILTER_VALIDATE_BOOLEAN));
    }
    
    protected function minPrice(Builder $builder, $value): void
    {
        $priceInKopecks = is_numeric($value) ? (int)($value * 100) : $value;
        $builder->where('price', '>=', $priceInKopecks);
    }
    
    protected function maxPrice(Builder $builder, $value): void
    {
        $priceInKopecks = is_numeric($value) ? (int)($value * 100) : $value;
        $builder->where('price', '<=', $priceInKopecks);
    }
    
    protected function parkingType(Builder $builder, $value): void
    {
        $builder->where('parking_type', $value);
    }
    
    protected function placeType(Builder $builder, $value): void
    {
        $builder->where('place_type', $value);
    }
    
    protected function dataSource(Builder $builder, $value): void
    {
        $builder->where('data_source', $value);
    }
    
    protected function search(Builder $builder, $value): void
    {
        $builder->where(function($query) use ($value) {
            $query->where('block_name', 'like', "%{$value}%")
                ->orWhere('number', 'like', "%{$value}%");
        });
    }
    
    protected function subway(Builder $builder, $value): void
    {
        $builder->whereHas('subways', function($query) use ($value) {
            $query->where('subways.id', $value);
        });
    }
    
    protected function sort(Builder $builder, $value): void
    {
        $direction = $this->getQueryParam('sort_direction', 'asc');
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';
        
        match($value) {
            'price' => $builder->orderBy('price', $direction),
            'name' => $builder->orderBy('block_name', $direction),
            'created' => $builder->orderBy('created_at', $direction),
            default => $builder->orderBy('created_at', 'desc'),
        };
    }
    
    protected function before(Builder $builder): void
    {
        if (!$this->getQueryParam('include_inactive')) {
            $builder->where('is_active', true);
        }
    }
}

