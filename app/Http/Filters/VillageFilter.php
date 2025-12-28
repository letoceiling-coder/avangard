<?php

namespace App\Http\Filters;

use Illuminate\Database\Eloquent\Builder;

class VillageFilter extends AbstractFilter
{
    protected function getCallbacks(): array
    {
        return [
            'city_id' => [$this, 'cityId'],
            'builder_id' => [$this, 'builderId'],
            'is_active' => [$this, 'active'],
            'is_new_village' => [$this, 'newVillage'],
            'data_source' => [$this, 'dataSource'],
            'search' => [$this, 'search'],
            'sort' => [$this, 'sort'],
        ];
    }
    
    protected function cityId(Builder $builder, $value): void
    {
        $builder->where('city_id', $value);
    }
    
    protected function builderId(Builder $builder, $value): void
    {
        $builder->where('builder_id', $value);
    }
    
    protected function active(Builder $builder, $value): void
    {
        $builder->where('is_active', filter_var($value, FILTER_VALIDATE_BOOLEAN));
    }
    
    protected function newVillage(Builder $builder, $value): void
    {
        $builder->where('is_new_village', filter_var($value, FILTER_VALIDATE_BOOLEAN));
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

