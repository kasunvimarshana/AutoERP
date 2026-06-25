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
use Modules\Tenant\Constants\TenantDomainCheckStatus;
use Modules\Tenant\Constants\TenantDomainOperationalStatus;
use Modules\Tenant\Constants\TenantDomainOwnershipStatus;
use Modules\Tenant\Constants\TenantDomainOwnershipVerificationOutcome;
use Modules\Tenant\Constants\TenantDomainStatus;
use Modules\Tenant\Jobs\VerifyTenantDomainOperationalReadiness;
use Modules\Tenant\Repositories\TenantDomainRepositoryInterface;
use Modules\Tenant\Services\Contracts\TenantDomainOwnershipVerifierInterface;

final class TenantDomainOwnershipVerificationService
{
    private const JOB_ACTOR_ID = 'tenant-domain-ownership-verifier';

    public function __construct(
        private readonly TenantDomainRepositoryInterface $domains,
        private readonly TenantDomainOwnershipVerifierInterface $verifier,
        private readonly TenantExecutionContextInterface $executionContext,
        private readonly AuditRecorderInterface $audit,
        private readonly ClockInterface $clock,
    ) {}

    public function execute(int $tenantId, int $domainId, string $challengeHash, ?int $requestedBy): string
    {
        return $this->executionContext->runForTenant(
            $tenantId,
            function () use ($tenantId, $domainId, $challengeHash, $requestedBy): string {
                $domain = $this->domains->findByIdForTenant($domainId, $tenantId);
                if ($domain === null || $domain->get('status') === TenantDomainStatus::DISABLED) {
                    return TenantDomainOwnershipVerificationOutcome::STOP;
                }

                $currentHash = trim((string) $domain->get('verification_token_hash', ''));
                if ($currentHash === '' || ! hash_equals($currentHash, $challengeHash)) {
                    return TenantDomainOwnershipVerificationOutcome::STOP;
                }

                $now = $this->clock->now();
                $expiresAt = $this->asDateTime($domain->get('verification_expires_at'));
                if ($expiresAt === null || $expiresAt <= $now) {
                    $this->domains->updateWithVersion(
                        $domainId,
                        $tenantId,
                        (int) $domain->require('row_version'),
                        [
                            'ownership_status' => TenantDomainOwnershipStatus::EXPIRED,
                            'verification_error_code' => 'DOMAIN_VERIFICATION_CHALLENGE_EXPIRED',
                            'verification_error_message' => 'The DNS verification challenge expired. Generate a new challenge.',
                            'last_verification_attempt_at' => $now,
                        ],
                    );

                    return TenantDomainOwnershipVerificationOutcome::STOP;
                }

                $verification = $this->verifier->verify((string) $domain->require('domain'), $currentHash);
                if (! $verification->verified) {
                    $updated = $this->domains->recordVerificationAttempt(
                        $domainId,
                        $tenantId,
                        (int) $domain->require('row_version'),
                        false,
                        $verification->errorCode,
                        $verification->message ?? 'Domain ownership could not be verified yet.',
                        $now,
                    );

                    return $updated === null
                        ? TenantDomainOwnershipVerificationOutcome::STOP
                        : TenantDomainOwnershipVerificationOutcome::RETRY;
                }

                $probeToken = bin2hex(random_bytes(32));
                $updated = $this->domains->updateWithVersion(
                    $domainId,
                    $tenantId,
                    (int) $domain->require('row_version'),
                    [
                        'status' => TenantDomainStatus::PENDING,
                        'ownership_status' => TenantDomainOwnershipStatus::VERIFIED,
                        'verified_at' => $now,
                        'verified_by' => $requestedBy,
                        'last_verified_at' => $now,
                        'verified_token_hash' => $currentHash,
                        'verification_token_hash' => null,
                        'verification_expires_at' => null,
                        'last_verification_attempt_at' => $now,
                        'verification_failure_count' => 0,
                        'verification_error_code' => null,
                        'verification_error_message' => null,
                        'revalidation_due_at' => $this->nextRevalidationAt($now),
                        'verification_grace_expires_at' => $this->verificationGraceExpiresAt($now),
                        'operational_probe_token' => $probeToken,
                        'operational_probe_token_hash' => hash('sha256', $probeToken),
                        'routing_status' => TenantDomainCheckStatus::CHECKING,
                        'tls_status' => TenantDomainCheckStatus::CHECKING,
                        'reachability_status' => TenantDomainCheckStatus::CHECKING,
                        'operational_status' => TenantDomainOperationalStatus::CHECKING,
                        'operational_error_code' => null,
                        'operational_error_message' => null,
                        'operational_retry_at' => null,
                    ],
                );
                if ($updated === null) {
                    return TenantDomainOwnershipVerificationOutcome::STOP;
                }

                $this->recordVerifiedAudit($updated->id(), $tenantId, (string) $updated->require('domain'));
                VerifyTenantDomainOperationalReadiness::dispatch($tenantId, (int) $updated->id())->afterCommit();

                return TenantDomainOwnershipVerificationOutcome::VERIFIED;
            },
        );
    }

    public function markFailed(int $tenantId, int $domainId, string $challengeHash): void
    {
        $this->executionContext->runForTenant($tenantId, function () use ($tenantId, $domainId, $challengeHash): void {
            $domain = $this->domains->findByIdForTenant($domainId, $tenantId);
            if ($domain === null) {
                return;
            }

            $currentHash = trim((string) $domain->get('verification_token_hash', ''));
            if ($currentHash === '' || ! hash_equals($currentHash, $challengeHash)) {
                return;
            }

            $this->domains->updateWithVersion(
                $domainId,
                $tenantId,
                (int) $domain->require('row_version'),
                [
                    'ownership_status' => TenantDomainOwnershipStatus::FAILED,
                    'verification_error_code' => 'DOMAIN_OWNERSHIP_VERIFICATION_FAILED',
                    'verification_error_message' => 'DNS ownership could not be verified after the configured retry attempts.',
                    'last_verification_attempt_at' => $this->clock->now(),
                ],
            );
        });
    }

    private function recordVerifiedAudit(int|string $domainId, int $tenantId, string $domain): void
    {
        $this->audit->recordSystem(new SystemAuditEventData(
            event: new AuditEventData(
                eventName: 'tenant.domain.ownership_verified',
                eventCategory: AuditEventCategory::SECURITY,
                sourceModule: 'tenant',
                subjectType: 'tenant_domain',
                subjectId: (string) $domainId,
                subjectReference: $domain,
                changes: ['new' => ['ownership_status' => TenantDomainOwnershipStatus::VERIFIED]],
                tags: ['tenant', 'domain', 'ownership'],
            ),
            actorType: AuditActorType::JOB,
            actorId: self::JOB_ACTOR_ID,
            actorName: 'Tenant domain ownership verifier',
            tenantId: $tenantId,
        ));
    }

    private function nextRevalidationAt(DateTimeImmutable $now): DateTimeImmutable
    {
        return $now->modify(sprintf(
            '+%d hours',
            max((int) config('tenant.domains.revalidation_interval_hours', 24), 1),
        ));
    }

    private function verificationGraceExpiresAt(DateTimeImmutable $now): DateTimeImmutable
    {
        return $now->modify(sprintf(
            '+%d days',
            max((int) config('tenant.domains.verification_grace_days', 7), 1),
        ));
    }

    private function asDateTime(mixed $value): ?DateTimeImmutable
    {
        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        $text = trim((string) $value);

        return $text === '' ? null : new DateTimeImmutable($text);
    }
}
