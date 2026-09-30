<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\OrganizationUnit\Contracts\OrganizationUnitLegalProfileReaderInterface;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderLine;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Models\SupplierAddress;
use Modules\Supplier\Models\SupplierContact;

final class PurchaseOrderPrintService
{
    public const PDF_PAPER_SIZE = 'A4';

    public const PDF_ORIENTATION = 'portrait';

    private const MONEY_SCALE = 2;

    private const QUANTITY_SCALE = 3;

    public function __construct(
        private readonly OrganizationUnitLegalProfileReaderInterface $organizationProfiles,
    ) {}

    /** @return Builder<PurchaseOrder> */
    public function scopedQuery(int $tenantId, ?int $organizationUnitId): Builder
    {
        $query = PurchaseOrder::query()
            ->with([
                'organizationUnit',
                'supplier.addresses',
                'supplier.contacts',
                'warehouse',
                'warehouseLocation',
                'currency',
                'approvedBy',
                'lines' => static fn ($query) => $query
                    ->with(['item', 'variant', 'uom'])
                    ->orderBy('line_number'),
            ])
            ->where('tenant_id', $tenantId);

        return $organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $organizationUnitId);
    }

    public function findScoped(int $orderId, int $tenantId, ?int $organizationUnitId): ?PurchaseOrder
    {
        return $this->scopedQuery($tenantId, $organizationUnitId)->find($orderId);
    }

    /** @return array<string, mixed> */
    public function viewData(PurchaseOrder $order): array
    {
        $order->loadMissing([
            'organizationUnit',
            'supplier.addresses',
            'supplier.contacts',
            'warehouse',
            'warehouseLocation',
            'currency',
            'approvedBy',
            'lines.item',
            'lines.variant',
            'lines.uom',
        ]);

        $currency = $this->currency($order);
        $status = $this->enumValue($order->status) ?? PurchaseOrderStatus::Draft->value;

        return [
            'document' => [
                'number' => $this->nullableString($order->purchase_order_number) ?? 'Unnumbered purchase order',
                'order_date' => $this->dateString($order->purchase_order_date),
                'expected_delivery_date' => $this->dateString($order->expected_delivery_date),
                'status' => $status,
                'status_label' => $this->label($status),
                'is_draft' => $status === PurchaseOrderStatus::Draft->value,
                'organization' => $this->organization($order),
                'supplier' => $this->supplier($order->supplier),
                'delivery' => [
                    'warehouse' => $this->relationLabel($order->warehouse),
                    'location' => $this->relationLabel($order->warehouseLocation),
                ],
                'currency' => $currency,
                'exchange_rate' => $this->formatDecimal($order->exchange_rate, 6),
                'notes' => $this->nullableString($order->notes),
                'approved_by' => $this->nullableString($order->approvedBy?->name)
                    ?? $this->nullableString($order->approvedBy?->full_name)
                    ?? $this->nullableString($order->approvedBy?->email),
                'approved_at' => $this->dateTimeString($order->approved_at),
                'lines' => $this->lines($order->lines, $currency),
                'amounts' => [
                    'subtotal' => $this->money($order->subtotal, $currency),
                    'discount_total' => $this->money($order->discount_total, $currency),
                    'tax_total' => $this->money($order->tax_total, $currency),
                    'charge_total' => $this->money($order->charge_total, $currency),
                    'adjustment_total' => $this->money($order->adjustment_total, $currency),
                    'grand_total' => $this->money($order->grand_total, $currency),
                ],
            ],
        ];
    }

    public function filename(PurchaseOrder $order): string
    {
        $number = $this->nullableString($order->purchase_order_number) ?? (string) $order->getKey();
        $safeNumber = trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $number), '-');

        return 'purchase-order-'.($safeNumber === '' ? (string) $order->getKey() : $safeNumber).'.pdf';
    }

    /** @param Collection<int, PurchaseOrderLine> $lines @return list<array<string, mixed>> */
    private function lines(Collection $lines, array $currency): array
    {
        return $lines->sortBy('line_number')->values()->map(function (PurchaseOrderLine $line) use ($currency): array {
            $itemName = $this->nullableString($line->item?->name);
            $itemCode = $this->nullableString($line->item?->code);
            $variantName = $this->nullableString($line->variant?->name);
            $description = $this->nullableString($line->description) ?? $itemName ?? $itemCode ?? 'Item';

            return [
                'line_number' => (int) $line->line_number,
                'reference' => $itemCode,
                'description' => $description,
                'variant' => $variantName,
                'quantity' => $this->formatDecimal($line->ordered_quantity, self::QUANTITY_SCALE),
                'uom' => $this->nullableString($line->uom?->symbol)
                    ?? $this->nullableString($line->uom?->code)
                    ?? $this->nullableString($line->uom?->name),
                'unit_price' => $this->money($line->unit_price, $currency),
                'discount' => $this->money($line->discount_amount, $currency),
                'tax' => $this->money($line->tax_amount, $currency),
                'total' => $this->money($line->line_total, $currency),
            ];
        })->all();
    }

    /** @return array{name:string,tin:?string,vat:?string,address:?string,phone:?string,email:?string} */
    private function organization(PurchaseOrder $order): array
    {
        $profile = $order->organization_unit_id === null
            ? null
            : $this->organizationProfiles->find((int) $order->tenant_id, (int) $order->organization_unit_id);

        return [
            'name' => $profile?->legalName ?? $this->nullableString($order->organizationUnit?->name) ?? 'Organization',
            'tin' => $profile?->tin,
            'vat' => $profile?->vatRegistrationNumber,
            'address' => $this->nullableString($profile?->address),
            'phone' => $profile?->phone,
            'email' => $profile?->email,
        ];
    }

    /** @return array{name:string,code:?string,tin:?string,vat:?string,address:?string,phone:?string,email:?string} */
    private function supplier(?Supplier $supplier): array
    {
        if (! $supplier instanceof Supplier) {
            return [
                'name' => 'Supplier', 'code' => null, 'tin' => null, 'vat' => null,
                'address' => null, 'phone' => null, 'email' => null,
            ];
        }

        /** @var SupplierAddress|null $address */
        $address = $supplier->addresses->where('is_active', true)->sortByDesc('is_primary')->first();
        /** @var SupplierContact|null $contact */
        $contact = $supplier->contacts->where('is_active', true)->sortByDesc('is_primary')->first();

        return [
            'name' => $this->nullableString($supplier->legal_name)
                ?? $this->nullableString($supplier->display_name)
                ?? $this->nullableString($supplier->name)
                ?? 'Supplier',
            'code' => $this->nullableString($supplier->code) ?? $this->nullableString($supplier->supplier_number),
            'tin' => $this->nullableString($supplier->tax_registration_number),
            'vat' => $this->nullableString($supplier->vat_number),
            'address' => $address instanceof SupplierAddress ? $this->address($address) : null,
            'phone' => $this->nullableString($supplier->phone)
                ?? $this->nullableString($contact?->phone)
                ?? $this->nullableString($contact?->mobile),
            'email' => $this->nullableString($supplier->email) ?? $this->nullableString($contact?->email),
        ];
    }

    private function address(SupplierAddress $address): ?string
    {
        return $this->nullableString(implode(', ', array_filter([
            $this->nullableString($address->address_line_1),
            $this->nullableString($address->address_line_2),
            $this->nullableString($address->city),
            $this->nullableString($address->state),
            $this->nullableString($address->postal_code),
            $this->nullableString($address->country),
        ])));
    }

    /** @return array{code:?string,prefix:string} */
    private function currency(PurchaseOrder $order): array
    {
        $code = $this->nullableString($order->currency?->code);
        $symbol = $this->nullableString($order->currency?->symbol);

        return ['code' => $code, 'prefix' => $symbol ?? $code ?? ''];
    }

    /** @return array{raw:string,amount:string,display:string} */
    private function money(mixed $value, array $currency): array
    {
        $raw = $this->nullableString($value) ?? '0.000000';
        $amount = $this->formatDecimal($raw, self::MONEY_SCALE);

        return [
            'raw' => $raw,
            'amount' => $amount,
            'display' => trim($currency['prefix'].' '.$amount),
        ];
    }

    private function relationLabel(mixed $relation): ?string
    {
        if ($relation === null) {
            return null;
        }

        $name = $this->nullableString($relation->name ?? null);
        $code = $this->nullableString($relation->code ?? null);

        return $name !== null && $code !== null ? $code.' - '.$name : $name ?? $code;
    }

    private function enumValue(mixed $value): ?string
    {
        return $value instanceof BackedEnum ? (string) $value->value : $this->nullableString($value);
    }

    private function label(string $value): string
    {
        return ucwords(str_replace('_', ' ', $value));
    }

    private function dateString(mixed $value): ?string
    {
        return $value instanceof DateTimeInterface ? $value->format('Y-m-d') : $this->nullableString($value);
    }

    private function dateTimeString(mixed $value): ?string
    {
        return $value instanceof DateTimeInterface ? $value->format('Y-m-d H:i') : $this->nullableString($value);
    }

    private function formatDecimal(mixed $value, int $scale): string
    {
        return number_format((float) ($this->nullableString($value) ?? '0'), $scale, '.', ',');
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
