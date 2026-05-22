<?php

declare(strict_types=1);

namespace Modules\UOM\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\UOM\Domain\Enums\UomType;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class UnitOfMeasure extends Model
{
    use HasTenantAndOrganization;
    use SoftDeletes;

    protected $table = 'unit_of_measures';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'type' => UomType::class,
            'is_base' => 'boolean',
        ];
    }

    #[Scope]
    protected function base(Builder $query): void
    {
        $query->where('is_base', true);
    }

    public function fromConversions(): HasMany
    {
        return $this->hasMany(
            'Modules\\UOM\\Infrastructure\\Persistence\\Eloquent\\Models\\UomConversion',
            'from_uom_id'
        );
    }

    public function toConversions(): HasMany
    {
        return $this->hasMany(
            'Modules\\UOM\\Infrastructure\\Persistence\\Eloquent\\Models\\UomConversion',
            'to_uom_id'
        );
    }
}
