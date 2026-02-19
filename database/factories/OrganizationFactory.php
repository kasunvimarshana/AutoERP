<?php

namespace Database\Factories;

use App\Enums\OrganizationStatus;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrganizationFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            'tenant_id' => Tenant::factory(),
            'parent_id' => null,
            'name' => $name,
            'slug' => Str::slug($name).'-'.$this->faker->unique()->numberBetween(1, 9999),
            'type' => 'company',
            'status' => OrganizationStatus::Active,
            'locale' => 'en',
            'timezone' => 'UTC',
            'currency' => 'USD',
            'address' => null,
            'settings' => null,
            'metadata' => null,
            'lft' => 0,
            'rgt' => 0,
            'depth' => 0,
        ];
    }
}
