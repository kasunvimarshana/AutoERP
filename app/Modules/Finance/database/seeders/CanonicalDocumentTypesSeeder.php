<?php

namespace App\Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CanonicalDocumentTypesSeeder extends Seeder
{
    public function run(): void
    {
        $documentTypes = [
            ['type_code' => 'sales_quote', 'type_name' => 'Sales Quote', 'category_code' => 'sales', 'affects_inventory' => false, 'affects_subledger' => false],
            ['type_code' => 'sales_order', 'type_name' => 'Sales Order', 'category_code' => 'sales', 'affects_inventory' => false, 'affects_subledger' => false],
            ['type_code' => 'shipment', 'type_name' => 'Shipment', 'category_code' => 'sales', 'affects_inventory' => true, 'affects_subledger' => false],
            ['type_code' => 'sales_invoice', 'type_name' => 'Sales Invoice', 'category_code' => 'sales', 'affects_inventory' => false, 'affects_subledger' => true],
            ['type_code' => 'sales_return', 'type_name' => 'Sales Return', 'category_code' => 'sales', 'affects_inventory' => true, 'affects_subledger' => true],
            ['type_code' => 'purchase_order', 'type_name' => 'Purchase Order', 'category_code' => 'purchase', 'affects_inventory' => false, 'affects_subledger' => false],
            ['type_code' => 'goods_receipt', 'type_name' => 'Goods Receipt', 'category_code' => 'purchase', 'affects_inventory' => true, 'affects_subledger' => false],
            ['type_code' => 'purchase_invoice', 'type_name' => 'Purchase Invoice', 'category_code' => 'purchase', 'affects_inventory' => false, 'affects_subledger' => true],
            ['type_code' => 'purchase_return', 'type_name' => 'Purchase Return', 'category_code' => 'purchase', 'affects_inventory' => true, 'affects_subledger' => true],
            ['type_code' => 'service_job', 'type_name' => 'Service Job', 'category_code' => 'service', 'affects_inventory' => false, 'affects_subledger' => false],
            ['type_code' => 'service_invoice', 'type_name' => 'Service Invoice', 'category_code' => 'service', 'affects_inventory' => false, 'affects_subledger' => true],
            ['type_code' => 'inventory_adjustment', 'type_name' => 'Inventory Adjustment', 'category_code' => 'inventory', 'affects_inventory' => true, 'affects_subledger' => false],
            ['type_code' => 'stock_transfer', 'type_name' => 'Stock Transfer', 'category_code' => 'inventory', 'affects_inventory' => true, 'affects_subledger' => false],
            ['type_code' => 'journal_manual', 'type_name' => 'Manual Journal', 'category_code' => 'finance', 'affects_inventory' => false, 'affects_subledger' => false],
        ];

        $tenantIds = DB::table('tenants')->pluck('id');

        foreach ($tenantIds as $tenantId) {
            foreach ($documentTypes as $documentType) {
                DB::table('document_types')->updateOrInsert(
                    [
                        'tenant_id' => $tenantId,
                        'type_code' => $documentType['type_code'],
                    ],
                    array_merge($documentType, [
                        'tenant_id' => $tenantId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }
        }
    }
}
