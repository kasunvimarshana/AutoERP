<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'keycloak_id' => (string) Str::uuid(),
            'email'       => $this->faker->unique()->safeEmail(),
            'first_name'  => $this->faker->firstName(),
            'last_name'   => $this->faker->lastName(),
            'username'    => $this->faker->unique()->userName(),
            'roles'       => ['viewer'],
            'is_active'   => true,
            'last_login_at' => null,
            'preferences' => [],
            'avatar_url'  => null,
            'phone'       => $this->faker->optional()->phoneNumber(),
            'department'  => $this->faker->optional()->randomElement([
                'Engineering', 'Finance', 'HR', 'Operations', 'Sales',
            ]),
        ];
    }

    public function active(): static
    {
        return $this->state(['is_active' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function admin(): static
    {
        return $this->state(['roles' => ['admin']]);
    }

    public function withRoles(string ...$roles): static
    {
        return $this->state(['roles' => array_values($roles)]);
    }

    public function withoutKeycloak(): static
    {
        return $this->state(['keycloak_id' => null]);
    }
}
