<?php

namespace Database\Factories;

use App\Modules\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = $this->faker->company();
        return [
            'name'      => $name,
            'slug'      => Str::slug($name) . '-' . Str::random(4),
            'domain'    => null,
            'is_active' => true,
            'plan'      => $this->faker->randomElement(['free', 'basic', 'pro']),
            'settings'  => [],
            'metadata'  => [],
        ];
    }
}
