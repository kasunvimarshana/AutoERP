<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use BackedEnum;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceDocumentKind;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\InvoiceDocumentSnapshot;
use Modules\Invoice\Models\InvoiceLine;
use Modules\OrganizationUnit\Contracts\OrganizationUnitLegalProfileReaderInterface;

final class InvoiceDocumentSnapshotService
{
    public function __construct(
        private readonly OrganizationUnitLegalProfileReaderInterface $organizationProfiles,
        private readonly InvoiceReferenceSnapshotService $references,
    ) {}

    public function create(Invoice $invoice, CreateInvoiceData $data): InvoiceDocumentSnapshot
    {
        $invoice->loadMissing(['tenant', 'organizationUnit', 'lines']);
        $organizationProfile = $data->organizationUnitId === null
            ? null
            : $this->organizationProfiles->find($data->tenantId, $data->organizationUnitId);
        $organization = [
            'name' => $organizationProfile?->legalName
                ?? $this->nullableString($invoice->organizationUnit?->name)
                ?? $this->nullableString($invoice->tenant?->name)
                ?? 'Organization',
            'tin' => $organizationProfile?->tin,
            'vat_registration_number' => $organizationProfile?->vatRegistrationNumber,
            'svat_registration_number' => $organizationProfile?->svatRegistrationNumber,
            'address' => $organizationProfile?->address,
            'phone' => $organizationProfile?->phone,
            'email' => $organizationProfile?->email,
        ];
        $counterparty = $this->references->documentParty($data);
        $outbound = $this->enumValue($invoice->direction) === InvoiceDirection::Outbound->value;
        $seller = $outbound ? $organization : $counterparty;
        $buyer = $outbound ? $counterparty : $organization;

        $snapshot = new InvoiceDocumentSnapshot();
        $snapshot->forceFill([
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'invoice_id' => (int) $invoice->getKey(),
            'document_kind' => $this->documentKind($invoice)->value,
            'organization_profile_present' => $organizationProfile !== null,
            ...$this->partyColumns('seller', $seller),
            ...$this->partyColumns('buyer', $buyer),
            'supply_date' => $this->nullableString($data->supplyDate),
            'supply_period_start' => $this->nullableString($data->supplyPeriodStart),
            'supply_period_end' => $this->nullableString($data->supplyPeriodEnd),
            'place_of_supply' => $this->nullableString($data->placeOfSupply)
                ?? $this->nullableString($organizationProfile?->address),
            'payment_mode' => $this->nullableString($data->paymentMode),
            'payment_terms' => $this->nullableString($data->paymentTerms),
        ]);
        $snapshot->save();

        return $snapshot;
    }

    private function documentKind(Invoice $invoice): InvoiceDocumentKind
    {
        $type = $this->enumValue($invoice->invoice_type);
        $direction = $this->enumValue($invoice->direction);

        if ($type === InvoiceType::Credit->value) {
            return InvoiceDocumentKind::CreditNote;
        }
        if ($type === InvoiceType::Debit->value) {
            return InvoiceDocumentKind::DebitNote;
        }
        if ($type === InvoiceType::Rental->value && $direction === InvoiceDirection::Inbound->value) {
            return InvoiceDocumentKind::OwnerPayableVoucher;
        }
        if ($direction === InvoiceDirection::Inbound->value) {
            return InvoiceDocumentKind::PurchaseInvoice;
        }

        return $this->hasOutputTax($invoice)
            ? InvoiceDocumentKind::TaxInvoice
            : InvoiceDocumentKind::Invoice;
    }

    private function hasOutputTax(Invoice $invoice): bool
    {
        return $invoice->lines->contains(static function (InvoiceLine $line): bool {
            if (! is_array($line->tax_snapshot)) {
                return false;
            }
            foreach ($line->tax_snapshot as $tax) {
                if (is_array($tax) && ! (bool) ($tax['is_withholding'] ?? false)) {
                    return true;
                }
            }

            return false;
        });
    }

    /** @param array{name:string,tin:?string,vat_registration_number:?string,svat_registration_number:?string,address:?string,phone:?string,email:?string} $party */
    private function partyColumns(string $prefix, array $party): array
    {
        return [
            $prefix.'_legal_name' => $party['name'],
            $prefix.'_tin' => $party['tin'],
            $prefix.'_vat_registration_number' => $party['vat_registration_number'],
            $prefix.'_svat_registration_number' => $party['svat_registration_number'],
            $prefix.'_address' => $party['address'],
            $prefix.'_phone' => $party['phone'],
            $prefix.'_email' => $party['email'],
        ];
    }

    private function enumValue(mixed $value): ?string
    {
        return $value instanceof BackedEnum ? (string) $value->value : $this->nullableString($value);
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
