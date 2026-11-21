<?php

declare(strict_types=1);

namespace Modules\Supplier\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Supplier\Enums\SupplierStatus;

final class SupplierStatusHistory extends TenantOwnedModel
{
    protected $table = 'supplier_status_histories';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        self::updating(static function (): void {
            throw new LogicException('SupplierStatusHistory is an immutable ledger and cannot be updated.');
        });

        self::deleting(static function (): void {
            throw new LogicException('SupplierStatusHistory is an immutable ledger and cannot be deleted.');
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'supplier_id' => 'integer',
            'old_status' => SupplierStatus::class,
            'new_status' => SupplierStatus::class,
            'changed_by' => 'integer',
            'changed_at' => 'datetime',
        ]);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
