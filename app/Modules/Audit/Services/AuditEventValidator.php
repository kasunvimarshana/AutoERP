<?php

declare(strict_types=1);

namespace Modules\Audit\Services;

use InvalidArgumentException;
use Modules\Audit\Constants\AuditActorType;
use Modules\Audit\Constants\AuditEventCategory;
use Modules\Audit\Data\AuditEventData;
use Modules\Audit\Data\PlatformAuditActorData;
use Modules\Audit\Data\SystemAuditEventData;

final class AuditEventValidator
{
    public function validate(AuditEventData $event): void
    {
        $this->identifier($event->eventName, 'event name', 150, true);
        $this->identifier($event->sourceModule, 'source module', 100);
        $this->identifier($event->subjectType, 'subject type', 100);
        $this->required($event->subjectId, 'subject identifier', 150);

        if (! in_array($event->eventCategory, AuditEventCategory::values(), true)) {
            throw new InvalidArgumentException('Unsupported audit event category.');
        }

        $this->optional($event->subjectReference, 'subject reference', 255);
        if ($event->sourceType !== null) {
            $this->identifier($event->sourceType, 'source type', 100);
        }
        $this->optional($event->sourceId, 'source identifier', 150);
        $this->optional($event->sourceReference, 'source reference', 255);
        $this->optional($event->producerKey, 'producer key', 190);

        $hasSourceType = $event->sourceType !== null && trim($event->sourceType) !== '';
        $hasSourceId = $event->sourceId !== null && trim($event->sourceId) !== '';
        if ($hasSourceType !== $hasSourceId) {
            throw new InvalidArgumentException('Audit source type and source identifier must be provided together.');
        }
        if ($event->sourceReference !== null && trim($event->sourceReference) !== '' && ! $hasSourceType) {
            throw new InvalidArgumentException('Audit source reference requires a source type and identifier.');
        }
    }

    public function validatePlatformActor(PlatformAuditActorData $actor): void
    {
        if (! in_array($actor->actorType, AuditActorType::values(), true)) {
            throw new InvalidArgumentException('Unsupported platform audit actor type.');
        }

        $this->required($actor->actorId, 'actor identifier', 100);
        $this->required($actor->actorName, 'actor name', 255);
        $this->optional($actor->actorGuard, 'actor guard', 64);
        $this->optional($actor->actorProvider, 'actor provider', 100);
        $this->optional($actor->applicationId, 'application identifier', 100);

        if ($actor->impersonatorUserId !== null && $actor->impersonatorUserId < 1) {
            throw new InvalidArgumentException('Impersonator user identifier must be positive.');
        }
    }

    public function validateSystem(SystemAuditEventData $event): void
    {
        $this->validate($event->event);

        if ($event->actorType === AuditActorType::USER || ! in_array($event->actorType, AuditActorType::values(), true)) {
            throw new InvalidArgumentException('System audit events require a non-user actor type.');
        }

        $this->required($event->actorId, 'actor identifier', 100);
        $this->required($event->actorName, 'actor name', 255);

        if ($event->tenantId < 1) {
            throw new InvalidArgumentException('Tenant identifier must be positive.');
        }
        if ($event->organizationUnitId !== null && $event->organizationUnitId < 1) {
            throw new InvalidArgumentException('Organization-unit identifier must be positive.');
        }
        $this->optional($event->applicationId, 'application identifier', 100);
    }

    private function identifier(string $value, string $field, int $maxLength, bool $allowDots = false): void
    {
        $this->required($value, $field, $maxLength);
        $pattern = $allowDots ? '/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/' : '/^[a-z0-9]+(?:[_-][a-z0-9]+)*$/';

        if (preg_match($pattern, trim($value)) !== 1) {
            throw new InvalidArgumentException("Audit {$field} must use a canonical lowercase identifier.");
        }
    }

    private function required(string $value, string $field, int $maxLength): void
    {
        $value = trim($value);
        if ($value === '') {
            throw new InvalidArgumentException("Audit {$field} is required.");
        }
        if (mb_strlen($value) > $maxLength) {
            throw new InvalidArgumentException("Audit {$field} may not exceed {$maxLength} characters.");
        }
    }

    private function optional(?string $value, string $field, int $maxLength): void
    {
        if ($value !== null && mb_strlen(trim($value)) > $maxLength) {
            throw new InvalidArgumentException("Audit {$field} may not exceed {$maxLength} characters.");
        }
    }
}
