<?php

namespace Database\Factories\Trend;

use App\Models\Trend\Subway;
use App\Models\Trend\City;
use App\Models\Trend\SubwayLine;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubwayFactory extends Factory
{
    protected $model = Subway::class;

    public function definition(): array
    {
        return [
            'city_id' => City::factory(),
            'subway_line_id' => SubwayLine::factory(),
            'guid' => $this->faker->unique()->slug(),
            'name' => $this->faker->words(2, true) . ' (метро)',
            'crm_id' => $this->faker->unique()->numberBetween(1, 500),
            'external_id' => $this->faker->unique()->regexify('[a-f0-9]{24}'),
            'latitude' => $this->faker->latitude(55.0, 56.0),
            'longitude' => $this->faker->longitude(37.0, 38.0),
            'priority' => 500,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}

