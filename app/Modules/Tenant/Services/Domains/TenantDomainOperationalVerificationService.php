<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Domains;

use DateInterval;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Modules\Audit\Constants\AuditActorType;
use Modules\Audit\Constants\AuditEventCategory;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Audit\Data\SystemAuditEventData;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\Contracts\TransactionManagerInterface;
use Modules\Tenant\Constants\TenantDomainCheckStatus;
use Modules\Tenant\Constants\TenantDomainOperationalStatus;
use Modules\Tenant\Constants\TenantDomainOperationalVerificationOutcome;
use Modules\Tenant\Constants\TenantDomainOwnershipStatus;
use Modules\Tenant\Constants\TenantDomainProbe;
use Modules\Tenant\Constants\TenantDomainStatus;
use Modules\Tenant\Repositories\TenantDomainRepositoryInterface;
use Throwable;

final class TenantDomainOperationalVerificationService
{
    public function __construct(
        private readonly TenantDomainRepositoryInterface $domains,
        private readonly TenantDomainTlsInspector $tls,
        private readonly TenantExecutionContextInterface $executionContext,
        private readonly TransactionManagerInterface $transactions,
        private readonly AuditRecorderInterface $audit,
        private readonly ClockInterface $clock,
    ) {}

    public function execute(int $tenantId, int $domainId): string
    {
        return $this->executionContext->runForTenant($tenantId, function () use ($tenantId, $domainId): string {
            $domain = $this->domains->findByIdForTenant($domainId, $tenantId);
            if (
                $domain === null
                || $domain->get('ownership_status') !== TenantDomainOwnershipStatus::VERIFIED
                || $domain->get('status') === TenantDomainStatus::DISABLED
            ) {
                return TenantDomainOperationalVerificationOutcome::STOP;
            }

            $token = trim((string) $domain->get('operational_probe_token'));
            if ($token === '') {
                return TenantDomainOperationalVerificationOutcome::STOP;
            }

            $now = $this->clock->now();
            try {
                $tlsExpiresAt = $this->tls->expiry((string) $domain->require('domain'));
                $minimumValidityDays = max(0, (int) config('tenant.domains.minimum_tls_validity_days', 1));
                if ($tlsExpiresAt <= $now->modify(sprintf('+%d days', $minimumValidityDays))) {
                    return $this->recordFailure(
                        $tenantId,
                        $domainId,
                        (int) $domain->require('row_version'),
                        'DOMAIN_TLS_CERTIFICATE_EXPIRING',
                        'The domain TLS certificate is expired or too close to expiry.',
                        TenantDomainCheckStatus::READY,
                        TenantDomainCheckStatus::FAILED,
                        TenantDomainCheckStatus::PENDING,
                        $tlsExpiresAt,
                    );
                }

                $response = Http::acceptJson()
                    ->withHeaders([TenantDomainProbe::HEADER => $token])
                    ->withOptions(['verify' => true])
                    ->connectTimeout(max(1, (int) config('tenant.domains.operational_connect_timeout_seconds', 5)))
                    ->timeout(max(2, (int) config('tenant.domains.operational_timeout_seconds', 10)))
                    ->get('https://'.(string) $domain->require('domain').'/'.TenantDomainProbe::PATH);

                $payload = $response->json();
                $verified = $response->successful()
                    && is_array($payload)
                    && ($payload['ready'] ?? false) === true
                    && (int) ($payload['tenant_id'] ?? 0) === $tenantId
                    && strtolower((string) ($payload['domain'] ?? '')) === strtolower((string) $domain->require('domain'));

                if (! $verified) {
                    return $this->recordFailure(
                        $tenantId,
                        $domainId,
                        (int) $domain->require('row_version'),
                        'DOMAIN_REACHABILITY_PROBE_FAILED',
                        'The domain reached an endpoint that did not confirm the expected tenant.',
                        TenantDomainCheckStatus::READY,
                        TenantDomainCheckStatus::READY,
                        TenantDomainCheckStatus::FAILED,
                        $tlsExpiresAt,
                    );
                }

                $updated = $this->transactions->runInTransaction(function () use (
                    $tenantId,
                    $domainId,
                    $domain,
                    $now,
                    $tlsExpiresAt,
                ) {
                    $updated = $this->domains->updateWithVersion(
                        $domainId,
                        $tenantId,
                        (int) $domain->require('row_version'),
                        [
                            'status' => TenantDomainStatus::ACTIVE,
                            'routing_status' => TenantDomainCheckStatus::READY,
                            'tls_status' => TenantDomainCheckStatus::READY,
                            'reachability_status' => TenantDomainCheckStatus::READY,
                            'operational_status' => TenantDomainOperationalStatus::READY,
                            'last_operational_check_at' => $now,
                            'operational_retry_at' => null,
                            'tls_expires_at' => $tlsExpiresAt,
                            'operational_error_code' => null,
                            'operational_error_message' => null,
                            'operational_claim_token' => null,
                            'operational_claimed_at' => null,
                        ],
                    );
                    if ($updated === null) {
                        return null;
                    }

                    $this->audit->recordSystem(new SystemAuditEventData(
                        event: new AuditEventData(
                            eventName: 'tenant.domain.operational_ready',
                            eventCategory: AuditEventCategory::SECURITY,
                            sourceModule: 'tenant',
                            subjectType: 'tenant_domain',
                            subjectId: (string) $domainId,
                            subjectReference: (string) $domain->require('domain'),
                            changes: ['new' => ['operational_status' => TenantDomainOperationalStatus::READY]],
                            tags: ['tenant', 'domain', 'operations'],
                        ),
                        actorType: AuditActorType::JOB,
                        actorId: 'tenant-domain-operational-verifier',
                        actorName: 'Tenant domain operational verifier',
                        tenantId: $tenantId,
                    ));

                    return $updated;
                });

                return $updated === null
                    ? TenantDomainOperationalVerificationOutcome::STOP
                    : TenantDomainOperationalVerificationOutcome::READY;
            } catch (ConnectionException $exception) {
                [$code, $message, $routing, $tls] = $this->connectionFailure($exception);

                return $this->recordFailure(
                    $tenantId,
                    $domainId,
                    (int) $domain->require('row_version'),
                    $code,
                    $message,
                    $routing,
                    $tls,
                    TenantDomainCheckStatus::PENDING,
                    null,
                );
            } catch (Throwable) {
                return $this->recordFailure(
                    $tenantId,
                    $domainId,
                    (int) $domain->require('row_version'),
                    'DOMAIN_OPERATIONAL_CHECK_FAILED',
                    'The domain operational check could not be completed.',
                    TenantDomainCheckStatus::FAILED,
                    TenantDomainCheckStatus::PENDING,
                    TenantDomainCheckStatus::PENDING,
                    null,
                );
            }
        });
    }

    private function recordFailure(
        int $tenantId,
        int $domainId,
        int $expectedVersion,
        string $code,
        string $message,
        string $routingStatus,
        string $tlsStatus,
        string $reachabilityStatus,
        ?\DateTimeInterface $tlsExpiresAt,
    ): string {
        $now = $this->clock->now();
        $retryAt = $now->add(new DateInterval(sprintf(
            'PT%dM',
            max(1, (int) config('tenant.domains.operational_retry_minutes', 15)),
        )));

        $updated = $this->domains->updateWithVersion($domainId, $tenantId, $expectedVersion, [
            'status' => TenantDomainStatus::PENDING,
            'routing_status' => $routingStatus,
            'tls_status' => $tlsStatus,
            'reachability_status' => $reachabilityStatus,
            'operational_status' => TenantDomainOperationalStatus::FAILED,
            'last_operational_check_at' => $now,
            'operational_retry_at' => $retryAt,
            'tls_expires_at' => $tlsExpiresAt,
            'operational_error_code' => $code,
            'operational_error_message' => $message,
            'operational_claim_token' => null,
            'operational_claimed_at' => null,
        ]);

        return $updated === null
            ? TenantDomainOperationalVerificationOutcome::STOP
            : TenantDomainOperationalVerificationOutcome::RETRY;
    }

    /** @return array{string,string,string,string} */
    private function connectionFailure(ConnectionException $exception): array
    {
        $message = strtolower($exception->getMessage());
        if (str_contains($message, 'ssl') || str_contains($message, 'certificate')) {
            return [
                'DOMAIN_TLS_CHECK_FAILED',
                'The domain is routed, but its TLS certificate could not be validated.',
                TenantDomainCheckStatus::READY,
                TenantDomainCheckStatus::FAILED,
            ];
        }

        return [
            'DOMAIN_ROUTING_CHECK_FAILED',
            'The domain could not be resolved or connected to the application.',
            TenantDomainCheckStatus::FAILED,
            TenantDomainCheckStatus::PENDING,
        ];
    }
}
