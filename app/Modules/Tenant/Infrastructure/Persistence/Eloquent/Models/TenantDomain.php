<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Concerns\HasTenant;

class TenantDomain extends Model
{
    use HasTenant;

    protected $table = 'tenant_domains';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'is_primary' => 'boolean',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Tenant\\Infrastructure\\Persistence\\Eloquent\\Models\\Tenant',
            'tenant_id'
        );
    }

    #[Scope]
    protected function primary(Builder $query): void
    {
        $query->where('is_primary', true);
    }

    #[Scope]
    protected function verified(Builder $query): void
    {
        $query->where('is_verified', true);
    }
}
