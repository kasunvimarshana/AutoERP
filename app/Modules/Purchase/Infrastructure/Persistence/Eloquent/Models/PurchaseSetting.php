<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class PurchaseSetting extends CoreModel
{
    use SoftDeletes;

    protected $table = 'purchase_settings';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'require_po_before_grn' => 'boolean',
            'require_grn_before_invoice' => 'boolean',
            'allow_direct_grn' => 'boolean',
            'allow_direct_purchase_invoice' => 'boolean',
            'allow_return_without_original' => 'boolean',
            'allow_negative_stock_on_return' => 'boolean',
            'allow_header_discount' => 'boolean',
            'allow_line_discount' => 'boolean',
            'is_active' => 'boolean',
        ]);
    }
}
