<?php

namespace Database\Factories;

use App\Enums\TicketBillingMode;
use App\Enums\TicketBillingStatus;
use App\Enums\TicketCategory;
use App\Enums\TicketChannel;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\SupportTicket;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SupportTicket>
 */
class SupportTicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => fn () => (int) SupportTicket::query()->max('number') + 1,
            'tenant_id' => null,
            'channel' => TicketChannel::PublicWeb(),
            'requester_name' => fake()->name(),
            'requester_email' => fake()->unique()->safeEmail(),
            'access_token' => Str::random(48),
            'subject' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'category' => TicketCategory::Question(),
            'priority' => TicketPriority::Normal(),
            'status' => TicketStatus::Open(),
            'covered_by_plan' => false,
            'billing_mode' => TicketBillingMode::Hourly(),
            'billing_status' => TicketBillingStatus::Pending(),
            'last_activity_at' => now(),
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn () => [
            'status' => TicketStatus::Resolved(),
            'resolved_at' => now(),
        ]);
    }
}
