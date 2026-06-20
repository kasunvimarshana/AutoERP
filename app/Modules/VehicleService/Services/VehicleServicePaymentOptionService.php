<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\FinanceAccount;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Models\PaymentMethod;
use Modules\Payment\Services\PaymentMethodService;
use Modules\VehicleService\Models\VehicleServiceJob;

final class VehicleServicePaymentOptionService
{
    public function __construct(private readonly PaymentMethodService $methods) {}

    /** @return array{methods: list<array<string, mixed>>, bank_accounts: list<array<string, mixed>>} */
    public function options(VehicleServiceJob $job): array
    {
        $methods = $this->methods
            ->effectiveActiveForDirection(
                (int) $job->tenant_id,
                $job->organization_unit_id,
                PaymentDirection::Inbound,
            )
            ->map(fn (PaymentMethod $method): array => [
                'id' => (int) $method->getKey(),
                'code' => (string) $method->code,
                'name' => (string) $method->name,
                'method_type' => $method->method_type instanceof \BackedEnum
                    ? $method->method_type->value
                    : (string) $method->method_type,
                'requires_reference' => (bool) $method->requires_reference,
                'requires_bank_account' => (bool) $method->requires_bank_account,
            ])
            ->all();

        $bankAccounts = FinanceAccount::query()
            ->where('tenant_id', $job->tenant_id)
            ->where(function (Builder $query) use ($job): void {
                $query->whereNull('organization_unit_id');
                if ($job->organization_unit_id !== null) {
                    $query->orWhere('organization_unit_id', $job->organization_unit_id);
                }
            })
            ->where('is_active', true)
            ->where('is_posting_account', true)
            ->where('is_bank_account', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn (FinanceAccount $account): array => [
                'id' => (int) $account->getKey(),
                'code' => (string) $account->code,
                'name' => (string) $account->name,
            ])
            ->all();

        return [
            'methods' => $methods,
            'bank_accounts' => $bankAccounts,
        ];
    }
}
