<?php

namespace Database\Factories\Finance;

use App\Models\Tenant;
use App\Modules\Finance\Enums\AccountType;
use App\Modules\Finance\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Finance\Models\Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'code' => fake()->unique()->numerify('####'),
            'name' => fake()->words(3, true),
            'type' => fake()->randomElement(AccountType::cases())->value,
            'parent_id' => null,
            'currency_id' => null,
            'opening_balance' => fake()->randomFloat(2, 0, 100000),
            'current_balance' => fake()->randomFloat(2, 0, 100000),
            'is_active' => true,
            'description' => fake()->optional()->sentence(),
        ];
    }

    public function asset(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AccountType::ASSET->value,
        ]);
    }

    public function liability(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AccountType::LIABILITY->value,
        ]);
    }

    public function equity(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AccountType::EQUITY->value,
        ]);
    }

    public function revenue(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AccountType::REVENUE->value,
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AccountType::EXPENSE->value,
        ]);
    }

    public function withParent(Account $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent->id,
            'type' => $parent->type->value,
            'tenant_id' => $parent->tenant_id,
        ]);
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }
}
