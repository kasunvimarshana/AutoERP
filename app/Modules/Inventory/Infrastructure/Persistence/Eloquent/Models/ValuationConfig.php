<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class ValuationConfig extends Model
{
    use HasTenantAndOrganization;

    protected $table = 'valuation_configs';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Item\\Infrastructure\\Persistence\\Eloquent\\Models\\Item',
            'item_id'
        );
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Item\\Infrastructure\\Persistence\\Eloquent\\Models\\ItemVariant',
            'variant_id'
        );
    }
}
