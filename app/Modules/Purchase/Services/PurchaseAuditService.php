<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Audit\Constants\AuditEventCategory;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;

final class PurchaseAuditService
{
    public function __construct(private readonly AuditRecorderInterface $audit) {}

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>  $metadata
     */
    public function recordDocumentEvent(
        string $eventName,
        string $subjectType,
        Model $document,
        ?array $before = null,
        array $metadata = [],
    ): void {
        $after = $document->attributesToArray();

        $this->record($eventName, $subjectType, $document, [
            'before' => $before,
            'after' => $after,
        ], $metadata);
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $metadata
     */
    public function recordDeletedDocumentEvent(
        string $eventName,
        string $subjectType,
        Model $document,
        array $before,
        array $metadata = [],
    ): void {
        $this->record($eventName, $subjectType, $document, [
            'before' => $before,
            'after' => null,
        ], $metadata);
    }

    /**
     * @param  array<string, mixed>  $changes
     * @param  array<string, mixed>  $metadata
     */
    private function record(
        string $eventName,
        string $subjectType,
        Model $document,
        array $changes,
        array $metadata,
    ): void {
        $this->audit->record(new AuditEventData(
            eventName: $eventName,
            eventCategory: AuditEventCategory::WORKFLOW,
            sourceModule: 'purchase',
            subjectType: $subjectType,
            subjectId: (string) $document->getKey(),
            subjectReference: $this->subjectReference($document),
            changes: $changes,
            metadata: array_merge([
                'tenant_id' => $document->getAttribute('tenant_id'),
                'organization_unit_id' => $document->getAttribute('organization_unit_id'),
                'row_version' => $document->getAttribute('row_version'),
            ], $metadata),
            tags: ['purchase', $subjectType],
            producerKey: $eventName.':'.$document->getKey().':'.$document->getAttribute('row_version'),
        ));
    }

    private function subjectReference(Model $document): ?string
    {
        foreach (['purchase_order_number', 'grn_number', 'return_number', 'debit_note_number'] as $field) {
            $value = $document->getAttribute($field);
            if ($value !== null && trim((string) $value) !== '') {
                return (string) $value;
            }
        }

        return null;
    }
}
