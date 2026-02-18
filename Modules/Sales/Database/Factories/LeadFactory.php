<?php

declare(strict_types=1);

namespace Modules\Sales\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Sales\Entities\Lead;

/**
 * Lead Factory
 *
 * Generates lead data with various statuses, sources, and probabilities.
 */
class LeadFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Lead::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement(['new', 'contacted', 'qualified', 'negotiation', 'won', 'lost']);
        $stage = $this->getStageFromStatus($status);
        $probability = $this->getProbabilityFromStatus($status);

        return [
            'tenant_id' => 1,
            'lead_number' => 'LEAD-'.fake()->unique()->numerify('######'),
            'customer_id' => null,
            'source' => fake()->randomElement([
                'website',
                'referral',
                'trade_show',
                'cold_call',
                'email_campaign',
                'social_media',
                'partner',
                'advertising',
                'webinar',
                'other',
            ]),
            'title' => fake()->catchPhrase(),
            'description' => fake()->paragraph(3),
            'contact_name' => fake()->name(),
            'contact_email' => fake()->unique()->safeEmail(),
            'contact_phone' => fake()->phoneNumber(),
            'company' => fake()->company(),
            'status' => $status,
            'stage' => $stage,
            'probability' => $probability,
            'expected_revenue' => fake()->randomFloat(2, 1000, 500000),
            'expected_close_date' => fake()->dateTimeBetween('now', '+6 months'),
            'assigned_to' => null,
            'tags' => fake()->boolean(50) ? fake()->randomElements(
                ['hot', 'warm', 'cold', 'enterprise', 'smb', 'high-priority', 'follow-up'],
                fake()->numberBetween(1, 3)
            ) : [],
            'notes' => fake()->boolean(60) ? fake()->paragraph() : null,
        ];
    }

    /**
     * Get stage from status.
     */
    private function getStageFromStatus(string $status): string
    {
        return match ($status) {
            'new', 'contacted' => 'lead',
            'qualified' => 'qualified',
            'negotiation', 'proposal' => 'prospect',
            'won', 'converted' => 'customer',
            'lost' => 'lead',
            default => 'lead',
        };
    }

    /**
     * Get probability from status.
     */
    private function getProbabilityFromStatus(string $status): int
    {
        return match ($status) {
            'new' => fake()->numberBetween(10, 20),
            'contacted' => fake()->numberBetween(20, 30),
            'qualified' => fake()->numberBetween(40, 60),
            'negotiation' => fake()->numberBetween(75, 90),
            'won' => 100,
            'lost' => 0,
            default => 10,
        };
    }

    /**
     * Indicate that the lead is new.
     */
    public function newLead(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'new',
            'stage' => 'lead',
            'probability' => fake()->numberBetween(10, 20),
        ]);
    }

    /**
     * Indicate that the lead is qualified.
     */
    public function qualified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'qualified',
            'stage' => 'qualified',
            'probability' => fake()->numberBetween(40, 60),
        ]);
    }

    /**
     * Indicate that the lead is won.
     */
    public function won(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'won',
            'stage' => 'customer',
            'probability' => 100,
            'expected_close_date' => fake()->dateTimeBetween('-3 months', 'now'),
        ]);
    }

    /**
     * Indicate that the lead is lost.
     */
    public function lost(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'lost',
            'stage' => 'lead',
            'probability' => 0,
            'expected_close_date' => fake()->dateTimeBetween('-3 months', 'now'),
        ]);
    }

    /**
     * Indicate that the lead is in negotiation.
     */
    public function negotiation(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'negotiation',
            'stage' => 'prospect',
            'probability' => fake()->numberBetween(75, 90),
            'expected_close_date' => fake()->dateTimeBetween('now', '+1 month'),
        ]);
    }

    /**
     * Indicate that the lead is hot.
     */
    public function hot(): static
    {
        return $this->state(fn (array $attributes) => [
            'probability' => fake()->numberBetween(70, 90),
            'expected_close_date' => fake()->dateTimeBetween('now', '+1 month'),
            'tags' => array_unique(array_merge($attributes['tags'] ?? [], ['hot', 'high-priority'])),
        ]);
    }

    /**
     * Indicate that the lead is from a website.
     */
    public function fromWebsite(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'website',
        ]);
    }

    /**
     * Indicate that the lead is from a referral.
     */
    public function fromReferral(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'referral',
            'probability' => min(($attributes['probability'] ?? 50) + 10, 100),
        ]);
    }

    /**
     * Indicate that the lead is converted to customer.
     */
    public function converted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'converted',
            'stage' => 'customer',
            'probability' => 100,
        ]);
    }
}
