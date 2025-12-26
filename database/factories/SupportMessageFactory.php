<?php

namespace Database\Factories;

use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SupportMessage>
 */
class SupportMessageFactory extends Factory
{
    protected $model = SupportMessage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'ticket_id' => SupportTicket::factory(),
            'sender' => fake()->randomElement(['local', 'crm']),
            'message' => fake()->paragraph(),
            'attachments' => null,
            'created_at' => now(),
        ];
    }

    /**
     * Indicate that the message is from local (admin).
     */
    public function fromLocal(): static
    {
        return $this->state(fn (array $attributes) => [
            'sender' => 'local',
        ]);
    }

    /**
     * Indicate that the message is from CRM.
     */
    public function fromCrm(): static
    {
        return $this->state(fn (array $attributes) => [
            'sender' => 'crm',
        ]);
    }

    /**
     * Add attachments to the message.
     */
    public function withAttachments(array $attachments): static
    {
        return $this->state(fn (array $attributes) => [
            'attachments' => $attachments,
        ]);
    }
}

