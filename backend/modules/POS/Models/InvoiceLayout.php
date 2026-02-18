<?php

declare(strict_types=1);

namespace Modules\POS\Models;

use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\BelongsToTenant;

/**
 * Invoice Layout Model
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property string|null $header_text
 * @property string|null $footer_text
 * @property bool $show_logo
 * @property bool $show_barcode
 * @property bool $show_customer
 * @property array|null $custom_fields
 * @property bool $is_default
 */
class InvoiceLayout extends BaseModel
{
    use BelongsToTenant;

    protected $table = 'pos_invoice_layouts';

    protected $fillable = [
        'tenant_id',
        'name',
        'header_text',
        'footer_text',
        'show_logo',
        'show_barcode',
        'show_customer',
        'custom_fields',
        'is_default',
    ];

    protected $casts = [
        'show_logo' => 'boolean',
        'show_barcode' => 'boolean',
        'show_customer' => 'boolean',
        'custom_fields' => 'array',
        'is_default' => 'boolean',
    ];

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
