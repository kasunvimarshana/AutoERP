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
    /**
     * @return array{
     *   party_number_snapshot:?string,
     *   party_code_snapshot:?string,
     *   party_name_snapshot:?string,
     *   party_legal_name_snapshot:?string,
     *   party_email_snapshot:?string,
     *   party_phone_snapshot:?string,
     *   party_tax_registration_snapshot:?string,
     *   currency_code_snapshot:?string,
     *   currency_name_snapshot:?string,
     *   currency_symbol_snapshot:?string
     * }
     */
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

    /**
     * @return array<int, array{
     *   item_code_snapshot:?string,
     *   item_name_snapshot:?string,
     *   uom_code_snapshot:?string,
     *   uom_name_snapshot:?string,
     *   tax_snapshot:?array
     * }>
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
        $party = $query
            ->where('tenant_id', $data->tenantId)
            ->whereKey($data->partyId)
            ->where($this->scopeConstraint($data))
            ->first();

        if (! $party instanceof Customer && ! $party instanceof Supplier) {
            throw new InvalidArgumentException('Invoice party belongs to a different tenant or organization-unit scope.');
        }

        return $party;
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
