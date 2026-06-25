<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Services\Audit;

use Illuminate\Database\Eloquent\Model;
use Modules\Audit\Constants\AuditEventCategory;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\OrganizationUnit\Models\OrganizationUnitDocumentModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\OrganizationUnit\Models\OrganizationUnitTypeModel;

final class OrganizationUnitAuditService
{
    public function __construct(private readonly AuditRecorderInterface $audit) {}

    /** @param array<string, mixed>|null $before @param array<string, mixed>|null $after */
    public function unit(string $action, OrganizationUnitModel $unit, ?array $before, ?array $after): void
    {
        $this->record(
            action: 'unit.'.$action,
            subjectType: 'organization_unit',
            subject: $unit,
            subjectReference: (string) $unit->getAttribute('name'),
            before: $this->publicUnitSnapshot($before),
            after: $this->publicUnitSnapshot($after),
            tags: ['organization-unit', 'hierarchy'],
        );
    }

    /** @param array<string, mixed>|null $before @param array<string, mixed>|null $after */
    public function type(string $action, OrganizationUnitTypeModel $type, ?array $before, ?array $after): void
    {
        $this->record(
            action: 'type.'.$action,
            subjectType: 'organization_unit_type',
            subject: $type,
            subjectReference: (string) $type->getAttribute('name'),
            before: $this->withoutInternalFields($before, ['name_key']),
            after: $this->withoutInternalFields($after, ['name_key']),
            tags: ['organization-unit', 'organization-unit-type'],
        );
    }

    /** @param array<string, mixed>|null $before @param array<string, mixed>|null $after */
    public function document(string $action, OrganizationUnitDocumentModel $document, ?array $before, ?array $after): void
    {
        $this->record(
            action: 'document.'.$action,
            subjectType: 'organization_unit_document',
            subject: $document,
            subjectReference: (string) $document->getAttribute('name'),
            before: $this->withoutInternalFields($before, ['object_key', 'active_name_hash']),
            after: $this->withoutInternalFields($after, ['object_key', 'active_name_hash']),
            tags: ['organization-unit', 'document'],
        );
    }

    /**
     * @param array<string, mixed>|null $before
     * @param array<string, mixed>|null $after
     * @param list<string> $tags
     */
    private function record(
        string $action,
        string $subjectType,
        Model $subject,
        string $subjectReference,
        ?array $before,
        ?array $after,
        array $tags,
    ): void {
        $this->audit->record(new AuditEventData(
            eventName: 'organization_unit.'.$action,
            eventCategory: AuditEventCategory::ADMINISTRATION,
            sourceModule: 'organization_unit',
            subjectType: $subjectType,
            subjectId: (string) $subject->getKey(),
            subjectReference: $subjectReference,
            changes: ['before' => $before, 'after' => $after],
            metadata: [
                'tenant_id' => (int) $subject->getAttribute('tenant_id'),
                'organization_unit_id' => $subject instanceof OrganizationUnitModel
                    ? (int) $subject->getKey()
                    : $this->positiveInt($subject->getAttribute('organization_unit_id')),
                'row_version' => $this->positiveInt($subject->getAttribute('row_version')),
            ],
            tags: $tags,
        ));
    }

    /** @param array<string, mixed>|null $snapshot @return array<string, mixed>|null */
    private function publicUnitSnapshot(?array $snapshot): ?array
    {
        return $this->withoutInternalFields($snapshot, ['path_hash', 'logo_object_key']);
    }

    /** @param array<string, mixed>|null $snapshot @param list<string> $fields @return array<string, mixed>|null */
    private function withoutInternalFields(?array $snapshot, array $fields): ?array
    {
        if ($snapshot === null) {
            return null;
        }
        foreach ($fields as $field) {
            unset($snapshot[$field]);
        }

        return $snapshot;
    }

    private function positiveInt(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}
