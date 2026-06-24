<?php

declare(strict_types=1);

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Core\Models\TenantOwnedModel;

final class TenantDomainModel extends TenantOwnedModel
{
    protected $table = 'tenant_domains';

    protected $fillable = [
        'tenant_id',
        'domain',
        'status',
        'verification_method',
        'verification_token_hash',
        'verified_token_hash',
        'verification_expires_at',
        'verified_at',
        'verified_by',
        'last_verification_attempt_at',
        'last_verified_at',
        'verification_failure_count',
        'verification_last_error',
        'revalidation_due_at',
        'verification_grace_expires_at',
        'revalidation_claim_token',
        'revalidation_claimed_at',
        'metadata',
        'row_version',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'verification_token_hash',
        'verified_token_hash',
        'revalidation_claim_token',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'verification_expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'last_verification_attempt_at' => 'datetime',
            'last_verified_at' => 'datetime',
            'verification_failure_count' => 'integer',
            'revalidation_due_at' => 'datetime',
            'verification_grace_expires_at' => 'datetime',
            'revalidation_claimed_at' => 'datetime',
            'metadata' => 'array',
        ]);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function primaryAssignment(): HasOne
    {
        return $this->hasOne(TenantPrimaryDomainModel::class, 'tenant_domain_id');
    }
}
