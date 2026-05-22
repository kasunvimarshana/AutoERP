<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class Batch extends Model
{
    use HasTenantAndOrganization;

    protected $table = 'batches';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'manufacture_date' => 'date',
            'expiry_date' => 'date',
            'received_date' => 'date',
            'unit_cost' => 'decimal:4',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo('Modules\\Item\\Infrastructure\\Persistence\\Eloquent\\Models\\Item', 'item_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo('Modules\\Item\\Infrastructure\\Persistence\\Eloquent\\Models\\ItemVariant', 'variant_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo('Modules\\Supplier\\Infrastructure\\Persistence\\Eloquent\\Models\\Supplier', 'supplier_id');
    }
}
