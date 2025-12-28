<?php

namespace App\Http\Filters;

use Illuminate\Database\Eloquent\Builder;

class CommercialBlockFilter extends AbstractFilter
{
    protected function getCallbacks(): array
    {
        return [
            'city_id' => [$this, 'cityId'],
            'district_id' => [$this, 'districtId'],
            'location_id' => [$this, 'locationId'],
            'builder_id' => [$this, 'builderId'],
            'is_active' => [$this, 'active'],
            'is_new_block' => [$this, 'newBlock'],
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
    
    protected function active(Builder $builder, $value): void
    {
        $builder->where('is_active', filter_var($value, FILTER_VALIDATE_BOOLEAN));
    }
    
    protected function newBlock(Builder $builder, $value): void
    {
        $builder->where('is_new_block', filter_var($value, FILTER_VALIDATE_BOOLEAN));
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
            'name' => $builder->orderBy('name', $direction),
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

