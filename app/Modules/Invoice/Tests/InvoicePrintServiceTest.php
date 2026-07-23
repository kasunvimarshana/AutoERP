<?php

declare(strict_types=1);

namespace Modules\Invoice\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Modules\Invoice\Constants\InvoiceTaxMetadata;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceAdjustmentData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\Enums\AdjustmentEffect;
use Modules\Invoice\Enums\AdjustmentType;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoicePartyType;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Http\Controllers\InvoiceController;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\Invoice\Services\InvoicePrintService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\Support\OrganizationUnitFixture;
use Tests\TestCase;

final class InvoicePrintServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_print_view_uses_persisted_totals_and_immutable_party_snapshots(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();
        $customerId = $this->customer($tenantId, $organizationUnitId, 'Original Print Customer');
        $invoice = $this->printedInvoice($tenantId, $organizationUnitId, $customerId);
        $originalName = (string) $invoice->documentSnapshot?->buyer_legal_name;

        DB::table('customers')->where('id', $customerId)->update([
            'display_name' => 'Changed Print Customer',
            'name' => 'Changed Print Customer',
            'legal_name' => 'Changed Print Customer Legal',
            'tax_registration_number' => 'CHANGED-TAX',
            'phone' => '0000000000',
            'updated_at' => now(),
        ]);

        $invoice = $this->invoice($tenantId, (int) $invoice->getKey());
        $data = app(InvoicePrintService::class)->viewData($invoice, '/invoices/'.(int) $invoice->getKey().'/pdf');
        $html = view('invoice.print', $data)->render();

        $this->assertSame('100.000000', $data['document']['amounts']['subtotal']['raw']);
        $this->assertSame('15.000000', $data['document']['amounts']['tax_total']['raw']);
        $this->assertSame('5.000000', $data['document']['amounts']['discount_total']['raw']);
        $this->assertSame('95.000000', $data['document']['amounts']['grand_total']['raw']);
        $this->assertSame($originalName, $data['document']['purchaser']['name']);
        $this->assertStringContainsString('Tax Invoice', $html);
        $this->assertStringContainsString('Tax Invoice No.', $html);
        $this->assertStringContainsString('Total Value of Supply', $html);
        $this->assertStringContainsString('Total Amount including VAT', $html);
        $this->assertStringContainsString($originalName, $html);
        $this->assertStringContainsString('95.00', $html);
        $this->assertStringNotContainsString('118.00', $html);
        $this->assertStringNotContainsString('Changed Print Customer', $html);
        $this->assertStringContainsString('VAT Amount', $html);
        $this->assertStringNotContainsString('Total Value of Supply @ 18%', $html);
        $this->assertStringNotContainsString('SAMPLE', $html);
        $this->assertStringNotContainsString('EOG', $html);
    }

    public function test_signed_print_link_is_issued_only_for_the_current_organization_scope(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();
        $otherOrganizationUnitId = $this->organizationUnit($tenantId, 'Invoice Print Other Unit', 'INV-PRINT-OTHER');
        $customerId = $this->customer($tenantId, $organizationUnitId, 'Scoped Print Customer');
        $invoice = $this->printedInvoice($tenantId, $organizationUnitId, $customerId);

        $request = $this->requestWithScope($tenantId, $organizationUnitId);
        $response = $this->withTenantExecutionContext(
            $tenantId,
            fn () => app(InvoiceController::class)->signedPrintLink($request, (int) $invoice->getKey()),
        );
        $payload = $response->getData(true);

        $this->assertStringContainsString('/signed/invoices/', $payload['data']['print_url']);
        $this->assertStringContainsString('/signed/invoices/', $payload['data']['pdf_url']);
        $this->assertStringContainsString('organization_unit='.$organizationUnitId, $payload['data']['print_url']);
        $this->assertStringContainsString('organization_unit='.$organizationUnitId, $payload['data']['pdf_url']);

        $this->expectException(NotFoundHttpException::class);

        $this->withTenantExecutionContext(
            $tenantId,
            fn () => app(InvoiceController::class)->signedPrintLink(
                $this->requestWithScope($tenantId, $otherOrganizationUnitId),
                (int) $invoice->getKey(),
            ),
        );
    }

    public function test_public_signed_print_requires_the_signed_organization_scope(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();
        $otherOrganizationUnitId = $this->organizationUnit($tenantId, 'Invoice Public Other Unit', 'INV-PUBLIC-OTHER');
        $customerId = $this->customer($tenantId, $organizationUnitId, 'Public Print Customer');
        $invoice = $this->printedInvoice($tenantId, $organizationUnitId, $customerId);

        $validUrl = URL::temporarySignedRoute('invoices.public.print', now()->addMinutes(5), [
            'invoice' => (int) $invoice->getKey(),
            'tenant' => $tenantId,
            'organization_unit' => $organizationUnitId,
        ]);
        $this->get($validUrl)
            ->assertOk()
            ->assertSee('Public Print Customer Legal')
            ->assertDontSee('118.00');

        $validPdfUrl = URL::temporarySignedRoute('invoices.public.pdf', now()->addMinutes(5), [
            'invoice' => (int) $invoice->getKey(),
            'tenant' => $tenantId,
            'organization_unit' => $organizationUnitId,
        ]);
        $pdfResponse = $this->get($validPdfUrl)->assertOk();
        $pdfResponse->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdfResponse->getContent());

        $wrongScopeUrl = URL::temporarySignedRoute('invoices.public.print', now()->addMinutes(5), [
            'invoice' => (int) $invoice->getKey(),
            'tenant' => $tenantId,
            'organization_unit' => $otherOrganizationUnitId,
        ]);
        $this->get($wrongScopeUrl)->assertNotFound();
    }

    private function printedInvoice(int $tenantId, int $organizationUnitId, int $customerId): Invoice
    {
        return $this->withTenantExecutionContext(
            $tenantId,
            fn (): Invoice => app(InvoiceCreationService::class)->create(new CreateInvoiceData(
                tenantId: $tenantId,
                invoiceType: InvoiceType::Manual,
                direction: InvoiceDirection::Outbound,
                invoiceDate: '2026-07-07',
                organizationUnitId: $organizationUnitId,
                invoiceNumber: 'INV-PRINT-'.Str::upper(Str::random(6)),
                partyType: InvoicePartyType::Customer->value,
                partyId: $customerId,
                lines: [
                    new InvoiceLineData(
                        lineNumber: 1,
                        description: 'Inclusive tax service',
                        quantity: '1.000000',
                        unitPrice: '100.000000',
                        taxAmount: '15.000000',
                        lineTotal: '100.000000',
                        metadata: [
                            InvoiceTaxMetadata::TAXES => [[
                                InvoiceTaxMetadata::CALCULATION_METHOD => InvoiceTaxMetadata::CALCULATION_METHOD_INCLUSIVE,
                                InvoiceTaxMetadata::TAX_AMOUNT => '15.000000',
                                InvoiceTaxMetadata::IS_WITHHOLDING => false,
                                'tax_code' => 'VAT-INCLUSIVE',
                            ]],
                        ],
                    ),
                ],
                adjustments: [
                    new InvoiceAdjustmentData(
                        name: 'Tax withholding',
                        adjustmentType: AdjustmentType::Withholding,
                        effect: AdjustmentEffect::Decrease,
                        amount: '5.000000',
                    ),
                ],
            )),
        );
    }

    private function invoice(int $tenantId, int $invoiceId): Invoice
    {
        return $this->withTenantExecutionContext(
            $tenantId,
            fn (): Invoice => Invoice::query()
                ->with(['tenant', 'organizationUnit', 'lines', 'documentSnapshot'])
                ->findOrFail($invoiceId),
        );
    }

    /** @return array{int, int} */
    private function scope(): array
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

        return [
            $tenantId,
            $this->organizationUnit($tenantId, 'Invoice Print Unit', 'INV-PRINT'),
        ];
    }

    private function organizationUnit(int $tenantId, string $name, string $code): int
    {
        return OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'code' => $code,
            'vat_registration_number' => 'VAT-'.$code,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function customer(int $tenantId, int $organizationUnitId, string $name): int
    {
        $suffix = Str::upper(Str::random(6));

        return (int) DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'customer_number' => 'CUS-'.$suffix,
            'code' => 'CUS-'.$suffix,
            'name' => $name,
            'legal_name' => $name.' Legal',
            'display_name' => $name,
            'customer_type' => 'company',
            'status' => 'active',
            'email' => 'customer-'.Str::lower($suffix).'@example.test',
            'phone' => '0111234567',
            'tax_registration_number' => 'TAX-'.$suffix,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function requestWithScope(int $tenantId, int $organizationUnitId): Request
    {
        $request = Request::create('/api/v1/invoices/signed-print', 'POST');
        $request->attributes->set((string) config('core.current_tenant.id_attribute', 'current_tenant_id'), $tenantId);
        $request->attributes->set(
            (string) config('core.current_organization_unit.id_attribute', 'current_organization_unit_id'),
            $organizationUnitId,
        );

        return $request;
    }
}
