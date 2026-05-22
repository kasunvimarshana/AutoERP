<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Domain\Enums\StockTransferStatus;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class StockTransfer extends Model
{
    use HasTenantAndOrganization;

    protected $table = 'stock_transfers';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'status' => StockTransferStatus::class,
            'transferred_at' => 'datetime',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany('Modules\\Inventory\\Infrastructure\\Persistence\\Eloquent\\Models\\StockTransferLine', 'stock_transfer_id');
    }
}
