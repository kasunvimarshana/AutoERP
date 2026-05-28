<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class PurchaseSettingModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'purchase_settings';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'default_supplier_payable_account_id' => 'integer',
            'default_purchase_account_id' => 'integer',
            'default_inventory_account_id' => 'integer',
            'default_purchase_discount_account_id' => 'integer',
            'default_purchase_tax_account_id' => 'integer',
            'default_freight_account_id' => 'integer',
            'default_return_account_id' => 'integer',
            'default_rounding_account_id' => 'integer',
            'default_write_off_account_id' => 'integer',
            'default_payment_term_id' => 'integer',
            'default_currency_id' => 'integer',
            'default_warehouse_id' => 'integer',
            'default_price_list_id' => 'integer',
            'default_tax_group_id' => 'integer',
            'purchase_order_document_definition_id' => 'integer',
            'grn_document_definition_id' => 'integer',
            'purchase_invoice_document_definition_id' => 'integer',
            'purchase_return_document_definition_id' => 'integer',
            'require_po_before_grn' => 'boolean',
            'require_grn_before_invoice' => 'boolean',
            'allow_direct_grn' => 'boolean',
            'allow_direct_purchase_document' => 'boolean',
            'allow_return_without_original' => 'boolean',
            'allow_negative_stock_on_return' => 'boolean',
            'allow_header_discount' => 'boolean',
            'allow_line_discount' => 'boolean',
            'is_active' => 'boolean',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ]);
    }
}
