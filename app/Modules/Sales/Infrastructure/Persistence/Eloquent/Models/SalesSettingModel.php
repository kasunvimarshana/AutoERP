<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class SalesSettingModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'sales_settings';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'default_customer_receivable_account_id' => 'integer',
            'default_sales_income_account_id' => 'integer',
            'default_inventory_account_id' => 'integer',
            'default_cogs_account_id' => 'integer',
            'default_sales_discount_account_id' => 'integer',
            'default_sales_tax_account_id' => 'integer',
            'default_return_account_id' => 'integer',
            'default_rounding_account_id' => 'integer',
            'default_write_off_account_id' => 'integer',
            'default_payment_term_id' => 'integer',
            'default_currency_id' => 'integer',
            'default_warehouse_id' => 'integer',
            'default_price_list_id' => 'integer',
            'default_tax_group_id' => 'integer',
            'sales_order_document_definition_id' => 'integer',
            'gdn_document_definition_id' => 'integer',
            'sales_invoice_document_definition_id' => 'integer',
            'sales_return_document_definition_id' => 'integer',
            'require_sales_order_before_gdn' => 'boolean',
            'require_gdn_before_invoice' => 'boolean',
            'allow_direct_gdn' => 'boolean',
            'allow_direct_sales_invoice' => 'boolean',
            'allow_return_without_original' => 'boolean',
            'reserve_stock_on_order' => 'boolean',
            'issue_stock_on_gdn' => 'boolean',
            'issue_stock_on_invoice' => 'boolean',
            'allow_header_discount' => 'boolean',
            'allow_line_discount' => 'boolean',
            'default_sales_order_status' => 'string',
            'default_gdn_status' => 'string',
            'default_sales_invoice_status' => 'string',
            'default_sales_return_status' => 'string',
            'is_active' => 'boolean',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ]);
    }
}
