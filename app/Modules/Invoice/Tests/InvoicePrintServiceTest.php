<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Invoice\Tests;

use DateTimeImmutable;
use Dompdf\Dompdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Modules\Configuration\Contracts\ConfigurationResolverInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Invoice\Constants\InvoiceTaxMetadata;
use Modules\Invoice\Contracts\InvoicePaymentMethodProviderInterface;
use Modules\Invoice\Data\InvoicePrintContext;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceAdjustmentData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\Enums\AdjustmentEffect;
use Modules\Invoice\Enums\AdjustmentType;
use Modules\Invoice\Enums\InvoiceCopyType;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceLineType;
use Modules\Invoice\Enums\InvoicePartyType;
use Modules\Invoice\Enums\InvoicePrintLayout;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Http\Controllers\InvoiceController;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\Invoice\Services\InvoicePrintService;
use Modules\User\Contracts\AuthenticatedUserProviderInterface;
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
        $this->assertSame([], $data['document']['purchaser_reference_fields']);
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
        $this->assertStringNotContainsString('Job No:</span>', $html);
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

    public function test_pdf_mode_fits_the_invoice_within_one_a4_page(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();
        $customerId = $this->customer($tenantId, $organizationUnitId, 'A4 Width Customer');
        $invoice = $this->printedInvoice($tenantId, $organizationUnitId, $customerId);
        $invoice = $this->invoice($tenantId, (int) $invoice->getKey());
        $html = view('invoice.print', app(InvoicePrintService::class)->viewData($invoice, mode: 'pdf'))->render();

        $this->assertStringContainsString('pdf-output', $html);

        $dompdf = new Dompdf;
        $dompdf->loadHtml($html);
        $layout = app(InvoicePrintService::class)->layout($invoice);
        $dompdf->setPaper($layout->paperSize(), $layout->orientation());
        $dompdf->render();

        $this->assertSame(1, $dompdf->getCanvas()->get_page_count());
    }

    public function test_service_a4_design_lists_lines_and_keeps_policy_and_trace_on_one_page(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();
        $customerId = $this->customer($tenantId, $organizationUnitId, 'Service Print Customer');
        $invoice = $this->withTenantExecutionContext(
            $tenantId,
            fn (): Invoice => app(InvoiceCreationService::class)->create(new CreateInvoiceData(
                tenantId: $tenantId,
                invoiceType: InvoiceType::Service,
                direction: InvoiceDirection::Outbound,
                invoiceDate: '2026-11-12',
                organizationUnitId: $organizationUnitId,
                partyType: InvoicePartyType::Customer->value,
                partyId: $customerId,
                lines: [
                    new InvoiceLineData(lineNumber: 1, description: 'Wash only', quantity: '1.000000', unitPrice: '600.000000', lineType: InvoiceLineType::Service),
                    new InvoiceLineData(lineNumber: 2, description: 'Engine oil', quantity: '9.000000', unitPrice: '2300.000000', discountAmount: '2000.000000'),
                    new InvoiceLineData(lineNumber: 3, description: 'Oil filter', quantity: '1.000000', unitPrice: '4265.000000'),
                    new InvoiceLineData(lineNumber: 4, description: 'Grease', quantity: '6.000000', unitPrice: '281.210000'),
                ],
                purchaserReferenceFields: [
                    ['label' => 'Job No', 'value' => 'VSJ-000902'],
                    ['label' => 'Vehicle No', 'value' => 'LK-3752'],
                    ['label' => 'Mileage', 'value' => '242,219 km'],
                    ['label' => 'Next Service Mileage', 'value' => '247,219 km'],
                ],
            )),
        );
        DB::table('invoice_lines')->where('invoice_id', $invoice->getKey())->where('line_number', 3)->update(['item_code_snapshot' => 'FILTER-A4']);
        $invoice = $this->invoice($tenantId, (int) $invoice->getKey());
        $prints = app(InvoicePrintService::class);
        $data = $prints->viewData(
            $invoice,
            mode: 'pdf',
            printContext: new InvoicePrintContext(
                new DateTimeImmutable('2026-11-16T06:47:00+00:00'),
                'Kasun Perera',
                InvoiceCopyType::Duplicate,
            ),
        );
        $html = view('invoice.print', $data)->render();

        $this->assertTrue($data['print_layout']['is_outbound_a4']);
        $this->assertSame(['Wash only', 'Engine oil', 'Oil filter', 'Grease'], array_column($data['document']['lines'], 'display_name'));
        $this->assertStringNotContainsString('>LABOUR<', $html);
        $this->assertStringNotContainsString('>SPARE PARTS / LUBRICANTS<', $html);
        $this->assertStringContainsString('Job No:</span> VSJ-000902', $html);
        $this->assertStringContainsString('Date of invoice:</strong> 12 Nov 2026', $html);
        $this->assertStringContainsString('Line discount', $html);
        $this->assertStringContainsString('FILTER-A4', $html);
        $this->assertStringContainsString('Balance due', $html);
        $this->assertStringContainsString('Non Refundable, Exchange possible only within 7 days of purchase', $html);
        $this->assertStringContainsString('( This invoice cannot be used for a VAT input Claim )', $html);
        $this->assertLessThan(strpos($html, 'class="signatures"'), strpos($html, 'class="service-policy"'));
        $this->assertStringNotContainsString('Checked by', $html);
        $this->assertStringContainsString('Thank you for your business.', $html);
        $this->assertStringContainsString('By: Kasun Perera | Duplicate', $html);
        $this->assertStringNotContainsString('class="item-code">2</span>', $html);

        $dompdf = new Dompdf;
        $dompdf->loadHtml($html);
        $dompdf->setPaper($prints->layout($invoice)->pdfPaper(), 'portrait');
        $dompdf->render();

        $this->assertSame(1, $dompdf->getCanvas()->get_page_count());
    }

    public function test_service_invoice_a5_layout_is_focused_portrait_and_fits_one_page(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();
        $customerId = $this->customer($tenantId, $organizationUnitId, 'A5 Compact Customer');
        $invoice = $this->printedInvoice($tenantId, $organizationUnitId, $customerId, InvoiceType::Service);
        DB::table('invoice_balances')
            ->where('invoice_id', $invoice->getKey())
            ->update([
                'paid_amount' => '100.000000',
                'remaining_amount' => '0.000000',
                'status' => 'paid',
                'updated_at' => now(),
            ]);
        DB::table('invoices')
            ->where('id', $invoice->getKey())
            ->update([
                'paid_total' => '100.000000',
                'balance_due' => '0.000000',
                'status' => 'paid',
                'updated_at' => now(),
            ]);
        $invoice = $this->invoice($tenantId, (int) $invoice->getKey());
        $configuration = $this->createMock(ConfigurationResolverInterface::class);
        $configuration->method('value')->willReturnCallback(
            static fn (string $key): string => $key === InvoicePrintLayout::CONFIGURATION_KEY
                ? InvoicePrintLayout::CompactA5->value
                : 'Asia/Colombo',
        );
        $this->app->instance(ConfigurationResolverInterface::class, $configuration);
        $prints = app(InvoicePrintService::class);
        $context = new InvoicePrintContext(
            new DateTimeImmutable('2026-09-16T05:12:00+00:00'),
            'Kasun Perera',
            InvoiceCopyType::Original,
        );
        $html = view('invoice.print', $prints->viewData(
            $invoice,
            mode: 'pdf',
            printContext: $context,
        ))->render();
        $printHtml = view('invoice.print', $prints->viewData($invoice))->render();

        $this->assertStringContainsString('class="layout-a5 pdf-output"', $html);
        $this->assertStringContainsString('@page { size: A5 portrait; margin: 6mm; }', $printHtml);
        $this->assertStringContainsString('Item Name', $html);
        $this->assertStringNotContainsString('>Reference<', $html);
        $this->assertStringNotContainsString('Description of Goods or Services', $html);
        $this->assertStringNotContainsString('Total Value of Supply', $html);
        $this->assertStringNotContainsString('VAT Amount', $html);
        $this->assertStringContainsString('Total Amount including VAT', $html);
        $this->assertStringContainsString('Line discount:', $html);
        $this->assertStringContainsString('Bill discount:', $html);
        $this->assertStringNotContainsString('Credit:', $html);
        $this->assertStringContainsString('Balance due:', $html);
        $this->assertStringContainsString('Printed: 16 Sep 2026 10:42 AM | By: Kasun Perera | Original', $html);
        $this->assertStringNotContainsString('DUPLICATE', $html);

        $layout = $prints->layout($invoice);
        $dompdf = new Dompdf;
        $dompdf->loadHtml($html);
        $dompdf->setPaper($layout->paperSize(), $layout->orientation());
        $dompdf->render();

        $this->assertSame(InvoicePrintLayout::CompactA5, $layout);
        $this->assertSame('portrait', $layout->orientation());
        $this->assertSame(1, $dompdf->getCanvas()->get_page_count());
    }

    public function test_service_invoice_continuous_layout_matches_reference_sections_on_one_page(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();
        $customerId = $this->customer($tenantId, $organizationUnitId, 'Continuous Print Customer');
        $invoice = $this->withTenantExecutionContext(
            $tenantId,
            fn (): Invoice => app(InvoiceCreationService::class)->create(new CreateInvoiceData(
                tenantId: $tenantId,
                invoiceType: InvoiceType::Service,
                direction: InvoiceDirection::Outbound,
                invoiceDate: '2026-11-16',
                organizationUnitId: $organizationUnitId,
                partyType: InvoicePartyType::Customer->value,
                partyId: $customerId,
                lines: [
                    new InvoiceLineData(lineNumber: 1, description: '3M Coolant Blue 4L', quantity: '1.000000', unitPrice: '5100.000000'),
                    new InvoiceLineData(lineNumber: 2, description: 'Wash & Vacuum', quantity: '1.000000', unitPrice: '1550.000000'),
                    new InvoiceLineData(lineNumber: 3, description: 'Oil filter', quantity: '1.000000', unitPrice: '850.000000'),
                    new InvoiceLineData(lineNumber: 4, description: 'Grease', quantity: '1.000000', unitPrice: '250.000000'),
                ],
                purchaserReferenceFields: [
                    ['label' => 'Job No', 'value' => 'VSJ-000864'],
                    ['label' => 'Vehicle', 'value' => '15-0572'],
                ],
            )),
        );
        DB::table('invoice_lines')->where('invoice_id', $invoice->getKey())->where('line_number', 1)->update(['item_code_snapshot' => 'COOLANT-4L']);
        $invoice = $this->invoice($tenantId, (int) $invoice->getKey());
        $configuration = $this->createMock(ConfigurationResolverInterface::class);
        $configuration->method('value')->willReturnCallback(
            static fn (string $key): string => $key === InvoicePrintLayout::CONFIGURATION_KEY
                ? InvoicePrintLayout::Continuous85By55->value
                : 'Asia/Colombo',
        );
        $this->app->instance(ConfigurationResolverInterface::class, $configuration);

        $prints = app(InvoicePrintService::class);
        $data = $prints->viewData(
            $invoice,
            mode: 'pdf',
            printContext: new InvoicePrintContext(
                new DateTimeImmutable('2026-11-16T05:25:00+00:00'),
                'Kasun Perera',
                InvoiceCopyType::Original,
            ),
        );
        $data['document']['supplier']['address'] = '363 Kandy Road, Kalagedihena';
        $data['document']['purchaser_reference_fields'][] = ['label' => 'Mileage', 'value' => '4,757 km'];
        $data['document']['purchaser_reference_fields'][] = ['label' => 'Next Service Mileage', 'value' => '14,757 km'];
        $html = view('invoice.print', $data)->render();
        $printHtml = view('invoice.print', $prints->viewData($invoice))->render();
        $layout = $prints->layout($invoice);

        $this->assertTrue($data['print_layout']['is_outbound_continuous']);
        $this->assertStringContainsString('@page { size: 215.9mm 139.7mm; margin: 5mm 13mm 2mm 3mm; }', $printHtml);
        $this->assertStringContainsString('layout-continuous layout-outbound-continuous pdf-output', $html);
        $this->assertStringContainsString('font-size: 11px; line-height: 1.25', $html);
        $this->assertStringNotContainsString('class="company-contact"', $html);
        $this->assertStringContainsString('363 Kandy Road,<br>Kalagedihena', $html);
        $this->assertStringContainsString('Job No:</strong> VSJ-000864', $html);
        $this->assertStringContainsString('Vehicle:</strong> 15-0572', $html);
        $this->assertMatchesRegularExpression('/Job No:<\/strong> VSJ-000864.*Vehicle:<\/strong> 15-0572.*<\/div>\s*<div class="service-inline-references">.*Current Mileage:<\/strong> 4,757 km.*Next Service Mileage:<\/strong> 14,757 km/s', $html);
        $this->assertStringNotContainsString('class="service-references"', $html);
        $this->assertStringContainsString('3M Coolant Blue 4L', $html);
        $this->assertStringNotContainsString('COOLANT-4L', $html);
        $this->assertStringContainsString('Subtotal before discounts', $html);
        $this->assertStringContainsString('Non Refundable, Exchange possible only within 7 days of purchase', $html);
        $this->assertStringContainsString('Printed: 16 Nov 2026 10:55 AM | By: Kasun Perera | Original', $html);

        $dompdf = new Dompdf;
        $dompdf->loadHtml($html);
        $dompdf->setPaper($layout->pdfPaper(), $layout->orientation());
        $dompdf->render();

        $this->assertSame(1, $dompdf->getCanvas()->get_page_count());
        $this->assertEqualsWithDelta(612, $dompdf->getCanvas()->get_width(), 0.01);
        $this->assertEqualsWithDelta(396, $dompdf->getCanvas()->get_height(), 0.01);
    }

    public function test_service_invoice_continuous_short_invoice_keeps_footer_on_one_page(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();
        $customerId = $this->customer($tenantId, $organizationUnitId, 'Short Print Customer');
        $invoice = $this->withTenantExecutionContext(
            $tenantId,
            fn (): Invoice => app(InvoiceCreationService::class)->create(new CreateInvoiceData(
                tenantId: $tenantId,
                invoiceType: InvoiceType::Service,
                direction: InvoiceDirection::Outbound,
                invoiceDate: '2026-11-17',
                organizationUnitId: $organizationUnitId,
                partyType: InvoicePartyType::Customer->value,
                partyId: $customerId,
                lines: [
                    new InvoiceLineData(lineNumber: 1, description: 'Wash & Vacuum', quantity: '1.000000', unitPrice: '1550.000000'),
                ],
                purchaserReferenceFields: [
                    ['label' => 'Job No', 'value' => 'VSJ-000733'],
                    ['label' => 'Vehicle No', 'value' => 'CBJ 9292'],
                ],
            )),
        );
        $invoice = $this->invoice($tenantId, (int) $invoice->getKey());
        $configuration = $this->createMock(ConfigurationResolverInterface::class);
        $configuration->method('value')->willReturnCallback(
            static fn (string $key): string => $key === InvoicePrintLayout::CONFIGURATION_KEY
                ? InvoicePrintLayout::Continuous85By55->value
                : 'Asia/Colombo',
        );
        $this->app->instance(ConfigurationResolverInterface::class, $configuration);

        $prints = app(InvoicePrintService::class);
        $html = view('invoice.print', $prints->viewData(
            $invoice,
            mode: 'pdf',
            printContext: new InvoicePrintContext(
                new DateTimeImmutable('2026-11-16T05:25:00+00:00'),
                'admin tenant',
                InvoiceCopyType::Original,
            ),
        ))->render();
        $dompdf = new Dompdf;
        $dompdf->loadHtml($html);
        $layout = $prints->layout($invoice);
        $dompdf->setPaper($layout->pdfPaper(), $layout->orientation());
        $dompdf->render();

        $this->assertStringContainsString('Printed: 16 Nov 2026 10:55 AM | By: admin tenant | Original', $html);
        $this->assertSame(1, $dompdf->getCanvas()->get_page_count());
    }

    public function test_sales_invoice_uses_the_service_layout_on_a4_and_continuous_paper(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();
        $customerId = $this->customer($tenantId, $organizationUnitId, 'Sales Print Customer');
        $invoice = $this->printedInvoice($tenantId, $organizationUnitId, $customerId, InvoiceType::Sales);
        DB::table('invoice_lines')->where('invoice_id', $invoice->getKey())->update(['item_code_snapshot' => 'SALES-ITEM-CODE']);
        $invoice = $this->invoice($tenantId, (int) $invoice->getKey());

        $prints = app(InvoicePrintService::class);
        $a4Data = $prints->viewData($invoice, mode: 'pdf');
        $a4Html = view('invoice.print', $a4Data)->render();

        $this->assertTrue($a4Data['print_layout']['is_outbound_a4']);
        $this->assertStringContainsString('layout-a4 layout-outbound-a4 pdf-output', $a4Html);
        $this->assertStringContainsString('class="settlement-table"', $a4Html);
        $this->assertStringContainsString('SALES-ITEM-CODE', $a4Html);
        $this->assertStringContainsString('Non Refundable, Exchange possible only within 7 days of purchase', $a4Html);
        $this->assertStringNotContainsString('Job No:', $a4Html);

        $a4Pdf = new Dompdf;
        $a4Pdf->loadHtml($a4Html);
        $a4Pdf->setPaper($prints->layout($invoice)->pdfPaper(), 'portrait');
        $a4Pdf->render();
        $this->assertSame(1, $a4Pdf->getCanvas()->get_page_count());

        $configuration = $this->createMock(ConfigurationResolverInterface::class);
        $configuration->method('value')->willReturnCallback(
            static fn (string $key): string => $key === InvoicePrintLayout::CONFIGURATION_KEY
                ? InvoicePrintLayout::Continuous85By55->value
                : 'Asia/Colombo',
        );
        $this->app->instance(ConfigurationResolverInterface::class, $configuration);

        $prints = app(InvoicePrintService::class);
        $continuousData = $prints->viewData($invoice, mode: 'pdf');
        $continuousHtml = view('invoice.print', $continuousData)->render();

        $this->assertTrue($continuousData['print_layout']['is_outbound_continuous']);
        $this->assertStringContainsString('layout-continuous layout-outbound-continuous pdf-output', $continuousHtml);
        $this->assertStringContainsString('class="settlement-table"', $continuousHtml);
        $this->assertStringContainsString('Bill discount', $continuousHtml);
        $this->assertStringNotContainsString('SALES-ITEM-CODE', $continuousHtml);
        $this->assertStringNotContainsString('Job No:', $continuousHtml);

        $dompdf = new Dompdf;
        $dompdf->loadHtml($continuousHtml);
        $dompdf->setPaper($prints->layout($invoice)->pdfPaper(), 'landscape');
        $dompdf->render();

        $this->assertSame(1, $dompdf->getCanvas()->get_page_count());
        $this->assertEqualsWithDelta(612, $dompdf->getCanvas()->get_width(), 0.01);
        $this->assertEqualsWithDelta(396, $dompdf->getCanvas()->get_height(), 0.01);
    }

    public function test_service_invoice_print_shows_discounts_and_two_decimal_quantity_with_uppercase_header_only(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();
        $customerId = $this->customer($tenantId, $organizationUnitId, 'Discount Print Customer');
        $invoice = $this->withTenantExecutionContext(
            $tenantId,
            fn (): Invoice => app(InvoiceCreationService::class)->create(new CreateInvoiceData(
                tenantId: $tenantId,
                invoiceType: InvoiceType::Service,
                direction: InvoiceDirection::Outbound,
                invoiceDate: '2026-09-19',
                organizationUnitId: $organizationUnitId,
                partyType: InvoicePartyType::Customer->value,
                partyId: $customerId,
                lines: [new InvoiceLineData(
                    lineNumber: 1,
                    description: 'Wash',
                    quantity: '1.000000',
                    unitPrice: '100.000000',
                    discountAmount: '10.000000',
                )],
                adjustments: [new InvoiceAdjustmentData(
                    name: 'Service bill discount',
                    adjustmentType: AdjustmentType::Discount,
                    effect: AdjustmentEffect::Decrease,
                    amount: '5.000000',
                )],
            )),
        );
        DB::table('invoice_lines')->where('invoice_id', $invoice->getKey())->update(['uom_code_snapshot' => 'HOUR']);
        DB::table('invoice_document_snapshots')->where('invoice_id', $invoice->getKey())->update([
            'seller_legal_name' => 'PIC Auto Lanka (PVT) LTD',
        ]);
        $invoice = $this->invoice($tenantId, (int) $invoice->getKey());

        $configuration = $this->createMock(ConfigurationResolverInterface::class);
        $configuration->method('value')->willReturnCallback(
            static fn (string $key): string => $key === InvoicePrintLayout::CONFIGURATION_KEY
                ? InvoicePrintLayout::CompactA5->value
                : 'Asia/Colombo',
        );
        $this->app->instance(ConfigurationResolverInterface::class, $configuration);

        $prints = app(InvoicePrintService::class);
        $data = $prints->viewData($invoice, mode: 'pdf');
        $html = view('invoice.print', $data)->render();

        $this->assertSame('10.000000', $data['document']['amounts']['line_discount_total']['raw']);
        $this->assertSame('5.000000', $data['document']['amounts']['bill_discount_total']['raw']);
        $this->assertSame('85.000000', $data['document']['amounts']['grand_total']['raw']);
        $this->assertSame('1.00', $data['document']['lines'][0]['quantity']['display']);
        $this->assertStringContainsString('PIC AUTO LANKA (PVT) LTD</div>', $html);
        $this->assertStringContainsString("Supplier's Name:</span> PIC Auto Lanka (PVT) LTD", $html);
        $this->assertStringContainsString('Line discount:', $html);
        $this->assertStringContainsString('Bill discount:', $html);
        $this->assertStringNotContainsString('HOUR', $html);
        $this->assertStringNotContainsString('Credit:', $html);

        $dompdf = new Dompdf;
        $dompdf->loadHtml($html);
        $layout = $prints->layout($invoice);
        $dompdf->setPaper($layout->paperSize(), $layout->orientation());
        $dompdf->render();

        $this->assertSame(1, $dompdf->getCanvas()->get_page_count());
    }

    public function test_purchase_invoice_uses_focused_portrait_layout_and_realized_payment_method(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();
        $customerId = $this->customer($tenantId, $organizationUnitId, 'Supplier Invoice Party');
        $invoice = $this->printedInvoice($tenantId, $organizationUnitId, $customerId, InvoiceType::Purchase);
        DB::table('invoice_lines')->where('invoice_id', $invoice->getKey())->update([
            'item_code_snapshot' => 'BP-001',
            'item_name_snapshot' => 'Brake Pad',
            'description' => 'Internal purchase description',
        ]);
        $invoice = $this->invoice($tenantId, (int) $invoice->getKey());

        $configuration = $this->createMock(ConfigurationResolverInterface::class);
        $configuration->method('value')->willReturnCallback(
            static fn (string $key): string => $key === InvoicePrintLayout::CONFIGURATION_KEY
                ? InvoicePrintLayout::CompactA5->value
                : 'Asia/Colombo',
        );
        $this->app->instance(ConfigurationResolverInterface::class, $configuration);

        $paymentMethods = $this->createMock(InvoicePaymentMethodProviderInterface::class);
        $paymentMethods->expects($this->once())
            ->method('namesForInvoice')
            ->with((int) $invoice->getKey(), $tenantId, $organizationUnitId)
            ->willReturn(['Cash']);
        $this->app->instance(InvoicePaymentMethodProviderInterface::class, $paymentMethods);

        $prints = app(InvoicePrintService::class);
        $data = $prints->viewData($invoice, mode: 'pdf');
        $html = view('invoice.print', $data)->render();

        $this->assertSame(InvoicePrintLayout::CompactA5, $prints->layout($invoice));
        $this->assertSame('Cash', $data['document']['resolved_payment_mode']);
        $this->assertSame('Brake Pad', $data['document']['lines'][0]['display_name']);
        $this->assertStringContainsString('Mode of Payment:</span> Cash', $html);
        $this->assertStringContainsString('Item Name', $html);
        $this->assertStringContainsString('Brake Pad', $html);
        $this->assertStringNotContainsString('BP-001 - Brake Pad', $html);
        $this->assertStringNotContainsString('Internal purchase description', $html);
        $this->assertStringNotContainsString('Credit:', $html);
        $this->assertStringContainsString('Balance due:', $html);
    }

    public function test_service_invoice_copy_labels_begin_with_the_first_print_even_when_unpaid(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();
        $customerId = $this->customer($tenantId, $organizationUnitId, 'Copy State Customer');
        $invoice = $this->printedInvoice($tenantId, $organizationUnitId, $customerId, InvoiceType::Service);
        $users = $this->createMock(AuthenticatedUserProviderInterface::class);
        $users->method('requireCurrentUserRecord')->willReturn(new DataRecord([
            'first_name' => 'Kasun',
            'last_name' => 'Perera',
            'username' => 'kasun',
            'email' => 'kasun@example.test',
        ]));
        $this->app->instance(AuthenticatedUserProviderInterface::class, $users);
        $this->assertNull($invoice->fresh()->original_printed_at);

        $originalResponse = $this->withTenantExecutionContext(
            $tenantId,
            fn () => app(InvoiceController::class)->signedPrintLink(
                $this->requestWithScope($tenantId, $organizationUnitId),
                (int) $invoice->getKey(),
            ),
        );
        $originalUrl = $originalResponse->getData(true)['data']['print_url'];
        parse_str((string) parse_url($originalUrl, PHP_URL_QUERY), $originalQuery);
        $originalHtml = $this->get($originalUrl)->assertOk()->getContent();

        $duplicateResponse = $this->withTenantExecutionContext(
            $tenantId,
            fn () => app(InvoiceController::class)->signedPrintLink(
                $this->requestWithScope($tenantId, $organizationUnitId),
                (int) $invoice->getKey(),
            ),
        );
        $duplicateUrl = $duplicateResponse->getData(true)['data']['print_url'];
        parse_str((string) parse_url($duplicateUrl, PHP_URL_QUERY), $duplicateQuery);

        $this->assertSame(InvoiceCopyType::Original->value, $originalQuery['copy_type'] ?? null);
        $this->assertSame(InvoiceCopyType::Duplicate->value, $duplicateQuery['copy_type'] ?? null);
        $this->assertSame('Kasun Perera', $originalQuery['printed_by'] ?? null);
        $this->assertStringContainsString('By: Kasun Perera | Original', $originalHtml);
        $this->assertNotNull($invoice->fresh()->original_printed_at);
    }

    public function test_sales_invoice_print_trace_shows_original_then_duplicate_on_both_paper_sizes(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();
        $customerId = $this->customer($tenantId, $organizationUnitId, 'Sales Copy Customer');
        $invoice = $this->printedInvoice($tenantId, $organizationUnitId, $customerId, InvoiceType::Sales);
        $users = $this->createMock(AuthenticatedUserProviderInterface::class);
        $users->method('requireCurrentUserRecord')->willReturn(new DataRecord([
            'first_name' => 'admin',
            'last_name' => 'tenant',
        ]));
        $this->app->instance(AuthenticatedUserProviderInterface::class, $users);

        $originalResponse = $this->withTenantExecutionContext(
            $tenantId,
            fn () => app(InvoiceController::class)->signedPrintLink(
                $this->requestWithScope($tenantId, $organizationUnitId),
                (int) $invoice->getKey(),
            ),
        );
        $originalUrl = $originalResponse->getData(true)['data']['print_url'];
        $originalHtml = $this->get($originalUrl)->assertOk()->getContent();

        $this->assertStringContainsString('By: admin tenant | Original', $originalHtml);
        $this->assertNotNull($invoice->fresh()->original_printed_at);

        $configuration = $this->createMock(ConfigurationResolverInterface::class);
        $configuration->method('value')->willReturnCallback(
            static fn (string $key): string => $key === InvoicePrintLayout::CONFIGURATION_KEY
                ? InvoicePrintLayout::Continuous85By55->value
                : 'Asia/Colombo',
        );
        $this->app->instance(ConfigurationResolverInterface::class, $configuration);

        $duplicateResponse = $this->withTenantExecutionContext(
            $tenantId,
            fn () => app(InvoiceController::class)->signedPrintLink(
                $this->requestWithScope($tenantId, $organizationUnitId),
                (int) $invoice->getKey(),
            ),
        );
        $duplicateUrl = $duplicateResponse->getData(true)['data']['print_url'];
        $duplicateHtml = $this->get($duplicateUrl)->assertOk()->getContent();

        $this->assertStringContainsString('layout-outbound-continuous', $duplicateHtml);
        $this->assertStringContainsString('Printed:', $duplicateHtml);
        $this->assertStringContainsString('By: admin tenant | Duplicate', $duplicateHtml);
    }

    public function test_whatsapp_shared_invoice_pdf_expires_and_respects_current_lifecycle(): void
    {
        [$tenantId, $organizationUnitId] = $this->scope();
        $customerId = $this->customer($tenantId, $organizationUnitId, 'WhatsApp Share Customer');
        $invoice = $this->printedInvoice($tenantId, $organizationUnitId, $customerId);
        DB::table('invoices')->where('id', $invoice->getKey())->update(['status' => 'posted']);

        $parameters = [
            'invoice' => (int) $invoice->getKey(),
            'tenant' => $tenantId,
            'organization_unit' => $organizationUnitId,
        ];
        $validUrl = URL::temporarySignedRoute('invoices.public.shared-pdf', now()->addMinutes(5), $parameters);
        $response = $this->get($validUrl)->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());

        DB::table('invoices')->where('id', $invoice->getKey())->update(['status' => 'cancelled']);
        $this->get($validUrl)->assertNotFound();

        $expiredUrl = URL::temporarySignedRoute('invoices.public.shared-pdf', now()->subMinute(), $parameters);
        $this->get($expiredUrl)->assertForbidden();
    }

    private function printedInvoice(
        int $tenantId,
        int $organizationUnitId,
        int $customerId,
        InvoiceType $invoiceType = InvoiceType::Manual,
    ): Invoice {
        return $this->withTenantExecutionContext(
            $tenantId,
            fn (): Invoice => app(InvoiceCreationService::class)->create(new CreateInvoiceData(
                tenantId: $tenantId,
                invoiceType: $invoiceType,
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
