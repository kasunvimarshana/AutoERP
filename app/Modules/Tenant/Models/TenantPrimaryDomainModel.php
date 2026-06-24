<?php

declare(strict_types=1);

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;

final class TenantPrimaryDomainModel extends TenantOwnedModel
{
    protected $table = 'tenant_primary_domains';

    protected $primaryKey = 'tenant_id';

    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'tenant_domain_id',
        'row_version',
        'updated_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'tenant_domain_id' => 'integer',
        ]);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(TenantDomainModel::class, 'tenant_domain_id');
    }
}
