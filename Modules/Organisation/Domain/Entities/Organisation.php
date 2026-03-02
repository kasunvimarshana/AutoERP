<?php

declare(strict_types=1);

namespace Modules\Organisation\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * Organisation entity.
 *
 * Represents an operational entity within a tenant.
 * A tenant may have multiple organisations.
 */
class Organisation extends Model
{
    use HasTenant;

    protected $table = 'organisations';

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

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }
}
