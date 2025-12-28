<?php

namespace Database\Factories\Trend;

use App\Models\Trend\Parking;
use App\Models\Trend\City;
use App\Models\Trend\Block;
use Illuminate\Database\Eloquent\Factories\Factory;

class ParkingFactory extends Factory
{
    protected $model = Parking::class;

    public function definition(): array
    {
        return [
            'city_id' => City::factory(),
            'block_id' => Block::factory(),
            'external_id' => $this->faker->unique()->regexify('[a-f0-9]{24}'),
            'block_guid' => $this->faker->slug(),
            'block_name' => 'ЖК ' . $this->faker->words(2, true),
            'number' => $this->faker->bothify('?##'),
            'floor' => $this->faker->numberBetween(-3, 5),
            'area' => $this->faker->randomFloat(2, 10, 30),
            'latitude' => $this->faker->latitude(55.0, 56.0),
            'longitude' => $this->faker->longitude(37.0, 38.0),
            'parking_type' => $this->faker->randomElement(['Подземный', 'Наземный']),
            'place_type' => $this->faker->randomElement(['Стандартное', 'Увеличенное']),
            'property_type' => 'new',
            'status' => $this->faker->randomElement(['available', 'booked']),
            'status_label' => 'Свободно',
            'price' => $this->faker->numberBetween(1000000, 5000000), // В копейках
            'reward_label' => '0.6-0.8%',
            'deadline' => $this->faker->randomElement(['2025 Q4', '2026 Q1']),
            'deadline_date' => $this->faker->dateTimeBetween('+6 months', '+1 year'),
            'deadline_over_check' => false,
            'data_source' => $this->faker->randomElement(['parser', 'manual']),
            'metadata' => null,
        ];
    }
}

