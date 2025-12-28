<?php

namespace App\Http\Filters;

use Illuminate\Database\Eloquent\Builder;

class CommercialPremiseFilter extends AbstractFilter
{
    protected function getCallbacks(): array
    {
        return [
            'commercial_block_id' => [$this, 'commercialBlockId'],
            'city_id' => [$this, 'cityId'],
            'builder_id' => [$this, 'builderId'],
            'district_id' => [$this, 'districtId'],
            'location_id' => [$this, 'locationId'],
            'is_active' => [$this, 'active'],
            'is_booked' => [$this, 'booked'],
            'is_sold' => [$this, 'sold'],
            'min_price' => [$this, 'minPrice'],
            'max_price' => [$this, 'maxPrice'],
            'data_source' => [$this, 'dataSource'],
            'search' => [$this, 'search'],
            'sort' => [$this, 'sort'],
        ];
    }
    
    protected function commercialBlockId(Builder $builder, $value): void
    {
        $builder->where('commercial_block_id', $value);
    }
    
    protected function cityId(Builder $builder, $value): void
    {
        $builder->where('city_id', $value);
    }
    
    protected function builderId(Builder $builder, $value): void
    {
        $builder->where('builder_id', $value);
    }
    
    protected function districtId(Builder $builder, $value): void
    {
        $builder->where('district_id', $value);
    }
    
    protected function locationId(Builder $builder, $value): void
    {
        $builder->where('location_id', $value);
    }
    
    protected function active(Builder $builder, $value): void
    {
        $builder->where('is_active', filter_var($value, FILTER_VALIDATE_BOOLEAN));
    }
    
    protected function booked(Builder $builder, $value): void
    {
        $builder->where('is_booked', filter_var($value, FILTER_VALIDATE_BOOLEAN));
    }
    
    protected function sold(Builder $builder, $value): void
    {
        $builder->where('is_sold', filter_var($value, FILTER_VALIDATE_BOOLEAN));
    }
    
    protected function minPrice(Builder $builder, $value): void
    {
        $builder->where('price', '>=', (int)$value);
    }
    
    protected function maxPrice(Builder $builder, $value): void
    {
        $builder->where('price', '<=', (int)$value);
    }
    
    protected function dataSource(Builder $builder, $value): void
    {
        $builder->where('data_source', $value);
    }
    
    protected function search(Builder $builder, $value): void
    {
        $builder->where(function($query) use ($value) {
            $query->where('name', 'like', "%{$value}%")
                  ->orWhere('address', 'like', "%{$value}%");
        });
    }
    
    protected function sort(Builder $builder, $value): void
    {
        $direction = $this->getQueryParam('sort_order', 'asc');
        $direction = in_array(strtolower($direction), ['asc', 'desc']) ? strtolower($direction) : 'asc';
        
        switch ($value) {
            case 'price':
                $builder->orderBy('price', $direction);
                break;
            case 'area':
                $builder->orderBy('area', $direction);
                break;
            case 'name':
                $builder->orderBy('name', $direction);
                break;
            default:
                $builder->orderBy('id', 'desc');
        }
    }
    
    protected function before(Builder $builder): void
    {
        if (!$this->getQueryParam('include_inactive')) {
            $builder->where('is_active', true);
        }
    }
}

