<?php

namespace Database\Factories\Trend;

use App\Models\Trend\City;
use Illuminate\Database\Eloquent\Factories\Factory;

class CityFactory extends Factory
{
    protected $model = City::class;

    public function definition(): array
    {
        return [
            'guid' => $this->faker->unique()->slug(),
            'name' => $this->faker->city(),
            'crm_id' => $this->faker->unique()->numberBetween(1, 1000),
            'external_id' => $this->faker->unique()->regexify('[a-f0-9]{24}'),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}

