<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Domains;

use DateTimeImmutable;
use Modules\Audit\Constants\AuditActorType;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Audit\Data\SystemAuditEventData;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Tenant\Repositories\TenantDomainRepositoryInterface;
use Modules\Tenant\Services\Contracts\TenantDomainOwnershipVerifierInterface;

final class RevalidateTenantDomainsService
{
    public function __construct(
        private readonly TenantDomainRepositoryInterface $domains,
        private readonly TenantDomainOwnershipVerifierInterface $verifier,
        private readonly AuditRecorderInterface $audit,
        private readonly ClockInterface $clock,
        private readonly TenantExecutionContextInterface $executionContext,
    ) {}

    /** @return array{checked:int,verified:int,failed:int,disabled:int} */
    public function execute(?int $limit = null): array
    {
        $now = $this->clock->now();
        $batchSize = max(
            1,
            min($limit ?? (int) config('tenant.domains.revalidation_batch_size', 100), 500),
        );
        $summary = ['checked' => 0, 'verified' => 0, 'failed' => 0, 'disabled' => 0];

        foreach ($this->domains->listDueForRevalidation($now, $batchSize) as $domain) {
            $summary['checked']++;
            $tenantId = (int) $domain->require('tenant_id');
            $result = $this->executionContext->runForTenant(
                $tenantId,
                fn (): array => $this->revalidate($domain, $tenantId, $now),
            );

            $summary['verified'] += $result['verified'];
            $summary['failed'] += $result['failed'];
            $summary['disabled'] += $result['disabled'];
        }

        return $summary;
    }

    /** @return array{verified:int,failed:int,disabled:int} */
    private function revalidate(DataRecord $domain, int $tenantId, DateTimeImmutable $now): array
    {
        $hash = trim((string) $domain->get('verified_token_hash', ''));
        $result = $this->verifier->verify((string) $domain->require('domain'), $hash);

        if ($result->verified) {
            $this->domains->recordVerificationAttempt(
                $domain->id(),
                $tenantId,
                true,
                null,
                $now,
                $this->nextDue($now),
                $this->graceExpiry($now),
            );

            return ['verified' => 1, 'failed' => 0, 'disabled' => 0];
        }

        $this->domains->recordVerificationAttempt(
            $domain->id(),
            $tenantId,
            false,
            $result->message,
            $now,
        );

        if (! $this->graceExpired($domain->get('verification_grace_expires_at'), $now)) {
            return ['verified' => 0, 'failed' => 1, 'disabled' => 0];
        }

        $disabled = $this->domains->updateWithVersion(
            $domain->id(),
            $tenantId,
            (int) $domain->get('row_version', 0),
            [
                'status' => 'disabled',
                'is_primary' => false,
                'primary_marker' => null,
                'verification_last_error' => $result->message,
            ],
        );
        if ($disabled === null) {
            return ['verified' => 0, 'failed' => 1, 'disabled' => 0];
        }

        $this->audit->recordSystem(new SystemAuditEventData(
            event: new AuditEventData(
                eventName: 'tenant.domain.revalidation_failed',
                eventCategory: 'security',
                sourceModule: 'tenant',
                subjectType: 'tenant_domain',
                subjectId: (string) $disabled->id(),
                subjectReference: (string) $disabled->get('domain'),
                changes: [
                    'old' => ['status' => 'active'],
                    'new' => ['status' => 'disabled'],
                ],
                metadata: [
                    'verification_error' => $result->errorCode,
                    'verification_message' => $result->message,
                ],
                tags: ['tenant', 'domain', 'revalidation'],
            ),
            actorType: AuditActorType::JOB,
            actorId: 'tenant-domain-revalidation',
            actorName: 'Tenant domain revalidation job',
            tenantId: $tenantId,
        ));

        return ['verified' => 0, 'failed' => 1, 'disabled' => 1];
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
}
