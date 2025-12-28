<?php

namespace Database\Factories;

use App\Models\ParserError;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ParserErrorFactory extends Factory
{
    protected $model = ParserError::class;

    public function definition(): array
    {
        return [
            'error_type' => $this->faker->randomElement(['api', 'parsing', 'validation', 'database']),
            'object_type' => $this->faker->randomElement(['block', 'parking', 'village', 'commercial_block']),
            'source_type' => 'parser',
            'object_class' => 'App\\Models\\Trend\\Block',
            'object_id' => null,
            'external_id' => $this->faker->regexify('[a-f0-9]{24}'),
            'error_code' => $this->faker->numberBetween(400, 500),
            'error_message' => $this->faker->sentence(),
            'error_details' => $this->faker->text(500),
            'context' => [
                'api_url' => $this->faker->url(),
                'request_params' => ['count' => 10, 'offset' => 0],
            ],
            'api_url' => $this->faker->url(),
            'http_status_code' => $this->faker->randomElement([400, 401, 403, 404, 500]),
            'response_body' => $this->faker->text(200),
            'request_method' => 'GET',
            'request_params' => ['count' => 10],
            'is_resolved' => false,
            'resolved_at' => null,
            'resolved_by' => null,
            'resolution_notes' => null,
            'attempts_count' => 1,
            'last_attempt_at' => now(),
            'user_id' => User::factory(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolved_by' => User::factory(),
            'resolution_notes' => 'Ошибка исправлена',
        ]);
    }
}

