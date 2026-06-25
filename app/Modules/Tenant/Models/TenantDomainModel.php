<?php

declare(strict_types=1);

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Tenant\Services\Domains\TenantDomainReadinessPolicy;

final class TenantDomainModel extends TenantOwnedModel
{
    protected static function booted(): void
    {
        static::saving(static function (self $model): void {
            (new TenantDomainReadinessPolicy())->assertValid($model->getAttributes());
        });
    }

    protected $table = 'tenant_domains';

    protected $fillable = [
        'tenant_id',
        'domain',
        'status',
        'ownership_status',
        'routing_status',
        'tls_status',
        'reachability_status',
        'operational_status',
        'verification_method',
        'verification_token_hash',
        'verified_token_hash',
        'verification_expires_at',
        'verified_at',
        'verified_by',
        'last_verification_attempt_at',
        'last_verified_at',
        'verification_failure_count',
        'verification_error_code',
        'verification_error_message',
        'revalidation_due_at',
        'verification_grace_expires_at',
        'operational_probe_token',
        'operational_probe_token_hash',
        'last_operational_check_at',
        'operational_retry_at',
        'tls_expires_at',
        'operational_error_code',
        'operational_error_message',
        'operational_claim_token',
        'operational_claimed_at',
        'operational_claim_lease_expires_at',
        'revalidation_claim_token',
        'revalidation_claimed_at',
        'revalidation_claim_lease_expires_at',
        'row_version',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'verification_token_hash',
        'verified_token_hash',
        'operational_probe_token',
        'operational_probe_token_hash',
        'operational_claim_token',
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
            'operational_probe_token' => 'encrypted',
            'last_operational_check_at' => 'datetime',
            'operational_retry_at' => 'datetime',
            'tls_expires_at' => 'datetime',
            'operational_claimed_at' => 'datetime',
            'operational_claim_lease_expires_at' => 'datetime',
            'revalidation_claimed_at' => 'datetime',
            'revalidation_claim_lease_expires_at' => 'datetime',
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
