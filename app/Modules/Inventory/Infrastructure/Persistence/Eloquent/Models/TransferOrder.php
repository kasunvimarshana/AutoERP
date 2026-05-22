<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Domain\Enums\TransferOrderStatus;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class TransferOrder extends Model
{
    use HasTenantAndOrganization;

    protected $table = 'transfer_orders';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'status' => TransferOrderStatus::class,
            'request_date' => 'date',
            'expected_date' => 'date',
            'shipped_date' => 'date',
            'received_date' => 'date',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany('Modules\\Inventory\\Infrastructure\\Persistence\\Eloquent\\Models\\TransferOrderLine', 'transfer_order_id');
    }
}
