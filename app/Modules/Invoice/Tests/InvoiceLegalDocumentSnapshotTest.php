<?php

declare(strict_types=1);

namespace Modules\Invoice\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Invoice\Constants\InvoiceTaxMetadata;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceDocumentKind;
use Modules\Invoice\Enums\InvoicePartyType;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\Invoice\Services\InvoicePrintService;
use Tests\Support\OrganizationUnitFixture;
use Tests\TestCase;

final class InvoiceLegalDocumentSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_tax_invoice_freezes_legal_parties_supply_and_payment_facts(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope([
            'legal_name' => 'AutoERP Mobility (Private) Limited',
            'tin' => 'ORG-TIN-001',
            'vat_registration_number' => 'ORG-VAT-001',
            'svat_registration_number' => 'ORG-SVAT-001',
            'address_line_1' => '100 Company Road',
            'city' => 'Colombo',
            'postal_code' => '00100',
            'country' => 'Sri Lanka',
            'legal_phone' => '0112000000',
        ]);
        $customerId = $this->customer($tenantId, $organizationUnitId, 'Customer Legal Limited');
        $addressId = $this->customerAddress($tenantId, $organizationUnitId, $customerId);

        $invoice = $this->createInvoice(new CreateInvoiceData(
            tenantId: $tenantId,
            invoiceType: InvoiceType::Manual,
            direction: InvoiceDirection::Outbound,
            invoiceDate: '2026-07-21',
            organizationUnitId: $organizationUnitId,
            invoiceNumber: 'TAX-INV-001',
            partyType: InvoicePartyType::Customer->value,
            partyId: $customerId,
            paymentMode: 'Credit',
            paymentTerms: 'Credit - 30 calendar days',
            supplyDate: '2026-07-20',
            supplyPeriodStart: '2026-07-01',
            supplyPeriodEnd: '2026-07-20',
            lines: [new InvoiceLineData(
                lineNumber: 1,
                description: 'Vehicle rental service',
                quantity: '1.000000',
                unitPrice: '125450.750000',
                taxAmount: '0.000000',
                lineTotal: '125450.750000',
                metadata: [
                    InvoiceTaxMetadata::TAXES => [[
                        InvoiceTaxMetadata::CALCULATION_METHOD => InvoiceTaxMetadata::CALCULATION_METHOD_EXCLUSIVE,
                        InvoiceTaxMetadata::TAX_AMOUNT => '0.000000',
                        InvoiceTaxMetadata::IS_WITHHOLDING => false,
                        'tax_code' => 'VAT-ZERO',
                    ]],
                ],
            )],
        ));

        $snapshot = $invoice->documentSnapshot;
        self::assertNotNull($snapshot);
        self::assertSame(InvoiceDocumentKind::TaxInvoice, $snapshot->document_kind);
        self::assertSame('AutoERP Mobility (Private) Limited', $snapshot->seller_legal_name);
        self::assertSame('ORG-TIN-001', $snapshot->seller_tin);
        self::assertSame('ORG-VAT-001', $snapshot->seller_vat_registration_number);
        self::assertSame('100 Company Road, Colombo, 00100, Sri Lanka', $snapshot->seller_address);
        self::assertSame('Customer Legal Limited', $snapshot->buyer_legal_name);
        self::assertSame('CUS-TIN-001', $snapshot->buyer_tin);
        self::assertSame('CUS-VAT-001', $snapshot->buyer_vat_registration_number);
        self::assertSame('20 Customer Avenue, Kandy, 20000, Sri Lanka', $snapshot->buyer_address);
        self::assertSame('2026-07-20', $snapshot->supply_date?->toDateString());
        self::assertSame('2026-07-01', $snapshot->supply_period_start?->toDateString());
        self::assertSame('2026-07-20', $snapshot->supply_period_end?->toDateString());
        self::assertSame('100 Company Road, Colombo, 00100, Sri Lanka', $snapshot->place_of_supply);
        self::assertSame('Credit', $snapshot->payment_mode);
        self::assertSame('Credit - 30 calendar days', $snapshot->payment_terms);

        DB::table('organization_unit_legal_profiles')
            ->where('organization_unit_id', $organizationUnitId)
            ->update(['legal_name' => 'Changed Organization', 'tin' => 'CHANGED-ORG-TIN']);
        DB::table('customers')->where('id', $customerId)->update([
            'legal_name' => 'Changed Customer',
            'tax_registration_number' => 'CHANGED-CUS-TIN',
        ]);
        DB::table('customer_addresses')->where('id', $addressId)->update([
            'address_line_1' => 'Changed Customer Address',
        ]);

        $invoice = $this->reload($tenantId, (int) $invoice->getKey());
        $document = app(InvoicePrintService::class)->viewData($invoice)['document'];
        self::assertSame('Tax Invoice', $document['title']);
        self::assertSame('Tax Invoice No.', $document['number_label']);
        self::assertSame('AutoERP Mobility (Private) Limited', $document['supplier']['name']);
        self::assertSame('ORG-TIN-001', $document['supplier']['tin']);
        self::assertSame('Customer Legal Limited', $document['purchaser']['name']);
        self::assertSame('CUS-TIN-001', $document['purchaser']['tin']);
        self::assertSame('20 Customer Avenue, Kandy, 20000, Sri Lanka', $document['purchaser']['address']);
        self::assertSame('100 Company Road, Colombo, 00100, Sri Lanka', $document['place_of_supply']);
        self::assertSame(
            'One Hundred Twenty-Five Thousand Four Hundred Fifty and 75/100 Only',
            $document['amount_in_words'],
        );
        self::assertSame([], $document['warnings']);
    }

    public function test_inbound_rental_document_prints_as_owner_payable_voucher(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();
        $supplierId = $this->supplier($tenantId, $organizationUnitId);

        $invoice = $this->createInvoice(new CreateInvoiceData(
            tenantId: $tenantId,
            invoiceType: InvoiceType::Rental,
            direction: InvoiceDirection::Inbound,
            invoiceDate: '2026-07-21',
            organizationUnitId: $organizationUnitId,
            invoiceNumber: 'OPV-001',
            partyType: InvoicePartyType::Supplier->value,
            partyId: $supplierId,
            supplyDate: '2026-07-20',
            supplyPeriodStart: '2026-07-01',
            supplyPeriodEnd: '2026-07-20',
            paymentMode: 'Credit',
            paymentTerms: 'Credit - 15 calendar days',
            lines: [new InvoiceLineData(
                lineNumber: 1,
                description: 'Owner rental payable',
                quantity: '1.000000',
                unitPrice: '50000.000000',
                lineTotal: '50000.000000',
            )],
        ));

        $document = app(InvoicePrintService::class)->viewData($invoice)['document'];
        self::assertSame(InvoiceDocumentKind::OwnerPayableVoucher, $invoice->documentSnapshot?->document_kind);
        self::assertSame('Owner Payable Voucher', $document['title']);
        self::assertSame('Owner Payable Voucher No.', $document['number_label']);
        self::assertSame('Vehicle Owner Legal', $document['supplier']['name']);
        self::assertSame('Invoice Test Unit Legal', $document['purchaser']['name']);
    }

    public function test_business_invoice_uses_available_identity_when_legal_profile_is_missing(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope(['create_legal_profile' => false]);
        $customerId = $this->customer($tenantId, $organizationUnitId, 'Missing Profile Customer');

        $invoice = $this->createInvoice(new CreateInvoiceData(
            tenantId: $tenantId,
            invoiceType: InvoiceType::Manual,
            direction: InvoiceDirection::Outbound,
            invoiceDate: '2026-07-21',
            organizationUnitId: $organizationUnitId,
            invoiceNumber: 'INV-MISSING-PROFILE',
            partyType: InvoicePartyType::Customer->value,
            partyId: $customerId,
            lines: [new InvoiceLineData(
                lineNumber: 1,
                description: 'Service',
                quantity: '1.000000',
                unitPrice: '10.000000',
                taxAmount: '0.000000',
                lineTotal: '10.000000',
                metadata: [
                    InvoiceTaxMetadata::TAXES => [[
                        InvoiceTaxMetadata::CALCULATION_METHOD => InvoiceTaxMetadata::CALCULATION_METHOD_EXCLUSIVE,
                        InvoiceTaxMetadata::TAX_AMOUNT => '0.000000',
                        InvoiceTaxMetadata::IS_WITHHOLDING => false,
                        'tax_code' => 'VAT-ZERO',
                    ]],
                ],
            )],
        ));

        $snapshot = $invoice->documentSnapshot;
        self::assertNotNull($snapshot);
        self::assertFalse((bool) $snapshot->organization_profile_present);
        self::assertSame(InvoiceDocumentKind::Invoice, $snapshot->document_kind);
        self::assertSame('Invoice Test Unit', $snapshot->seller_legal_name);
        self::assertNull($snapshot->seller_tin);
        self::assertNull($snapshot->seller_vat_registration_number);
        self::assertNull($snapshot->seller_svat_registration_number);
        self::assertNull($snapshot->seller_address);
        self::assertNull($snapshot->seller_phone);
        self::assertNull($snapshot->seller_email);
        self::assertNull($snapshot->place_of_supply);

        $document = app(InvoicePrintService::class)->viewData($invoice)['document'];
        self::assertSame('Invoice', $document['title']);
        self::assertSame('Invoice No.', $document['number_label']);
        self::assertSame('Invoice Test Unit', $document['supplier']['name']);
        self::assertNull($document['supplier']['tin']);
        self::assertNull($document['supplier']['vat_registration_number']);
        self::assertNull($document['supplier']['address']);
        self::assertSame([], $document['warnings']);
    }

    private function createInvoice(CreateInvoiceData $data): Invoice
    {
        return $this->withTenantExecutionContext(
            $data->tenantId,
            fn (): Invoice => app(InvoiceCreationService::class)->create($data),
        );
    }

    private function reload(int $tenantId, int $invoiceId): Invoice
    {
        return $this->withTenantExecutionContext(
            $tenantId,
            fn (): Invoice => Invoice::query()
                ->with(['tenant', 'organizationUnit', 'lines', 'documentSnapshot'])
                ->findOrFail($invoiceId),
        );
    }

    /** @param array<string, mixed> $organizationAttributes @return array{int, int} */
    private function scope(array $organizationAttributes = []): array
    {
        $suffix = Str::upper(Str::random(6));
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-'.$suffix,
            'name' => 'Tenant '.$suffix,
            'slug' => 'tenant-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $organizationUnitId = OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => 'Invoice Test Unit',
            'code' => 'INV-LEGAL-'.$suffix,
            'created_at' => now(),
            'updated_at' => now(),
            ...$organizationAttributes,
        ]);

        return [$tenantId, $organizationUnitId];
    }

    private function customer(int $tenantId, int $organizationUnitId, string $legalName): int
    {
        $suffix = Str::upper(Str::random(6));

        return (int) DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'customer_number' => 'CUS-'.$suffix,
            'code' => 'CUS-'.$suffix,
            'name' => $legalName,
            'legal_name' => $legalName,
            'display_name' => $legalName,
            'customer_type' => 'company',
            'status' => 'active',
            'email' => 'customer@example.test',
            'phone' => '0812000000',
            'tax_registration_number' => 'CUS-TIN-001',
            'vat_number' => 'CUS-VAT-001',
            'svat_number' => 'CUS-SVAT-001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function customerAddress(int $tenantId, int $organizationUnitId, int $customerId): int
    {
        return (int) DB::table('customer_addresses')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'customer_id' => $customerId,
            'address_type' => 'registered',
            'address_line_1' => '20 Customer Avenue',
            'city' => 'Kandy',
            'postal_code' => '20000',
            'country' => 'Sri Lanka',
            'is_primary' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function supplier(int $tenantId, int $organizationUnitId): int
    {
        $suffix = Str::upper(Str::random(6));

        return (int) DB::table('suppliers')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'supplier_number' => 'SUP-'.$suffix,
            'code' => 'SUP-'.$suffix,
            'name' => 'Vehicle Owner',
            'legal_name' => 'Vehicle Owner Legal',
            'display_name' => 'Vehicle Owner',
            'supplier_type' => 'individual',
            'status' => 'active',
            'phone' => '0772000000',
            'tax_registration_number' => 'OWNER-TIN-001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
