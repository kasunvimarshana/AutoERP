<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * AccountType entity.
 *
 * Represents a category of accounts (Asset, Liability, Equity, Revenue, Expense).
 */
class AccountType extends Model
{
    use HasTenant;

    protected $table = 'account_types';

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
