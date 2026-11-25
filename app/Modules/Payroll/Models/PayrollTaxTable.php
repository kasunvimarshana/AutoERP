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

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Payroll\Models\Concerns\ScopesPayrollTenant;

final class PayrollTaxTable extends TenantOwnedModel
{
    use ScopesPayrollTenant;

    protected $table = 'payroll_tax_tables';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'effective_year' => 'integer',
            'standard_personal_relief' => 'decimal:6',
            'is_active' => 'boolean',
        ]);
    }

    public function brackets(): HasMany
    {
        return $this->hasMany(PayrollTaxBracket::class, 'tax_table_id')->orderBy('bracket_order');
    }
}
