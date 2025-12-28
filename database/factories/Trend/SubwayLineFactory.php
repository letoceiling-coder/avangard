<?php

namespace Database\Factories\Trend;

use App\Models\Trend\SubwayLine;
use App\Models\Trend\City;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubwayLineFactory extends Factory
{
    protected $model = SubwayLine::class;

    public function definition(): array
    {
        return [
            'city_id' => City::factory(),
            'name' => $this->faker->randomElement(['Сокольническая', 'Замоскворецкая', 'Арбатско-Покровская']) . ' линия',
            'color' => $this->faker->hexColor(),
            'line_number' => $this->faker->numberBetween(1, 15),
            'external_id' => $this->faker->unique()->regexify('[a-f0-9]{24}'),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}

