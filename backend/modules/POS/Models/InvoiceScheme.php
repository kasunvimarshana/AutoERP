<?php

declare(strict_types=1);

namespace Modules\POS\Models;

use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\BelongsToTenant;

/**
 * Invoice Scheme Model
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property string $scheme_type
 * @property string|null $prefix
 * @property int $start_number
 * @property int $total_digits
 * @property bool $is_default
 */
class InvoiceScheme extends BaseModel
{
    use BelongsToTenant;

    protected $table = 'pos_invoice_schemes';

    protected $fillable = [
        'tenant_id',
        'name',
        'scheme_type',
        'prefix',
        'start_number',
        'total_digits',
        'is_default',
    ];

    protected $casts = [
        'start_number' => 'integer',
        'total_digits' => 'integer',
        'is_default' => 'boolean',
    ];

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
