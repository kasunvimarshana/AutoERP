<?php

namespace Database\Factories;

use App\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TenantFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.$this->faker->unique()->numberBetween(1, 9999),
            'domain' => null,
            'status' => TenantStatus::Active,
            'plan' => 'free',
            'settings' => null,
            'metadata' => null,
            'trial_ends_at' => null,
            'suspended_at' => null,
        ];
    }

    public function trial(): static
    {
        return $this->state(['status' => TenantStatus::Trial, 'trial_ends_at' => now()->addDays(14)]);
    }

    public function suspended(): static
    {
        return $this->state(['status' => TenantStatus::Suspended, 'suspended_at' => now()]);
    }
}
