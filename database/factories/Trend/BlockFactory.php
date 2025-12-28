<?php

namespace Database\Factories\Trend;

use App\Models\Trend\Block;
use App\Models\Trend\City;
use App\Models\Trend\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;

class BlockFactory extends Factory
{
    protected $model = Block::class;

    public function definition(): array
    {
        return [
            'city_id' => City::factory(),
            'builder_id' => Builder::factory(),
            'guid' => $this->faker->unique()->slug(),
            'name' => 'ЖК ' . $this->faker->words(2, true),
            'address' => $this->faker->address(),
            'crm_id' => $this->faker->unique()->numberBetween(10000, 99999),
            'external_id' => $this->faker->unique()->regexify('[a-f0-9]{24}'),
            'latitude' => $this->faker->latitude(55.0, 56.0),
            'longitude' => $this->faker->longitude(37.0, 38.0),
            'status' => 1,
            'is_suite' => $this->faker->boolean(10),
            'is_exclusive' => $this->faker->boolean(20),
            'is_marked' => false,
            'is_active' => true,
            'min_price' => $this->faker->numberBetween(5000000, 10000000), // В копейках
            'max_price' => $this->faker->numberBetween(10000000, 20000000),
            'apartments_count' => $this->faker->numberBetween(10, 500),
            'view_apartments_count' => $this->faker->numberBetween(0, 100),
            'exclusive_apartments_count' => $this->faker->numberBetween(0, 50),
            'deadline' => $this->faker->randomElement(['2025 Q4', '2026 Q1', '2026 Q2']),
            'deadline_date' => $this->faker->dateTimeBetween('+6 months', '+2 years'),
            'deadline_over_check' => false,
            'finishing' => $this->faker->randomElement(['Без отделки', 'Чистовая', 'Предчистовая']),
            'data_source' => $this->faker->randomElement(['parser', 'manual', 'feed']),
            'metadata' => null,
            'advantages' => null,
            'payment_types' => null,
            'contract_types' => null,
        ];
    }
}

