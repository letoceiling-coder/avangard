<?php

namespace Database\Factories\Trend;

use App\Models\Trend\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;

class BuilderFactory extends Factory
{
    protected $model = Builder::class;

    public function definition(): array
    {
        return [
            'guid' => $this->faker->unique()->slug(),
            'name' => $this->faker->company() . ' Development',
            'crm_id' => $this->faker->unique()->numberBetween(1000, 10000),
            'external_id' => $this->faker->unique()->regexify('[a-f0-9]{24}'),
            'description' => $this->faker->text(200),
            'website' => $this->faker->url(),
            'email' => $this->faker->email(),
            'phone' => $this->faker->phoneNumber(),
            'is_active' => true,
            'is_exclusive' => $this->faker->boolean(20),
            'sort_order' => 0,
        ];
    }
}

