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
use Modules\Reporting\Services\GoodsReceiptSummaryReportService;
use Tests\Support\OrganizationUnitFixture;
use Tests\TestCase;

final class GoodsReceiptSummaryReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_groups_posted_receipts_and_uses_full_filtered_totals(): void
    {
        $currency = (int) DB::table('currencies')->insertGetId([
            'code' => 'LKR', 'name' => 'Sri Lankan Rupee', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $tenant = $this->tenant($currency);
        $main = OrganizationUnitFixture::create(['tenant_id' => $tenant, 'code' => 'MAIN', 'name' => 'Main']);
        $branch = OrganizationUnitFixture::create(['tenant_id' => $tenant, 'code' => 'KOSWATTA', 'name' => 'Koswatta']);
        $supplier = (int) DB::table('suppliers')->insertGetId([
            'tenant_id' => $tenant, 'supplier_number' => 'SUP-1', 'code' => 'SUP-1', 'name' => 'Semini Motors',
            'supplier_type' => 'company', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $warehouse = (int) DB::table('warehouses')->insertGetId([
            'tenant_id' => $tenant, 'name' => 'Main Warehouse', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $item = (int) DB::table('items')->insertGetId([
            'tenant_id' => $tenant, 'code' => 'ITEM-1', 'name' => 'Oil', 'item_type' => 'product', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $pieces = $this->uom($tenant, 'PCS');
        $litres = $this->uom($tenant, 'LTR');
        $order = (int) DB::table('purchase_orders')->insertGetId([
            'tenant_id' => $tenant, 'organization_unit_id' => $main, 'supplier_id' => $supplier, 'warehouse_id' => $warehouse,
            'currency_id' => $currency, 'purchase_order_number' => 'PO-1', 'purchase_order_date' => '2026-10-04',
            'status' => 'approved', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $first = $this->receipt($tenant, $main, $warehouse, $supplier, 'GRN-1', '2026-10-05', GoodsReceiptNoteStatus::Posted, 'Supplier note 0299022', $order);
        $second = $this->receipt($tenant, $main, $warehouse, $supplier, 'GRN-2', '2026-10-06', GoodsReceiptNoteStatus::Posted, 'Handle carefully');
        $third = $this->receipt($tenant, $branch, $warehouse, $supplier, 'GRN-3', '2026-10-07', GoodsReceiptNoteStatus::Posted, 'Branch receipt');
        $draft = $this->receipt($tenant, $branch, $warehouse, $supplier, 'GRN-DRAFT', '2026-10-08', GoodsReceiptNoteStatus::Draft, 'Ignore');
        $reversed = $this->receipt($tenant, $branch, $warehouse, $supplier, 'GRN-REVERSED', '2026-10-09', GoodsReceiptNoteStatus::Reversed, 'Ignore');
        $this->line($tenant, $main, $first, $item, $pieces, '3', '2', '100', GoodsReceiptNoteLineStatus::Posted);
        $this->line($tenant, $main, $second, $item, $litres, '1', '1', '20', GoodsReceiptNoteLineStatus::Posted);
        $this->line($tenant, $branch, $third, $item, $pieces, '4', '4', '5', GoodsReceiptNoteLineStatus::Posted);
        $this->line($tenant, $branch, $draft, $item, $pieces, '10', '10', '100', GoodsReceiptNoteLineStatus::Open);
        $this->line($tenant, $branch, $reversed, $item, $pieces, '10', '10', '100', GoodsReceiptNoteLineStatus::Reversed);

        $service = app(GoodsReceiptSummaryReportService::class);
        $params = ['tenant_id' => $tenant, 'organization_unit_id' => null, 'page' => 1, 'per_page' => 1];
        $firstPage = $service->run($params);

        self::assertSame(3, $firstPage['summary']['total_receipts']);
        self::assertSame(3, $firstPage['meta']['total']);
        self::assertCount(1, $firstPage['data']);
        self::assertSame('GRN-3', $firstPage['data'][0]['grn_number']);
        self::assertSame('4.000000', $firstPage['data'][0]['accepted_quantity']);
        self::assertSame('20.000000', $firstPage['data'][0]['total_cost']);
        self::assertCount(2, $firstPage['summary']['locations']);
        $mainTotal = collect($firstPage['summary']['locations'])->firstWhere('location_id', $main);
        self::assertNotNull($mainTotal);
        self::assertSame('220.000000', $mainTotal['total_cost']);
        self::assertNull($mainTotal['accepted_quantity']);
        self::assertSame('240.000000', $firstPage['summary']['currencies'][0]['total_cost']);
        self::assertNull($firstPage['summary']['currencies'][0]['accepted_quantity']);

        $filtered = $service->run([...$params, 'organization_unit_id' => $main, 'date_to' => '2026-10-05']);
        self::assertSame(1, $filtered['summary']['total_receipts']);
        self::assertSame('GRN-1', $filtered['data'][0]['grn_number']);
        self::assertSame('Supplier note 0299022', $filtered['data'][0]['note']);
        self::assertSame(['id' => $order, 'number' => 'PO-1'], $filtered['data'][0]['purchase_order']);
        self::assertSame('2.000000', $filtered['summary']['currencies'][0]['accepted_quantity']);

        $document = $service->exportData($params);
        $html = app(HtmlExporter::class)->render($document);
        self::assertStringContainsString('Supplier note 0299022', $html);
        self::assertStringContainsString('Mixed units', $html);
        self::assertStringContainsString('Grand total (LKR)', $html);
        $csv = app(CsvExporter::class)->export($document)->getContent();
        self::assertIsString($csv);
        self::assertStringContainsString('GRN-1', $csv);
        self::assertStringContainsString('PO-1', $csv);
        self::assertStringNotContainsString('GRN-DRAFT', $csv);
        self::assertStringNotContainsString('GRN-REVERSED', $csv);
    }

    private function tenant(int $currency): int
    {
        $suffix = Str::lower(Str::random(6));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(), 'code' => 'TEN-GRS-'.Str::upper($suffix), 'name' => 'GRN Summary',
            'slug' => 'grn-summary-'.$suffix, 'base_currency_id' => $currency, 'status' => 'active',
            'status_changed_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function uom(int $tenant, string $code): int
    {
        return (int) DB::table('unit_of_measures')->insertGetId([
            'tenant_id' => $tenant, 'code' => $code, 'name' => $code, 'symbol' => $code,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function receipt(int $tenant, int $organization, int $warehouse, int $supplier, string $number, string $date, GoodsReceiptNoteStatus $status, string $note, ?int $order = null): int
    {
        return (int) DB::table('goods_receipt_notes')->insertGetId([
            'tenant_id' => $tenant, 'organization_unit_id' => $organization, 'purchase_order_id' => $order,
            'supplier_type' => 'supplier', 'supplier_id' => $supplier, 'warehouse_id' => $warehouse,
            'grn_number' => $number, 'received_date' => $date, 'status' => $status->value, 'notes' => $note,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function line(int $tenant, int $organization, int $receipt, int $item, int $uom, string $received, string $accepted, string $price, GoodsReceiptNoteLineStatus $status): void
    {
        DB::table('goods_receipt_note_lines')->insert([
            'tenant_id' => $tenant, 'organization_unit_id' => $organization, 'goods_receipt_note_id' => $receipt,
            'item_id' => $item, 'uom_id' => $uom, 'received_quantity' => $received,
            'accepted_quantity' => $accepted, 'remaining_quantity' => $accepted, 'unit_price' => $price,
            'line_total' => '0', 'status' => $status->value, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
