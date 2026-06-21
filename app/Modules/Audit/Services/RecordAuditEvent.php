<?php

declare(strict_types=1);

namespace Modules\Audit\Services;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Audit\Constants\AuditActorType;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Audit\Data\SystemAuditEventData;
use Modules\Audit\Exceptions\AuditWriteException;
use Modules\Audit\Repositories\AuditLogWriterInterface;
use Modules\Core\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Throwable;

final class RecordAuditEvent implements AuditRecorderInterface
{
    public function __construct(
        private readonly AuditLogWriterInterface $writer,
        private readonly AuditEventValidator $validator,
        private readonly AuditPayloadSanitizer $sanitizer,
        private readonly AuditRequestContextResolver $requestContext,
        private readonly AuditOwnershipValidator $ownership,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
        private readonly CurrentUserContextAccessorInterface $currentUser,
    ) {}

    public function record(AuditEventData $event): void
    {
        $this->validator->validate($event);

        $tenant = $this->currentTenant->requireCurrent();
        $user = $this->currentUser->requireCurrent();
        $organization = $this->currentOrganizationUnit->current();

        if ($organization !== null && $organization->tenantId() !== $tenant->tenantId()) {
            throw new AuditWriteException('Current organization unit does not belong to the current tenant.');
        }

        $actor = $user->user();
        $token = $user->tokenPayload();
        $tenantName = $this->nullableString($tenant->tenant()->get('name'));
        if ($tenantName === null) {
            throw new AuditWriteException('Current tenant name is required for the audit snapshot.');
        }

        $this->append($event, [
            'tenant_id' => $tenant->tenantId(),
            'tenant_name' => $tenantName,
            'organization_unit_id' => $organization?->organizationUnitId(),
            'organization_unit_name' => $organization?->name(),
            'actor_type' => AuditActorType::USER,
            'actor_id' => (string) $user->userId(),
            'actor_name' => $this->actorName($actor),
            'actor_guard' => $this->nullableTrim($user->guard()),
            'actor_provider' => $this->nullableTrim($user->provider()),
            'application_id' => $this->nullableTrim($user->applicationId()),
            'impersonator_user_id' => $this->positiveInt($token['impersonator_user_id'] ?? null),
            ...$this->requestContext->resolve(),
        ]);
    }

    public function recordSystem(SystemAuditEventData $event): void
    {
        $this->validator->validateSystem($event);
        $scope = $this->ownership->validateSystemScope($event->tenantId, $event->organizationUnitId);

        $this->append($event->event, [
            'tenant_id' => $event->tenantId,
            'tenant_name' => $scope['tenant_name'],
            'organization_unit_id' => $event->organizationUnitId,
            'organization_unit_name' => $scope['organization_unit_name'],
            'actor_type' => trim($event->actorType),
            'actor_id' => trim($event->actorId),
            'actor_name' => trim($event->actorName),
            'actor_guard' => null,
            'actor_provider' => null,
            'application_id' => $this->nullableTrim($event->applicationId),
            'impersonator_user_id' => null,
            'request_id' => null,
            'request_method' => null,
            'route_name' => null,
            'route_path' => null,
            'ip_address' => null,
            'user_agent' => null,
        ]);
    }

    /** @param array<string, mixed> $context */
    private function append(AuditEventData $event, array $context): void
    {
        $changes = $this->sanitizer->sanitize($event->changes);
        $metadata = $this->sanitizer->sanitize($event->metadata);
        $tags = $this->sanitizer->sanitizeTags($event->tags);
        $this->sanitizer->assertPayloadSize($changes, $metadata, $tags);

        $occurredAt = ($event->occurredAt ?? new DateTimeImmutable('now', new DateTimeZone('UTC')))
            ->setTimezone(new DateTimeZone('UTC'));

        $producerKey = $this->nullableTrim($event->producerKey);
        $tenantScope = is_numeric($context['tenant_id'] ?? null) ? (string) $context['tenant_id'] : 'platform';

        $attributes = [
            'event_uuid' => (string) Str::uuid(),
            'producer_key' => $producerKey,
            'producer_fingerprint' => $producerKey !== null
                ? hash('sha256', $tenantScope.'|'.trim($event->sourceModule).'|'.$producerKey)
                : null,
            ...$context,
            'event_category' => trim($event->eventCategory),
            'event_name' => trim($event->eventName),
            'subject_type' => trim($event->subjectType),
            'subject_id' => trim($event->subjectId),
            'subject_reference' => $this->nullableTrim($event->subjectReference),
            'source_module' => trim($event->sourceModule),
            'source_type' => $this->nullableTrim($event->sourceType),
            'source_id' => $this->nullableTrim($event->sourceId),
            'source_reference' => $this->nullableTrim($event->sourceReference),
            'changes' => $changes,
            'metadata' => $metadata,
            'tags' => $tags === [] ? null : $tags,
            'occurred_at' => $occurredAt->format('Y-m-d H:i:s'),
            'recorded_at' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s'),
        ];

        try {
            $this->writer->append($attributes);
        } catch (Throwable $exception) {
            throw new AuditWriteException('Audit event could not be recorded.', previous: $exception);
        }
    }

    private function actorName(Authenticatable $actor): string
    {
        if ($actor instanceof Model) {
            $firstName = $this->nullableString($actor->getAttribute('first_name'));
            $lastName = $this->nullableString($actor->getAttribute('last_name'));
            $name = trim(implode(' ', array_filter([$firstName, $lastName])));

            foreach ([$name, $this->nullableString($actor->getAttribute('name')), $this->nullableString($actor->getAttribute('email'))] as $candidate) {
                if ($candidate !== null && $candidate !== '') {
                    return mb_substr($candidate, 0, 255);
                }
            }
        }

        return 'User '.$actor->getAuthIdentifier();
    }

    private function nullableTrim(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    private function nullableString(mixed $value): ?string
    {
        return is_scalar($value) && trim((string) $value) !== '' ? trim((string) $value) : null;
    }

    private function positiveInt(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}
