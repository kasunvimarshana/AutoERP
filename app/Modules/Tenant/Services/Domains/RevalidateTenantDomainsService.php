<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Domains;

use DateTimeImmutable;
use Modules\Audit\Constants\AuditActorType;
use Modules\Audit\Constants\AuditEventCategory;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Audit\Data\SystemAuditEventData;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\Contracts\TransactionManagerInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Tenant\Constants\TenantStatus;
use Modules\Tenant\Jobs\VerifyTenantDomainOperationalReadiness;
use Modules\Tenant\Repositories\TenantDomainRepositoryInterface;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Services\Contracts\TenantDomainOwnershipVerifierInterface;
use Modules\Tenant\Services\TenantLifecycleService;
use RuntimeException;
use Throwable;

final class RevalidateTenantDomainsService
{
    private const JOB_ACTOR_ID = 'tenant-domain-revalidation';
    private const PRIMARY_DOMAIN_LOST_REASON = 'Primary domain ownership verification failed and no verified fallback domain is available.';

    public function __construct(
        private readonly TenantDomainRepositoryInterface $domains,
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantDomainOwnershipVerifierInterface $verifier,
        private readonly TenantLifecycleService $lifecycle,
        private readonly AuditRecorderInterface $audit,
        private readonly ClockInterface $clock,
        private readonly TransactionManagerInterface $transactions,
        private readonly TenantExecutionContextInterface $executionContext,
    ) {}

    /** @return array{checked:int,verified:int,failed:int,disabled:int,suspended:int,conflicted:int,operational_queued:int} */
    public function execute(?int $limit = null): array
    {
        $now = $this->clock->now();
        $batchSize = max(
            1,
            min($limit ?? (int) config('tenant.domains.revalidation_batch_size', 100), 500),
        );
        $claimTimeout = max((int) config('tenant.domains.revalidation_claim_timeout_minutes', 30), 5);
        $claimToken = $this->claimToken();
        $domains = $this->domains->claimDueForRevalidation(
            dueAt: $now,
            claimedAt: $now,
            leaseExpiresAt: $now->modify("+{$claimTimeout} minutes"),
            claimToken: $claimToken,
            limit: $batchSize,
        );
        $summary = [
            'checked' => 0,
            'verified' => 0,
            'failed' => 0,
            'disabled' => 0,
            'suspended' => 0,
            'conflicted' => 0,
            'operational_queued' => 0,
        ];

        foreach ($domains as $domain) {
            $summary['checked']++;
            $tenantId = (int) $domain->require('tenant_id');

            try {
                $result = $this->executionContext->runForTenant(
                    $tenantId,
                    fn (): array => $this->revalidate($domain, $tenantId, $now, $claimToken),
                );

                foreach (['verified', 'failed', 'disabled', 'suspended', 'conflicted'] as $key) {
                    $summary[$key] += $result[$key];
                }
            } catch (Throwable $exception) {
                $summary['failed']++;
                $this->executionContext->runForTenant(
                    $tenantId,
                    fn (): ?DataRecord => $this->domains->releaseRevalidationClaim(
                        $domain->id(),
                        $tenantId,
                        (int) $domain->require('row_version'),
                        $claimToken,
                        'DOMAIN_REVALIDATION_PROCESSING_FAILED',
                        'Domain ownership revalidation could not be completed and will be retried.',
                        $now,
                    ),
                );
                $this->recordProcessingFailure($domain, $tenantId, $exception);
            }
        }

        $operationalClaimToken = $this->claimToken();
        $operationalDomains = $this->domains->claimDueForOperationalVerification(
            dueAt: $now,
            claimedAt: $now,
            leaseExpiresAt: $now->modify("+{$claimTimeout} minutes"),
            claimToken: $operationalClaimToken,
            limit: $batchSize,
        );
        foreach ($operationalDomains as $operationalDomain) {
            VerifyTenantDomainOperationalReadiness::dispatch(
                (int) $operationalDomain->require('tenant_id'),
                (int) $operationalDomain->id(),
            );
            $summary['operational_queued']++;
        }

        return $summary;
    }

    /** @return array{verified:int,failed:int,disabled:int,suspended:int,conflicted:int} */
    private function revalidate(
        DataRecord $domain,
        int $tenantId,
        DateTimeImmutable $now,
        string $claimToken,
    ): array {
        $hash = trim((string) $domain->get('verified_token_hash', ''));
        $result = $this->verifier->verify((string) $domain->require('domain'), $hash);
        $expectedVersion = (int) $domain->require('row_version');

        if ($result->verified) {
            $updated = $this->domains->recordVerificationAttempt(
                $domain->id(),
                $tenantId,
                $expectedVersion,
                true,
                null,
                null,
                $now,
                $this->nextDue($now),
                $this->graceExpiry($now),
            );

            return $updated === null
                ? $this->conflictSummary()
                : ['verified' => 1, 'failed' => 0, 'disabled' => 0, 'suspended' => 0, 'conflicted' => 0];
        }

        if (! $this->graceExpired($domain->get('verification_grace_expires_at'), $now)) {
            $updated = $this->domains->recordVerificationAttempt(
                $domain->id(),
                $tenantId,
                $expectedVersion,
                false,
                $result->errorCode,
                $result->message,
                $now,
            );

            return $updated === null
                ? $this->conflictSummary()
                : ['verified' => 0, 'failed' => 1, 'disabled' => 0, 'suspended' => 0, 'conflicted' => 0];
        }

        return $this->transactions->runInTransaction(function () use (
            $domain,
            $tenantId,
            $expectedVersion,
            $claimToken,
            $result,
            $now,
        ): array {
            $disabled = $this->domains->disableAfterFailedRevalidation(
                $domain->id(),
                $tenantId,
                $expectedVersion,
                $claimToken,
                $result->errorCode,
                $result->message,
                $now,
                null,
            );
            if ($disabled === null) {
                return $this->conflictSummary();
            }

            $suspended = 0;
            if ($disabled['primary_lost']) {
                $tenant = $this->tenants->findById($tenantId);
                if ($tenant !== null && $tenant->get('status') === TenantStatus::ACTIVE) {
                    $transition = $this->lifecycle->transition(
                        $tenantId,
                        (int) $tenant->require('row_version'),
                        TenantStatus::SUSPENDED,
                        self::PRIMARY_DOMAIN_LOST_REASON,
                    );
                    if ($transition->isFailure()) {
                        throw new RuntimeException($transition->errorOrFail()->message);
                    }
                    $suspended = 1;
                }
            }

            $this->recordDisabledAudit(
                $disabled['domain'],
                $tenantId,
                $result->errorCode,
                $result->message,
                $disabled['fallback_primary'],
                $suspended === 1,
            );

            return [
                'verified' => 0,
                'failed' => 1,
                'disabled' => 1,
                'suspended' => $suspended,
                'conflicted' => 0,
            ];
        });
    }

    private function recordDisabledAudit(
        DataRecord $domain,
        int $tenantId,
        ?string $errorCode,
        ?string $message,
        ?DataRecord $fallbackPrimary,
        bool $tenantSuspended,
    ): void {
        $this->audit->recordSystem(new SystemAuditEventData(
            event: new AuditEventData(
                eventName: 'tenant.domain.revalidation_failed',
                eventCategory: AuditEventCategory::SECURITY,
                sourceModule: 'tenant',
                subjectType: 'tenant_domain',
                subjectId: (string) $domain->id(),
                subjectReference: (string) $domain->get('domain'),
                changes: [
                    'old' => ['status' => 'active'],
                    'new' => [
                        'status' => 'disabled',
                        'fallback_primary_domain_id' => $fallbackPrimary?->id(),
                        'tenant_suspended' => $tenantSuspended,
                    ],
                ],
                metadata: [
                    'verification_error' => $errorCode,
                    'verification_message' => $message,
                ],
                tags: ['tenant', 'domain', 'revalidation'],
            ),
            actorType: AuditActorType::JOB,
            actorId: self::JOB_ACTOR_ID,
            actorName: 'Tenant domain revalidation job',
            tenantId: $tenantId,
        ));
    }

    private function recordProcessingFailure(DataRecord $domain, int $tenantId, Throwable $exception): void
    {
        $this->audit->recordSystem(new SystemAuditEventData(
            event: new AuditEventData(
                eventName: 'tenant.domain.revalidation_processing_failed',
                eventCategory: AuditEventCategory::SECURITY,
                sourceModule: 'tenant',
                subjectType: 'tenant_domain',
                subjectId: (string) $domain->id(),
                subjectReference: (string) $domain->get('domain'),
                metadata: ['exception' => $exception::class],
                tags: ['tenant', 'domain', 'revalidation', 'failure'],
            ),
            actorType: AuditActorType::JOB,
            actorId: self::JOB_ACTOR_ID,
            actorName: 'Tenant domain revalidation job',
            tenantId: $tenantId,
        ));
    }

    /** @return array{verified:int,failed:int,disabled:int,suspended:int,conflicted:int} */
    private function conflictSummary(): array
    {
        return ['verified' => 0, 'failed' => 0, 'disabled' => 0, 'suspended' => 0, 'conflicted' => 1];
    }

    private function nextDue(DateTimeImmutable $now): DateTimeImmutable
    {
        return $now->modify(sprintf(
            '+%d hours',
            max((int) config('tenant.domains.revalidation_interval_hours', 24), 1),
        ));
    }

    private function graceExpiry(DateTimeImmutable $now): DateTimeImmutable
    {
        return $now->modify(sprintf(
            '+%d days',
            max((int) config('tenant.domains.verification_grace_days', 7), 1),
        ));
    }

    private function graceExpired(mixed $value, DateTimeImmutable $now): bool
    {
        return $value !== null
            && trim((string) $value) !== ''
            && new DateTimeImmutable((string) $value) <= $now;
    }

    private function claimToken(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20),
        );
    }
}
