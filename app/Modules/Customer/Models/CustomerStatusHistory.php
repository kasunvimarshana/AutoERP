<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Customer\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Customer\Enums\CustomerStatus;

final class CustomerStatusHistory extends TenantOwnedModel
{
    protected $table = 'customer_status_histories';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        self::updating(static function (): void {
            throw new LogicException('CustomerStatusHistory is an immutable ledger and cannot be updated.');
        });

        self::deleting(static function (): void {
            throw new LogicException('CustomerStatusHistory is an immutable ledger and cannot be deleted.');
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'customer_id' => 'integer',
            'old_status' => CustomerStatus::class,
            'new_status' => CustomerStatus::class,
            'changed_by' => 'integer',
            'changed_at' => 'datetime',
        ]);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
