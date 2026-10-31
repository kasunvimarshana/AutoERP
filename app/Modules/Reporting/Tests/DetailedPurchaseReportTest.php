<?php

declare(strict_types=1);

namespace Modules\Reporting\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Purchase\Enums\GoodsReceiptNoteLineStatus;
use Modules\Purchase\Enums\GoodsReceiptNoteStatus;
use Modules\Reporting\Exporters\CsvExporter;
use Modules\Reporting\Exporters\HtmlExporter;
use Modules\Reporting\Exporters\PdfExporter;
use Modules\Reporting\Services\DetailedPurchaseReportService;
use Tests\TestCase;

final class DetailedPurchaseReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_keeps_purchase_orders_complete_and_counts_only_posted_grn_cost(): void
    {
        $tenant = $this->tenant();
        $supplier = $this->supplier($tenant);
        $warehouse = $this->warehouse($tenant);
        $pieces = $this->uom($tenant, 'PCS');
        $litres = $this->uom($tenant, 'LTR');
        $lkr = $this->currency('LKR');
        $usd = $this->currency('USD');
        $brake = $this->item($tenant, 'BRAKE', 'Brake oil');
        $coolant = $this->item($tenant, 'COOLANT', 'Radiator coolant');
        $water = $this->item($tenant, 'WATER', 'Battery water');
        $firstOrder = $this->order($tenant, $supplier, $warehouse, $lkr, 'PO-001', '2026-10-05', '1500');
        DB::table('purchase_orders')->where('id', $firstOrder)->update(['approved_by' => $this->approver($tenant)]);
        $secondOrder = $this->order($tenant, $supplier, $warehouse, $usd, 'PO-002', '2026-10-06', '90');
        $brakeLine = $this->line($tenant, $firstOrder, $brake, $pieces, 1, '5', '203.39');
        $coolantLine = $this->line($tenant, $firstOrder, $coolant, $pieces, 2, '2', '100');
        $this->line($tenant, $secondOrder, $coolant, $pieces, 1, '3', '20');
        $this->line($tenant, $secondOrder, $water, $litres, 2, '5', '6');
        $posted = $this->grn($tenant, $firstOrder, $warehouse, 'GRN-POSTED', GoodsReceiptNoteStatus::Posted);
        $this->grnLine($tenant, $posted, $brakeLine, $brake, $pieces, '3', '203.39', GoodsReceiptNoteLineStatus::Posted);
        $this->grnLine($tenant, $posted, $coolantLine, $coolant, $pieces, '2', '100', GoodsReceiptNoteLineStatus::Posted);
        $reversed = $this->grn($tenant, $firstOrder, $warehouse, 'GRN-REVERSED', GoodsReceiptNoteStatus::Reversed);
        $this->grnLine($tenant, $reversed, $brakeLine, $brake, $pieces, '2', '203.39', GoodsReceiptNoteLineStatus::Reversed);
        $draft = $this->grn($tenant, $firstOrder, $warehouse, 'GRN-DRAFT', GoodsReceiptNoteStatus::Draft);
        $this->grnLine($tenant, $draft, $brakeLine, $brake, $pieces, '1', '203.39', GoodsReceiptNoteLineStatus::Open);

        $service = app(DetailedPurchaseReportService::class);
        $params = ['tenant_id' => $tenant, 'organization_unit_id' => null, 'page' => 1, 'per_page' => 1];
        $firstPage = $service->run($params);

        self::assertSame(2, $firstPage['meta']['total']);
        self::assertCount(1, $firstPage['data']);
        self::assertCount(2, $firstPage['data'][0]['items']);
        self::assertSame('PO-001', $firstPage['data'][0]['purchase_order_number']);
        self::assertSame(['label' => 'Approved by', 'actor' => ['name' => 'Nimali Perera']], $firstPage['data'][0]['status_action']);
        self::assertSame('3.000000', $firstPage['data'][0]['items'][0]['grn_quantity']);
        self::assertSame('610.170000', $firstPage['data'][0]['items'][0]['grn_cost']);
        self::assertSame('810.170000', $firstPage['data'][0]['grn_cost']);
        self::assertSame('1500.000000', $firstPage['data'][0]['po_amount']);
        self::assertSame(2, $firstPage['summary']['total_orders']);
        self::assertCount(2, $firstPage['summary']['currencies']);
        self::assertSame('7.000000', $firstPage['summary']['suppliers'][0]['ordered_quantity']);
        self::assertSame('5.000000', $firstPage['summary']['suppliers'][0]['grn_quantity']);
        self::assertNull($firstPage['summary']['suppliers'][1]['ordered_quantity']);

        $secondPage = $service->run([...$params, 'page' => 2]);
        self::assertSame('PO-002', $secondPage['data'][0]['purchase_order_number']);
        self::assertSame(['label' => 'Approved by', 'actor' => null], $secondPage['data'][0]['status_action']);
        self::assertNull($secondPage['data'][0]['ordered_quantity']);
        self::assertNull($secondPage['data'][0]['grn_quantity']);

        $itemSearch = $service->run([...$params, 'search' => 'Brake']);
        self::assertSame(1, $itemSearch['meta']['total']);
        self::assertCount(2, $itemSearch['data'][0]['items']);
        self::assertSame('810.170000', $itemSearch['summary']['currencies'][0]['grn_cost']);

        $dateFilter = $service->run([...$params, 'date_from' => '2026-10-06']);
        self::assertSame(1, $dateFilter['meta']['total']);
        self::assertSame('PO-002', $dateFilter['data'][0]['purchase_order_number']);

        $document = $service->exportData($params);
        $html = app(HtmlExporter::class)->render($document);
        self::assertStringContainsString('PO-001 total', $html);
        self::assertStringContainsString('PO-002 total (mixed units)', $html);
        self::assertStringContainsString('Approved by: Nimali Perera', $html);
        self::assertStringContainsString('Approved by: Not recorded', $html);
        self::assertStringContainsString('R F Marketing Services total', $html);
        self::assertStringContainsString('Report total (LKR) (PCS)', $html);

        $csv = app(CsvExporter::class)->export($document)->getContent();
        self::assertIsString($csv);
        self::assertStringContainsString('Report total (LKR) (PCS)', $csv);
        self::assertStringContainsString('Approved by: Nimali Perera', $csv);

        $pdf = app(PdfExporter::class)->export($document);
        self::assertSame(200, $pdf->getStatusCode());
        self::assertStringStartsWith('%PDF-', (string) $pdf->getContent());
    }

    public function test_it_displays_the_actor_for_the_current_purchase_order_status(): void
    {
        $tenant = $this->tenant();
        $supplier = $this->supplier($tenant);
        $warehouse = $this->warehouse($tenant);
        $currency = $this->currency('LKR');
        $approver = $this->approver($tenant);
        $canceller = $this->approver($tenant, 'Kamal', 'Silva');
        $statuses = [
            ['PO-DRAFT', 'draft', 'created_by', 'Created by', $approver],
            ['PO-PENDING', 'pending_approval', 'submitted_by', 'Submitted by', $approver],
            ['PO-APPROVED', 'approved', 'approved_by', 'Approved by', $approver],
            ['PO-CLOSED', 'closed', 'closed_by', 'Closed by', $approver],
            ['PO-CANCELLED', 'cancelled', 'cancelled_by', 'Cancelled by', $canceller],
            ['PO-OLD-CANCELLED', 'cancelled', 'cancelled_by', 'Cancelled by', null],
        ];

        foreach ($statuses as [$number, $status, $actorColumn, $label, $actorId]) {
            $orderId = $this->order($tenant, $supplier, $warehouse, $currency, $number, '2026-10-05', '100');
            $values = [
                'status' => $status,
                $actorColumn => $actorId,
            ];
            if ($status === 'cancelled') {
                $values['approved_by'] = $approver;
            }
            DB::table('purchase_orders')->where('id', $orderId)->update($values);
        }

        $service = app(DetailedPurchaseReportService::class);
        $params = ['tenant_id' => $tenant, 'organization_unit_id' => null, 'page' => 1, 'per_page' => 25];
        $rows = collect($service->run($params)['data'])->keyBy('purchase_order_number');

        foreach ($statuses as [$number, , , $label, $actorId]) {
            self::assertSame($label, $rows[$number]['status_action']['label']);
            self::assertSame(
                $actorId === null ? null : ['name' => $number === 'PO-CANCELLED' ? 'Kamal Silva' : 'Nimali Perera'],
                $rows[$number]['status_action']['actor'],
            );
        }

        $csv = app(CsvExporter::class)->export($service->exportData($params))->getContent();
        self::assertIsString($csv);
        self::assertStringContainsString('PO-CANCELLED | 2026-10-05 | Cancelled | Cancelled by: Kamal Silva', $csv);
        self::assertStringContainsString('PO-OLD-CANCELLED | 2026-10-05 | Cancelled | Cancelled by: Not recorded', $csv);
        self::assertStringNotContainsString('PO-CANCELLED | 2026-10-05 | Cancelled | Approved by:', $csv);
    }

    private function tenant(): int
    {
        $now = now();

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-POD-'.Str::upper(Str::random(6)),
            'name' => 'Purchase detail tenant',
            'slug' => 'purchase-detail-'.Str::lower(Str::random(6)),
            'status' => 'active',
            'status_changed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function supplier(int $tenant): int
    {
        return (int) DB::table('suppliers')->insertGetId([
            'tenant_id' => $tenant,
            'supplier_number' => 'SUP-POD',
            'code' => 'SUP-POD',
            'name' => 'R F Marketing Services',
            'supplier_type' => 'company',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function approver(int $tenant, string $firstName = 'Nimali', string $lastName = 'Perera'): int
    {
        return (int) DB::table('users')->insertGetId([
            'tenant_id' => $tenant,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => strtolower($firstName).'@example.test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function warehouse(int $tenant): int
    {
        return (int) DB::table('warehouses')->insertGetId([
            'tenant_id' => $tenant,
            'name' => 'Main',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function currency(string $code): int
    {
        return (int) DB::table('currencies')->insertGetId([
            'code' => $code,
            'name' => $code,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function uom(int $tenant, string $code): int
    {
        return (int) DB::table('unit_of_measures')->insertGetId([
            'tenant_id' => $tenant,
            'code' => $code,
            'name' => $code,
            'symbol' => $code,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function item(int $tenant, string $code, string $name): int
    {
        return (int) DB::table('items')->insertGetId([
            'tenant_id' => $tenant,
            'code' => $code,
            'name' => $name,
            'item_type' => 'product',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function order(int $tenant, int $supplier, int $warehouse, int $currency, string $number, string $date, string $amount): int
    {
        return (int) DB::table('purchase_orders')->insertGetId([
            'tenant_id' => $tenant,
            'supplier_id' => $supplier,
            'warehouse_id' => $warehouse,
            'currency_id' => $currency,
            'purchase_order_number' => $number,
            'purchase_order_date' => $date,
            'status' => 'approved',
            'grand_total' => $amount,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function line(int $tenant, int $order, int $item, int $uom, int $number, string $quantity, string $price): int
    {
        return (int) DB::table('purchase_order_lines')->insertGetId([
            'tenant_id' => $tenant,
            'purchase_order_id' => $order,
            'line_number' => $number,
            'item_id' => $item,
            'uom_id' => $uom,
            'ordered_quantity' => $quantity,
            'remaining_quantity' => $quantity,
            'unit_price' => $price,
            'line_total' => '0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function grn(int $tenant, int $order, int $warehouse, string $number, GoodsReceiptNoteStatus $status): int
    {
        return (int) DB::table('goods_receipt_notes')->insertGetId([
            'tenant_id' => $tenant,
            'purchase_order_id' => $order,
            'warehouse_id' => $warehouse,
            'grn_number' => $number,
            'received_date' => '2026-10-08',
            'status' => $status->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function grnLine(int $tenant, int $grn, int $orderLine, int $item, int $uom, string $quantity, string $price, GoodsReceiptNoteLineStatus $status): void
    {
        DB::table('goods_receipt_note_lines')->insert([
            'tenant_id' => $tenant,
            'goods_receipt_note_id' => $grn,
            'purchase_order_line_id' => $orderLine,
            'item_id' => $item,
            'uom_id' => $uom,
            'received_quantity' => $quantity,
            'accepted_quantity' => $quantity,
            'remaining_quantity' => $quantity,
            'unit_price' => $price,
            'line_total' => '0',
            'status' => $status->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
