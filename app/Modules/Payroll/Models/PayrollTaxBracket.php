<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Payroll\Models\Concerns\ScopesPayrollTenant;

final class PayrollTaxBracket extends TenantOwnedModel
{
    use ScopesPayrollTenant;

    protected $table = 'payroll_tax_brackets';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'tax_table_id' => 'integer',
            'bracket_order' => 'integer',
            'threshold_from' => 'decimal:4',
            'threshold_to' => 'decimal:4',
            'rate_percentage' => 'decimal:2',
        ]);
    }

    public function taxTable(): BelongsTo
    {
        return $this->belongsTo(PayrollTaxTable::class, 'tax_table_id');
    }
}
