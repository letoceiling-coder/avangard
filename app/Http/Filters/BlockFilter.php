<?php

namespace App\Http\Filters;

use Illuminate\Database\Eloquent\Builder;

class BlockFilter extends AbstractFilter
{
    protected function getCallbacks(): array
    {
        return [
            'city_id' => [$this, 'cityId'],
            'region_id' => [$this, 'regionId'],
            'location_id' => [$this, 'locationId'],
            'builder_id' => [$this, 'builderId'],
            'is_exclusive' => [$this, 'exclusive'],
            'is_active' => [$this, 'active'],
            'min_price' => [$this, 'minPrice'],
            'max_price' => [$this, 'maxPrice'],
            'deadline' => [$this, 'deadline'],
            'finishing' => [$this, 'finishing'],
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
    
    protected function regionId(Builder $builder, $value): void
    {
        $builder->where('region_id', $value);
    }
    
    protected function locationId(Builder $builder, $value): void
    {
        $builder->where('location_id', $value);
    }
    
    protected function builderId(Builder $builder, $value): void
    {
        $builder->where('builder_id', $value);
    }
    
    protected function exclusive(Builder $builder, $value): void
    {
        $builder->where('is_exclusive', filter_var($value, FILTER_VALIDATE_BOOLEAN));
    }
    
    protected function active(Builder $builder, $value): void
    {
        $builder->where('is_active', filter_var($value, FILTER_VALIDATE_BOOLEAN));
    }
    
    protected function minPrice(Builder $builder, $value): void
    {
        // Конвертируем рубли в копейки
        $priceInKopecks = is_numeric($value) ? (int)($value * 100) : $value;
        $builder->where('min_price', '>=', $priceInKopecks);
    }
    
    protected function maxPrice(Builder $builder, $value): void
    {
        // Конвертируем рубли в копейки
        $priceInKopecks = is_numeric($value) ? (int)($value * 100) : $value;
        $builder->where('max_price', '<=', $priceInKopecks);
    }
    
    protected function deadline(Builder $builder, $value): void
    {
        $builder->where('deadline', 'like', "%{$value}%");
    }
    
    protected function finishing(Builder $builder, $value): void
    {
        $builder->where('finishing', $value);
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
            
            // Если поддержка полнотекстового поиска
            if (method_exists($query, 'whereFullText')) {
                $query->orWhereFullText(['name', 'address'], $value);
            }
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
            'price' => $builder->orderBy('min_price', $direction),
            'name' => $builder->orderBy('name', $direction),
            'deadline' => $builder->orderBy('deadline_date', $direction),
            'created' => $builder->orderBy('created_at', $direction),
            default => $builder->orderBy('created_at', 'desc'),
        };
    }
    
    protected function before(Builder $builder): void
    {
        // По умолчанию показываем только активные, если не указано иначе
        if (!$this->getQueryParam('include_inactive')) {
            $builder->where('is_active', true);
        }
    }
}

