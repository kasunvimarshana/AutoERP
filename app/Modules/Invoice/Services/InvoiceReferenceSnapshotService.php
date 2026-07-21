<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Modules\Customer\Models\Customer;
use Modules\Invoice\Constants\InvoiceTaxMetadata;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\Enums\InvoicePartyType;
use Modules\Item\Models\Item;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Supplier\Models\Supplier;
use Modules\UOM\Models\UnitOfMeasureModel;

final class InvoiceReferenceSnapshotService
{
    /** @return array<string, ?string> */
    public function header(CreateInvoiceData $data): array
    {
        $party = $this->party($data);
        $currency = $data->currencyId === null
            ? null
            : CurrencyModel::query()->find($data->currencyId);

        if ($data->currencyId !== null && ! $currency instanceof CurrencyModel) {
            throw new InvalidArgumentException('Invoice currency does not exist.');
        }

        return [
            'party_number_snapshot' => $party === null
                ? null
                : $this->nullableString($party->{$this->partyNumberColumn($data->partyType)}),
            'party_code_snapshot' => $party === null ? null : $this->nullableString($party->code),
            'party_name_snapshot' => $party === null
                ? null
                : $this->nullableString($party->display_name)
                    ?? $this->nullableString($party->legal_name)
                    ?? $this->nullableString($party->name),
            'party_legal_name_snapshot' => $party === null ? null : $this->nullableString($party->legal_name),
            'party_email_snapshot' => $party === null ? null : $this->nullableString($party->email),
            'party_phone_snapshot' => $party === null
                ? null
                : $this->nullableString($party->phone) ?? $this->nullableString($party->mobile),
            'party_tax_registration_snapshot' => $party === null
                ? null
                : $this->nullableString($party->tax_registration_number)
                    ?? $this->nullableString($party->vat_number),
            'currency_code_snapshot' => $currency instanceof CurrencyModel
                ? $this->nullableString($currency->code)
                : null,
            'currency_name_snapshot' => $currency instanceof CurrencyModel
                ? $this->nullableString($currency->name)
                : null,
            'currency_symbol_snapshot' => $currency instanceof CurrencyModel
                ? $this->nullableString($currency->symbol)
                : null,
        ];
    }

    /** @return array{name:string,tin:?string,vat_registration_number:?string,svat_registration_number:?string,address:?string,phone:?string,email:?string} */
    public function documentParty(CreateInvoiceData $data): array
    {
        $party = $this->party($data);
        if (! $party instanceof Customer && ! $party instanceof Supplier) {
            throw new InvalidArgumentException('Invoice document party does not exist.');
        }

        return [
            'name' => $this->nullableString($party->legal_name)
                ?? $this->nullableString($party->display_name)
                ?? $this->nullableString($party->name)
                ?? 'Counterparty',
            'tin' => $this->nullableString($party->tax_registration_number),
            'vat_registration_number' => $this->nullableString($party->vat_number),
            'svat_registration_number' => $this->nullableString($party->svat_number),
            'address' => $this->partyAddress($party, $data->organizationUnitId),
            'phone' => $this->nullableString($party->phone) ?? $this->nullableString($party->mobile),
            'email' => $this->nullableString($party->email),
        ];
    }

    /**
     * @return array<int, array{item_code_snapshot:?string,item_name_snapshot:?string,uom_code_snapshot:?string,uom_name_snapshot:?string,tax_snapshot:?array}>
     */
    public function lines(CreateInvoiceData $data): array
    {
        $itemIds = [];
        $uomIds = [];
        foreach ($data->lines as $line) {
            if (! $line instanceof InvoiceLineData) {
                throw new InvalidArgumentException('Invoice lines must be InvoiceLineData instances.');
            }
            if ($line->itemId !== null) {
                $itemIds[] = $line->itemId;
            }
            if ($line->uomId !== null) {
                $uomIds[] = $line->uomId;
            }
        }

        $items = Item::withTrashed()
            ->where('tenant_id', $data->tenantId)
            ->whereIn('id', array_values(array_unique($itemIds)))
            ->where($this->scopeConstraint($data))
            ->get()
            ->keyBy(fn (Item $item): int => (int) $item->getKey());
        $uoms = UnitOfMeasureModel::withTrashed()
            ->where('tenant_id', $data->tenantId)
            ->whereIn('id', array_values(array_unique($uomIds)))
            ->where($this->scopeConstraint($data))
            ->get()
            ->keyBy(fn (UnitOfMeasureModel $uom): int => (int) $uom->getKey());

        $snapshots = [];
        foreach ($data->lines as $line) {
            $item = $line->itemId === null ? null : $items->get($line->itemId);
            $uom = $line->uomId === null ? null : $uoms->get($line->uomId);
            if ($line->itemId !== null && ! $item instanceof Item) {
                throw new InvalidArgumentException('Invoice item belongs to a different tenant or organization-unit scope.');
            }
            if ($line->uomId !== null && ! $uom instanceof UnitOfMeasureModel) {
                throw new InvalidArgumentException('Invoice UOM belongs to a different tenant or organization-unit scope.');
            }

            $metadata = is_array($line->metadata) ? $line->metadata : [];
            $taxes = $metadata[InvoiceTaxMetadata::TAXES] ?? null;
            $snapshots[$line->lineNumber] = [
                'item_code_snapshot' => $item instanceof Item ? $this->nullableString($item->code) : null,
                'item_name_snapshot' => $item instanceof Item ? $this->nullableString($item->name) : null,
                'uom_code_snapshot' => $uom instanceof UnitOfMeasureModel ? $this->nullableString($uom->code) : null,
                'uom_name_snapshot' => $uom instanceof UnitOfMeasureModel ? $this->nullableString($uom->name) : null,
                'tax_snapshot' => is_array($taxes) ? array_values($taxes) : null,
            ];
        }

        return $snapshots;
    }

    private function party(CreateInvoiceData $data): Customer|Supplier|null
    {
        if ($data->partyId === null && $data->partyType === null) {
            return null;
        }
        if ($data->partyId === null || $data->partyType === null) {
            throw new InvalidArgumentException('Invoice party type and identifier must be supplied together.');
        }

        $query = match ($data->partyType) {
            InvoicePartyType::Customer->value => Customer::withTrashed(),
            InvoicePartyType::Supplier->value => Supplier::withTrashed(),
            default => throw new InvalidArgumentException('Invoice party type is not supported.'),
        };
        $organizationUnitId = $data->organizationUnitId;
        $party = $query
            ->with(['addresses' => static function (Builder $addresses) use ($organizationUnitId): void {
                $addresses->where('is_active', true)->whereNull('deleted_at');
                $organizationUnitId === null
                    ? $addresses->whereNull('organization_unit_id')
                    : $addresses->where(static fn (Builder $scope): Builder => $scope
                        ->whereNull('organization_unit_id')
                        ->orWhere('organization_unit_id', $organizationUnitId));
            }])
            ->where('tenant_id', $data->tenantId)
            ->whereKey($data->partyId)
            ->where($this->scopeConstraint($data))
            ->first();

        if (! $party instanceof Customer && ! $party instanceof Supplier) {
            throw new InvalidArgumentException('Invoice party belongs to a different tenant or organization-unit scope.');
        }

        return $party;
    }

    private function partyAddress(Customer|Supplier $party, ?int $organizationUnitId): ?string
    {
        $address = $party->addresses
            ->sortBy(function ($address) use ($organizationUnitId): string {
                $addressOrganizationUnitId = is_numeric($address->organization_unit_id)
                    ? (int) $address->organization_unit_id
                    : null;
                $scopeRank = $organizationUnitId === null || $addressOrganizationUnitId === $organizationUnitId
                    ? 0
                    : 1;
                $type = $address->address_type instanceof \BackedEnum
                    ? (string) $address->address_type->value
                    : (string) $address->address_type;
                $primary = (bool) $address->is_primary;
                $typeRank = match (true) {
                    $primary && $type === 'registered' => 0,
                    $primary && $type === 'billing' => 1,
                    $primary => 2,
                    $type === 'registered' => 3,
                    $type === 'billing' => 4,
                    default => 5,
                };

                return sprintf('%02d-%02d-%020d', $scopeRank, $typeRank, (int) $address->getKey());
            })
            ->first();

        if ($address === null) {
            return null;
        }

        $parts = array_filter([
            $this->nullableString($address->address_line_1),
            $this->nullableString($address->address_line_2),
            $this->nullableString($address->city),
            $this->nullableString($address->state),
            $this->nullableString($address->postal_code),
            $this->nullableString($address->country),
        ]);

        return $parts === [] ? null : implode(', ', $parts);
    }

    private function partyNumberColumn(?string $partyType): string
    {
        return match ($partyType) {
            InvoicePartyType::Customer->value => 'customer_number',
            InvoicePartyType::Supplier->value => 'supplier_number',
            default => throw new InvalidArgumentException('Invoice party type is not supported.'),
        };
    }

    private function scopeConstraint(CreateInvoiceData $data): callable
    {
        return static function (Builder $query) use ($data): void {
            if ($data->organizationUnitId === null) {
                $query->whereNull('organization_unit_id');

                return;
            }

            $query->where(static fn (Builder $scope): Builder => $scope
                ->whereNull('organization_unit_id')
                ->orWhere('organization_unit_id', $data->organizationUnitId));
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
