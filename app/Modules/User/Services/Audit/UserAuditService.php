<?php

declare(strict_types=1);

namespace Modules\User\Services\Audit;

use Illuminate\Database\Eloquent\Model;
use Modules\Audit\Constants\AuditEventCategory;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;

final class UserAuditService
{
    public function __construct(private readonly AuditRecorderInterface $audit) {}

    /** @param array<string,mixed>|null $before @param array<string,mixed>|null $after */
    public function record(
        string $action,
        string $subjectType,
        Model $subject,
        ?array $before,
        ?array $after,
        ?string $reason = null,
    ): void {
        $metadata = ['tenant_id' => (int) $subject->getAttribute('tenant_id')];
        if ($reason !== null && trim($reason) !== '') {
            $metadata['reason'] = trim($reason);
        }

        $this->audit->record(new AuditEventData(
            eventName: 'user.'.$action,
            eventCategory: str_contains($action, 'role') || str_contains($action, 'permission')
                ? AuditEventCategory::AUTHORIZATION
                : AuditEventCategory::ADMINISTRATION,
            sourceModule: 'user',
            subjectType: $subjectType,
            subjectId: (string) $subject->getKey(),
            subjectReference: $this->subjectReference($subject),
            changes: [
                'before' => $this->sanitize($before),
                'after' => $this->sanitize($after),
            ],
            metadata: $metadata,
            tags: ['user', $subjectType],
        ));
    }

    /** @param array<string,mixed>|null $snapshot @return array<string,mixed>|null */
    private function sanitize(?array $snapshot): ?array
    {
        if ($snapshot === null) {
            return null;
        }
        foreach ([
            'device_token_hash', 'device_token_encrypted', 'object_key',
            'token_hash', 'delivery_token',
        ] as $key) {
            unset($snapshot[$key]);
        }
        return $snapshot;
    }

    private function subjectReference(Model $subject): string
    {
        foreach (['email', 'name', 'original_filename'] as $key) {
            $value = trim((string) $subject->getAttribute($key));
            if ($value !== '') {
                return $value;
            }
        }
        return (string) $subject->getKey();
    }
}
