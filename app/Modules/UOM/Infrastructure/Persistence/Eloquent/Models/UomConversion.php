<?php

declare(strict_types=1);

namespace Modules\UOM\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization as HasTenantAndOrganizationTrait;

class UomConversion extends Model
{
    use HasTenantAndOrganizationTrait;
    use SoftDeletes;

    protected $table = 'uom_conversions';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'factor' => 'decimal:4',
            'is_bidirectional' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function fromUom(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\UOM\\Infrastructure\\Persistence\\Eloquent\\Models\\UnitOfMeasure',
            'from_uom_id'
        );
    }

    public function toUom(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\UOM\\Infrastructure\\Persistence\\Eloquent\\Models\\UnitOfMeasure',
            'to_uom_id'
        );
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Item\\Infrastructure\\Persistence\\Eloquent\\Models\\Item',
            'item_id'
        );
    }
}
