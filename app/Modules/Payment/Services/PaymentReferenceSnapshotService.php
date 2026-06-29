<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Modules\Customer\Models\Customer;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Supplier\Models\Supplier;

final class PaymentReferenceSnapshotService
{
    public function header(CreatePaymentData $data): array
    {
        $party = $this->party($data);
        $currency = $data->currencyId === null ? null : CurrencyModel::query()->find($data->currencyId);
        if ($data->currencyId !== null && ! $currency instanceof CurrencyModel) {
            throw new InvalidArgumentException('Payment currency does not exist.');
        }

        return [
            'party_number_snapshot' => $party === null ? null : $this->partyNumber($party, $data->partyType),
            'party_code_snapshot' => $party === null ? null : $this->nullableString($party->code),
            'party_name_snapshot' => $party === null ? null : (
                $this->nullableString($party->display_name)
                ?? $this->nullableString($party->legal_name)
                ?? $this->nullableString($party->name)
            ),
            'party_email_snapshot' => $party === null ? null : $this->nullableString($party->email),
            'party_phone_snapshot' => $party === null ? null : (
                $this->nullableString($party->phone) ?? $this->nullableString($party->mobile)
            ),
            'currency_code_snapshot' => $currency instanceof CurrencyModel ? $this->nullableString($currency->code) : null,
            'currency_name_snapshot' => $currency instanceof CurrencyModel ? $this->nullableString($currency->name) : null,
            'currency_symbol_snapshot' => $currency instanceof CurrencyModel ? $this->nullableString($currency->symbol) : null,
        ];
    }

    private function party(CreatePaymentData $data): Customer|Supplier|null
    {
        if ($data->partyType === null && $data->partyId === null) {
            return null;
        }
        if ($data->partyType === null || $data->partyId === null) {
            throw new InvalidArgumentException('Payment party type and identifier must be supplied together.');
        }

        $query = match ($data->partyType) {
            'customer' => Customer::withTrashed(),
            'supplier' => Supplier::withTrashed(),
            default => throw new InvalidArgumentException('Unsupported payment party type.'),
        };
        $party = $query
            ->where('tenant_id', $data->tenantId)
            ->whereKey($data->partyId)
            ->where($this->scopeConstraint($data))
            ->first();

        if (! $party instanceof Customer && ! $party instanceof Supplier) {
            throw new InvalidArgumentException('Payment party belongs to a different tenant or organization-unit scope.');
        }

        return $party;
    }

    private function scopeConstraint(CreatePaymentData $data): callable
    {
        return static function (Builder $query) use ($data): void {
            $query->where(function (Builder $scope) use ($data): void {
                $scope->whereNull('organization_unit_id');
                if ($data->organizationUnitId !== null) {
                    $scope->orWhere('organization_unit_id', $data->organizationUnitId);
                }
            });
        };
    }

    private function partyNumber(Customer|Supplier $party, ?string $partyType): ?string
    {
        return match ($partyType) {
            'customer' => $this->nullableString($party->customer_number),
            'supplier' => $this->nullableString($party->supplier_number),
            default => null,
        };
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
