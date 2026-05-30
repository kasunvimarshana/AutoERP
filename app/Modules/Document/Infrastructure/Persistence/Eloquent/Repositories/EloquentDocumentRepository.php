<?php

namespace Modules\Document\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Document\Domain\Aggregates\DocumentAggregate;
use Modules\Document\Domain\Entities\Document;
use Modules\Document\Domain\Entities\DocumentItem;
use Modules\Document\Domain\Repositories\DocumentRepositoryInterface;
use Modules\Document\Infrastructure\Persistence\Eloquent\Models\DocumentAttachmentModel;
use Modules\Document\Infrastructure\Persistence\Eloquent\Models\DocumentItemModel;
use Modules\Document\Infrastructure\Persistence\Eloquent\Models\DocumentModel;

class EloquentDocumentRepository implements DocumentRepositoryInterface
{
    public function save(DocumentAggregate $aggregate): DocumentAggregate
    {
        $model = DocumentModel::create([
            'tenant_id' => $aggregate->document->tenantId,
            'organization_unit_id' => $aggregate->document->organizationUnitId,
            'document_type_id' => $aggregate->document->documentTypeId,
            'document_number' => $aggregate->document->documentNumber,
            'status' => $aggregate->document->status,
            'owner_id' => $aggregate->document->ownerId,
            'party_id' => $aggregate->document->partyId,
            'document_date' => $aggregate->document->documentDate,
            'due_date' => $aggregate->document->dueDate,
            'subtotal' => $aggregate->document->subtotal,
            'discount_total' => $aggregate->document->discountTotal,
            'tax_total' => $aggregate->document->taxTotal,
            'grand_total' => $aggregate->document->grandTotal,
            'notes' => $aggregate->document->notes,
            'created_by' => $aggregate->document->createdBy,
            'updated_by' => $aggregate->document->updatedBy,
        ]);

        $this->persistDocumentFieldValues($aggregate->document->tenantId, $model->id, $aggregate->document->data);

        foreach ($aggregate->items as $item) {
            $itemModel = DocumentItemModel::create([
                'tenant_id' => $aggregate->document->tenantId,
                'document_id' => $model->id,
                'item_type' => $item->itemType,
                'description' => $item->description,
                'line_total' => $item->lineTotal,
                'sequence' => $item->sequence,
            ]);

            $this->persistDocumentItemFieldValues($aggregate->document->tenantId, $itemModel->id, $item->data);
        }

        $this->createVersionSnapshot($aggregate->document->tenantId, $model->id, null, 'initial_create');
        $this->recordEvent($aggregate->document->tenantId, $model->id, 'document.created', [
            'status' => $model->status,
        ]);

        DB::table('document_status_histories')->insert([
            'tenant_id' => $aggregate->document->tenantId,
            'document_id' => $model->id,
            'from_status' => null,
            'to_status' => $model->status,
            'action_name' => 'create',
            'performed_by' => null,
            'reason' => 'Document created',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $saved = $this->findById($aggregate->document->tenantId, $model->id);

        return $saved ?? $aggregate;
    }

    public function findById(int $tenantId, int $id): ?DocumentAggregate
    {
        $model = DocumentModel::query()
            ->with(['items', 'attachments'])
            ->where('tenant_id', $tenantId)
            ->find($id);

        if ($model === null) {
            return null;
        }

        $documentFieldValues = DB::table('document_field_values')
            ->where('tenant_id', $tenantId)
            ->where('document_id', $model->id)
            ->orderBy('id')
            ->get();

        $documentData = [];
        foreach ($documentFieldValues as $row) {
            $documentData[(string) $row->field_key] = $this->fromTypedValueRecord($row);
        }

        $itemIds = $model->items->pluck('id')->all();
        $itemDataByItemId = [];
        if ($itemIds !== []) {
            $itemFieldRows = DB::table('document_item_field_values')
                ->where('tenant_id', $tenantId)
                ->whereIn('document_item_id', $itemIds)
                ->orderBy('id')
                ->get();

            foreach ($itemFieldRows as $row) {
                $itemId = (int) $row->document_item_id;
                $itemDataByItemId[$itemId] ??= [];
                $itemDataByItemId[$itemId][(string) $row->field_key] = $this->fromTypedValueRecord($row);
            }
        }

        $document = new Document(
            id: $model->id,
            tenantId: $model->tenant_id,
            organizationUnitId: $model->organization_unit_id,
            documentTypeId: $model->document_type_id,
            documentNumber: $model->document_number,
            documentDate: $model->document_date?->format('Y-m-d') ?? '',
            dueDate: $model->due_date?->format('Y-m-d'),
            status: $model->status,
            ownerId: $model->owner_id,
            partyId: $model->party_id,
            subtotal: (string) $model->subtotal,
            discountTotal: (string) $model->discount_total,
            taxTotal: (string) $model->tax_total,
            grandTotal: (string) $model->grand_total,
            data: $documentData,
            notes: $model->notes,
            createdBy: $model->created_by,
            updatedBy: $model->updated_by,
            attachments: $model->attachments->map(fn (DocumentAttachmentModel $attachment): array => [
                'id' => $attachment->id,
                'file_name' => $attachment->file_name,
                'stored_name' => $attachment->stored_name,
                'disk' => $attachment->disk,
                'directory' => $attachment->directory,
                'mime_type' => $attachment->mime_type,
                'file_size' => $attachment->file_size,
            ])->all(),
        );

        $items = $model->items->map(fn (DocumentItemModel $item): DocumentItem => new DocumentItem(
            id: $item->id,
            documentId: $item->document_id,
            itemType: $item->item_type,
            description: $item->description,
            lineTotal: (string) $item->line_total,
            sequence: $item->sequence,
            data: $itemDataByItemId[$item->id] ?? [],
        ))->all();

        return new DocumentAggregate($document, $items);
    }

    public function update(DocumentAggregate $aggregate): DocumentAggregate
    {
        $model = DocumentModel::query()
            ->where('tenant_id', $aggregate->document->tenantId)
            ->findOrFail($aggregate->document->id);
        $previousStatus = $model->status;

        $model->update([
            'status' => $aggregate->document->status,
            'notes' => $aggregate->document->notes,
            'updated_by' => $aggregate->document->updatedBy,
        ]);

        $this->persistDocumentFieldValues($aggregate->document->tenantId, $model->id, $aggregate->document->data);

        $nextVersion = (int) DB::table('document_versions')
            ->where('tenant_id', $aggregate->document->tenantId)
            ->where('document_id', $model->id)
            ->max('version') + 1;

        $this->createVersionSnapshot($aggregate->document->tenantId, $model->id, $nextVersion, 'update');

        DB::table('document_status_histories')->insert([
            'tenant_id' => $aggregate->document->tenantId,
            'document_id' => $model->id,
            'from_status' => $previousStatus,
            'to_status' => $aggregate->document->status,
            'action_name' => 'status_change',
            'performed_by' => null,
            'reason' => 'Document status updated',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->recordEvent($aggregate->document->tenantId, $model->id, 'document.status_changed', [
            'from_status' => $previousStatus,
            'to_status' => $aggregate->document->status,
        ]);

        return $this->findById($aggregate->document->tenantId, $model->id) ?? $aggregate;
    }

    public function delete(int $id): bool
    {
        $model = DocumentModel::query()->findOrFail($id);

        return (bool) $model->delete();
    }

    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $tenantId = (int) ($filters['tenant_id'] ?? 0);

        return DocumentModel::query()
            ->with('type')
            ->where('tenant_id', $tenantId)
            ->when(
                isset($filters['document_type_id']),
                fn ($query) => $query->where('document_type_id', $filters['document_type_id']),
            )
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(isset($filters['search']) && $filters['search'] !== '', function ($query) use ($filters): void {
                $search = "%{$filters['search']}%";
                $query->where(function ($nested) use ($search): void {
                    $nested->where('document_number', 'like', $search)
                        ->orWhere('notes', 'like', $search);
                });
            })
            ->orderByDesc('document_date')
            ->paginate($perPage);
    }

    public function storeAttachment(int $documentId, array $payload): array
    {
        $document = $this->requireDocument((int) $payload['tenant_id'], $documentId);

        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $payload['uploaded_file'];
        $path = $uploadedFile->storeAs(
            $payload['directory'],
            $payload['stored_name'],
            $payload['disk'],
        );

        $attachment = DocumentAttachmentModel::create([
            'tenant_id' => $document->tenant_id,
            'document_id' => $documentId,
            'disk' => $payload['disk'],
            'directory' => dirname($path) === '.' ? null : dirname($path),
            'file_name' => $payload['file_name'],
            'stored_name' => basename($path),
            'mime_type' => $payload['mime_type'],
            'file_size' => $payload['file_size'],
            'checksum' => $payload['checksum'],
            'uploaded_by' => $payload['uploaded_by'],
        ]);

        $this->recordEvent($document->tenant_id, $documentId, 'document.attachment_uploaded', [
            'attachment_id' => $attachment->id,
        ]);

        return [
            'id' => $attachment->id,
            'file_name' => $attachment->file_name,
            'stored_name' => $attachment->stored_name,
            'mime_type' => $attachment->mime_type,
            'file_size' => $attachment->file_size,
            'disk' => $attachment->disk,
            'directory' => $attachment->directory,
        ];
    }

    public function addComment(int $tenantId, int $documentId, array $payload): array
    {
        $document = $this->requireDocument($tenantId, $documentId);

        $id = DB::table('document_comments')->insertGetId([
            'tenant_id' => $document->tenant_id,
            'document_id' => $documentId,
            'comment' => (string) $payload['comment'],
            'author_id' => $payload['author_id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (array) DB::table('document_comments')->where('id', $id)->first();
    }

    public function addActivity(int $tenantId, int $documentId, array $payload): array
    {
        $document = $this->requireDocument($tenantId, $documentId);

        $id = DB::table('document_activities')->insertGetId([
            'tenant_id' => $document->tenant_id,
            'document_id' => $documentId,
            'activity_type' => (string) $payload['activity_type'],
            'description' => $payload['description'] ?? null,
            'performed_by' => $payload['performed_by'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (array) DB::table('document_activities')->where('id', $id)->first();
    }

    public function addEvent(int $tenantId, int $documentId, array $payload): array
    {
        $document = $this->requireDocument($tenantId, $documentId);

        $id = $this->recordEvent(
            (int) $document->tenant_id,
            $documentId,
            (string) $payload['event_type'],
            $payload['attributes'] ?? $payload['payload'] ?? [],
            $payload['performed_by'] ?? null,
        );

        return (array) DB::table('document_events')->where('id', $id)->first();
    }

    public function addPermission(int $tenantId, int $documentId, array $payload): array
    {
        $document = $this->requireDocument($tenantId, $documentId);

        $id = DB::table('document_permissions')->insertGetId([
            'tenant_id' => $document->tenant_id,
            'document_id' => $documentId,
            'principal_type' => (string) $payload['principal_type'],
            'principal_identifier' => (string) $payload['principal_identifier'],
            'ability' => (string) $payload['ability'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (array) DB::table('document_permissions')->where('id', $id)->first();
    }

    public function addRelation(int $tenantId, int $documentId, array $payload): array
    {
        $document = $this->requireDocument($tenantId, $documentId);
        $target = $this->requireDocument($tenantId, (int) $payload['target_document_id']);

        $id = DB::table('document_relations')->insertGetId([
            'tenant_id' => $document->tenant_id,
            'source_document_id' => $document->id,
            'target_document_id' => $target->id,
            'relation_type' => $payload['relation_type'] ?? 'reference',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (array) DB::table('document_relations')->where('id', $id)->first();
    }

    public function listComments(int $tenantId, int $documentId): array
    {
        return DB::table('document_comments')
            ->where('tenant_id', $tenantId)
            ->where('document_id', $documentId)
            ->orderByDesc('id')
            ->get()
            ->map(static fn ($row): array => (array) $row)
            ->all();
    }

    public function listActivities(int $tenantId, int $documentId): array
    {
        return DB::table('document_activities')
            ->where('tenant_id', $tenantId)
            ->where('document_id', $documentId)
            ->orderByDesc('id')
            ->get()
            ->map(static fn ($row): array => (array) $row)
            ->all();
    }

    public function listEvents(int $tenantId, int $documentId): array
    {
        return DB::table('document_events')
            ->where('tenant_id', $tenantId)
            ->where('document_id', $documentId)
            ->orderByDesc('id')
            ->get()
            ->map(static fn ($row): array => (array) $row)
            ->all();
    }

    public function listPermissions(int $tenantId, int $documentId): array
    {
        return DB::table('document_permissions')
            ->where('tenant_id', $tenantId)
            ->where('document_id', $documentId)
            ->orderByDesc('id')
            ->get()
            ->map(static fn ($row): array => (array) $row)
            ->all();
    }

    public function listRelations(int $tenantId, int $documentId): array
    {
        return DB::table('document_relations')
            ->where('tenant_id', $tenantId)
            ->where(function ($query) use ($documentId): void {
                $query->where('source_document_id', $documentId)
                    ->orWhere('target_document_id', $documentId);
            })
            ->orderByDesc('id')
            ->get()
            ->map(static fn ($row): array => (array) $row)
            ->all();
    }

    public function listAttachments(int $tenantId, int $documentId): array
    {
        return DB::table('document_attachments')
            ->where('tenant_id', $tenantId)
            ->where('document_id', $documentId)
            ->orderByDesc('id')
            ->get()
            ->map(static fn ($row): array => (array) $row)
            ->all();
    }

    public function removeAttachment(int $tenantId, int $documentId, int $attachmentId): bool
    {
        return DB::table('document_attachments')
            ->where('tenant_id', $tenantId)
            ->where('document_id', $documentId)
            ->where('id', $attachmentId)
            ->delete() > 0;
    }

    public function removeRelation(int $tenantId, int $documentId, int $relationId): bool
    {
        return DB::table('document_relations')
            ->where('tenant_id', $tenantId)
            ->where('id', $relationId)
            ->where(function ($query) use ($documentId): void {
                $query->where('source_document_id', $documentId)
                    ->orWhere('target_document_id', $documentId);
            })
            ->delete() > 0;
    }

    public function listVersions(int $tenantId, int $documentId): array
    {
        return DB::table('document_versions')
            ->where('tenant_id', $tenantId)
            ->where('document_id', $documentId)
            ->orderByDesc('version')
            ->get()
            ->map(static fn ($row): array => (array) $row)
            ->all();
    }

    public function getVersion(int $tenantId, int $documentId, int $versionId): ?array
    {
        $row = DB::table('document_versions')
            ->where('tenant_id', $tenantId)
            ->where('document_id', $documentId)
            ->where('id', $versionId)
            ->first();

        return $row === null ? null : (array) $row;
    }

    public function replacePermissions(int $tenantId, int $documentId, array $permissions): array
    {
        DB::table('document_permissions')
            ->where('tenant_id', $tenantId)
            ->where('document_id', $documentId)
            ->delete();

        foreach ($permissions as $permission) {
            DB::table('document_permissions')->insert([
                'tenant_id' => $tenantId,
                'document_id' => $documentId,
                'principal_type' => (string) ($permission['principal_type'] ?? 'role'),
                'principal_identifier' => (string) ($permission['principal_identifier'] ?? ''),
                'ability' => (string) ($permission['ability'] ?? 'view'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $this->listPermissions($tenantId, $documentId);
    }

    public function updateDocumentMetadata(int $tenantId, int $documentId, array $metadata): array
    {
        $this->assertDocumentExists($tenantId, $documentId);

        DB::transaction(function () use ($tenantId, $documentId, $metadata): void {
            foreach (array_values($metadata) as $index => $entry) {
                $fieldKey = (string) $entry['field_key'];
                $value = $entry['value'] ?? null;
                $valueType = (string) ($entry['value_type'] ?? $this->inferValueType($value));

                $definitionId = DB::table('document_metadata_definitions')
                    ->where('tenant_id', $tenantId)
                    ->where('entity_type', 'document')
                    ->where('metadata_key', $fieldKey)
                    ->value('id');

                if ($definitionId === null) {
                    $definitionId = DB::table('document_metadata_definitions')->insertGetId([
                        'tenant_id' => $tenantId,
                        'entity_type' => 'document',
                        'metadata_key' => $fieldKey,
                        'label' => (string) ($entry['label'] ?? str($fieldKey)->replace('_', ' ')->title()),
                        'data_type' => $valueType,
                        'is_required' => false,
                        'is_active' => true,
                        'display_order' => $index + 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('document_metadata_values')->updateOrInsert(
                    [
                        'tenant_id' => $tenantId,
                        'metadata_definition_id' => $definitionId,
                        'entity_type' => 'document',
                        'entity_id' => $documentId,
                    ],
                    [
                        ...$this->typedValueColumns($value),
                        'value_type' => $valueType,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ],
                );
            }

            $this->recordEvent($tenantId, $documentId, 'document.metadata_updated', [
                'field_count' => count($metadata),
            ]);
        });

        return $this->listDocumentMetadata($tenantId, $documentId);
    }

    public function listDocumentLines(int $tenantId, int $documentId): array
    {
        $this->assertDocumentExists($tenantId, $documentId);

        $orderColumn = Schema::hasColumn('document_items', 'line_no') ? 'line_no' : 'sequence';

        return DB::table('document_items')
            ->where('tenant_id', $tenantId)
            ->where('document_id', $documentId)
            ->orderBy($orderColumn)
            ->get()
            ->map(static fn ($row): array => [
                'id' => $row->id,
                'document_id' => $row->document_id,
                'line_no' => $row->line_no ?? $row->sequence ?? null,
                'line_type' => $row->line_type ?? $row->item_type ?? 'line',
                'item_label' => $row->item_label ?? null,
                'description' => $row->description ?? null,
                'quantity' => $row->quantity ?? null,
                'uom_label' => $row->uom_label ?? null,
                'unit_price' => $row->unit_price ?? null,
                'discount_amount' => $row->discount_amount ?? null,
                'tax_amount' => $row->tax_amount ?? null,
                'line_total' => $row->line_total ?? null,
                'source_line_type' => $row->source_line_type ?? null,
                'source_line_id' => $row->source_line_id ?? null,
                'display_order' => $row->display_order ?? $row->sequence ?? null,
                'created_at' => $row->created_at ?? null,
                'updated_at' => $row->updated_at ?? null,
            ])
            ->all();
    }

    public function createDocumentType(int $tenantId, array $payload): array
    {
        $id = DB::table('document_types')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $payload['organization_unit_id'] ?? null,
            'name' => (string) $payload['name'],
            'code' => (string) $payload['code'],
            'description' => $payload['description'] ?? null,
            'module_scope' => (string) ($payload['module_scope'] ?? 'shared'),
            'default_status' => (string) ($payload['default_status'] ?? 'draft'),
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'requires_source' => (bool) ($payload['requires_source'] ?? false),
            'supports_items' => (bool) ($payload['supports_items'] ?? true),
            'supports_attachments' => (bool) ($payload['supports_attachments'] ?? true),
            'supports_comments' => (bool) ($payload['supports_comments'] ?? true),
            'supports_versions' => (bool) ($payload['supports_versions'] ?? true),
            'supports_workflow' => (bool) ($payload['supports_workflow'] ?? true),
            'created_by' => $payload['created_by'] ?? null,
            'updated_by' => $payload['updated_by'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (array) DB::table('document_types')->where('id', $id)->first();
    }

    public function listDocumentTypes(int $tenantId): array
    {
        return DB::table('document_types')
            ->where(function ($query) use ($tenantId): void {
                $query->where('tenant_id', $tenantId)
                    ->orWhereNull('tenant_id');
            })
            ->orderBy('name')
            ->get()
            ->map(static fn ($row): array => (array) $row)
            ->all();
    }

    public function getDocumentType(int $tenantId, int $typeId): ?array
    {
        $row = DB::table('document_types')
            ->where('id', $typeId)
            ->where(function ($query) use ($tenantId): void {
                $query->where('tenant_id', $tenantId)
                    ->orWhereNull('tenant_id');
            })
            ->first();

        return $row === null ? null : (array) $row;
    }

    public function updateDocumentType(int $tenantId, int $typeId, array $payload): array
    {
        DB::table('document_types')
            ->where('id', $typeId)
            ->update([
                'name' => (string) ($payload['name'] ?? ''),
                'code' => (string) ($payload['code'] ?? ''),
                'description' => $payload['description'] ?? null,
                'module_scope' => (string) ($payload['module_scope'] ?? 'shared'),
                'default_status' => (string) ($payload['default_status'] ?? 'draft'),
                'is_active' => (bool) ($payload['is_active'] ?? true),
                'requires_source' => (bool) ($payload['requires_source'] ?? false),
                'supports_items' => (bool) ($payload['supports_items'] ?? true),
                'supports_attachments' => (bool) ($payload['supports_attachments'] ?? true),
                'supports_comments' => (bool) ($payload['supports_comments'] ?? true),
                'supports_versions' => (bool) ($payload['supports_versions'] ?? true),
                'supports_workflow' => (bool) ($payload['supports_workflow'] ?? true),
                'tenant_id' => $tenantId,
                'updated_at' => now(),
            ]);

        return (array) DB::table('document_types')->where('id', $typeId)->first();
    }

    public function createItemType(int $tenantId, array $payload): array
    {
        $id = DB::table('document_item_types')->insertGetId([
            'name' => (string) $payload['name'],
            'code' => (string) $payload['code'],
            'display_name' => (string) $payload['display_name'],
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (array) DB::table('document_item_types')->where('id', $id)->first();
    }

    public function listItemTypes(): array
    {
        return DB::table('document_item_types')
            ->orderBy('display_name')
            ->get()
            ->map(static fn ($row): array => (array) $row)
            ->all();
    }

    public function createDocumentDefinition(int $tenantId, array $payload): array
    {
        $fields = is_array($payload['fields'] ?? null) ? $payload['fields'] : [];
        $allowedItemTypes = is_array($payload['allowed_item_types'] ?? null) ? $payload['allowed_item_types'] : [];
        $sectionsPayload = $this->normalizeDefinitionSectionsPayload($payload);
        $settingsPayload = is_array($payload['settings'] ?? null) ? $payload['settings'] : [];
        $validationRules = is_array($payload['validation_rules'] ?? null) ? $payload['validation_rules'] : [];

        $id = DB::table('document_definitions')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $payload['organization_unit_id'] ?? null,
            'document_type_id' => (int) $payload['document_type_id'],
            'definition_code' => $payload['definition_code'] ?? ($payload['settings']['code'] ?? null),
            'version' => (int) ($payload['version'] ?? 1),
            'name' => (string) $payload['name'],
            'description' => $payload['description'] ?? ($payload['settings']['description'] ?? null),
            'source_module' => (string) ($payload['source_module'] ?? ($payload['settings']['source_module'] ?? 'shared')),
            'template_id' => isset($payload['template_id']) ? (int) $payload['template_id'] : null,
            'sequence_id' => isset($payload['sequence_id']) ? (int) $payload['sequence_id'] : null,
            'workflow_id' => isset($payload['workflow_id']) ? (int) $payload['workflow_id'] : null,
            'default_status' => (string) ($payload['default_status'] ?? 'draft'),
            'supports_versions' => (bool) ($payload['supports_versions'] ?? true),
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'created_by' => $payload['created_by'] ?? null,
            'updated_by' => $payload['updated_by'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($fields as $index => $field) {
            DB::table('document_definition_fields')->insert([
                'tenant_id' => $tenantId,
                'document_definition_id' => $id,
                'field_key' => (string) $field['field_key'],
                'label' => (string) ($field['label'] ?? $field['field_key']),
                'data_type' => (string) ($field['data_type'] ?? 'text'),
                'is_required' => (bool) ($field['is_required'] ?? false),
                'is_readonly' => (bool) ($field['is_readonly'] ?? false),
                'display_order' => (int) ($field['display_order'] ?? ($index + 1)),
                'default_value' => isset($field['default_value']) ? (string) $field['default_value'] : null,
                'validation_rule' => isset($field['validation_rule']) ? (string) $field['validation_rule'] : null,
                'section_key' => $field['section_key'] ?? null,
                'is_active' => (bool) ($field['is_active'] ?? true),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $allowedTypeRows = $this->resolveAllowedItemTypeRows($tenantId, $id, $allowedItemTypes);
        if ($allowedTypeRows !== []) {
            DB::table('document_definition_item_types')->insert($allowedTypeRows);
        }

        $definitionSettingRows = $this->buildTypedSettingRows(
            tenantId: $tenantId,
            definitionId: $id,
            settings: $settingsPayload,
            validationRules: $validationRules,
        );
        if ($definitionSettingRows !== []) {
            DB::table('document_definition_settings')->insert($definitionSettingRows);
        }

        $sectionRows = [];
        $sectionFieldRows = [];
        foreach ($sectionsPayload as $sectionIndex => $section) {
            $sectionId = DB::table('document_definition_sections')->insertGetId([
                'tenant_id' => $tenantId,
                'document_definition_id' => $id,
                'section_key' => (string) ($section['section_key'] ?? ('section_' . ($sectionIndex + 1))),
                'label' => (string) ($section['label'] ?? 'Section ' . ($sectionIndex + 1)),
                'display_order' => (int) ($section['display_order'] ?? ($sectionIndex + 1)),
                'is_visible' => (bool) ($section['is_visible'] ?? true),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $sectionRows[] = $sectionId;

            $fieldKeys = is_array($section['field_keys'] ?? null) ? $section['field_keys'] : [];
            if ($fieldKeys === []) {
                continue;
            }

            $definitionFields = DB::table('document_definition_fields')
                ->where('tenant_id', $tenantId)
                ->where('document_definition_id', $id)
                ->whereIn('field_key', $fieldKeys)
                ->get(['id', 'field_key'])
                ->keyBy('field_key');

            foreach ($fieldKeys as $order => $fieldKey) {
                $field = $definitionFields->get($fieldKey);
                if ($field === null) {
                    continue;
                }
                $sectionFieldRows[] = [
                    'tenant_id' => $tenantId,
                    'section_id' => $sectionId,
                    'field_definition_id' => $field->id,
                    'display_order' => $order + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if ($sectionFieldRows !== []) {
            DB::table('document_definition_section_fields')->insert($sectionFieldRows);
        }

        return [
            'definition' => (array) DB::table('document_definitions')->where('id', $id)->first(),
            'fields' => DB::table('document_definition_fields')
                ->where('document_definition_id', $id)
                ->orderBy('display_order')
                ->get()
                ->map(static fn ($row): array => (array) $row)
                ->all(),
            'allowed_item_types' => DB::table('document_definition_item_types as ddit')
                ->join('document_item_types as dit', 'dit.id', '=', 'ddit.item_type_id')
                ->where('ddit.tenant_id', $tenantId)
                ->where('ddit.document_definition_id', $id)
                ->orderBy('ddit.display_order')
                ->get(['ddit.*', 'dit.code as item_type_code'])
                ->map(static fn ($row): array => (array) $row)
                ->all(),
            'settings' => DB::table('document_definition_settings')
                ->where('tenant_id', $tenantId)
                ->where('document_definition_id', $id)
                ->orderBy('id')
                ->get()
                ->map(static fn ($row): array => (array) $row)
                ->all(),
            'sections' => DB::table('document_definition_sections')
                ->where('tenant_id', $tenantId)
                ->where('document_definition_id', $id)
                ->orderBy('display_order')
                ->get()
                ->map(static fn ($row): array => (array) $row)
                ->all(),
        ];
    }

    public function listDocumentDefinitions(int $tenantId): array
    {
        $definitions = DB::table('document_definitions as dd')
            ->join('document_types as dt', 'dt.id', '=', 'dd.document_type_id')
            ->where('dd.tenant_id', $tenantId)
            ->orderByDesc('dd.id')
            ->get(['dd.*', 'dt.name as document_type_name', 'dt.code as document_type_code'])
            ->map(static fn ($row): array => (array) $row);

        if ($definitions->isEmpty()) {
            return [];
        }

        $definitionIds = $definitions->pluck('id')->all();

        $fieldsByDefinition = DB::table('document_definition_fields')
            ->where('tenant_id', $tenantId)
            ->whereIn('document_definition_id', $definitionIds)
            ->orderBy('display_order')
            ->get()
            ->groupBy('document_definition_id');

        $itemTypesByDefinition = DB::table('document_definition_item_types as ddit')
            ->join('document_item_types as dit', 'dit.id', '=', 'ddit.item_type_id')
            ->where('ddit.tenant_id', $tenantId)
            ->whereIn('ddit.document_definition_id', $definitionIds)
            ->orderBy('ddit.display_order')
            ->get(['ddit.document_definition_id', 'ddit.display_order', 'dit.code', 'dit.display_name'])
            ->groupBy('document_definition_id');

        return $definitions->map(function (array $definition) use ($fieldsByDefinition, $itemTypesByDefinition): array {
            $id = (int) $definition['id'];
            $definition['fields'] = ($fieldsByDefinition->get($id) ?? collect())
                ->map(static fn ($row): array => (array) $row)
                ->values()
                ->all();
            $definition['allowed_item_types'] = ($itemTypesByDefinition->get($id) ?? collect())
                ->map(static fn ($row): array => [
                    'code' => (string) $row->code,
                    'display_name' => (string) $row->display_name,
                    'display_order' => (int) $row->display_order,
                ])
                ->values()
                ->all();

            return $definition;
        })->all();
    }

    public function getDocumentDefinition(int $tenantId, int $definitionId): ?array
    {
        $definitions = $this->listDocumentDefinitions($tenantId);

        foreach ($definitions as $definition) {
            if ((int) $definition['id'] === $definitionId) {
                return $definition;
            }
        }

        return null;
    }

    public function updateDocumentDefinition(int $tenantId, int $definitionId, array $payload): array
    {
        DB::table('document_definitions')
            ->where('tenant_id', $tenantId)
            ->where('id', $definitionId)
            ->update([
                'document_type_id' => (int) ($payload['document_type_id'] ?? 0),
                'definition_code' => $payload['definition_code'] ?? ($payload['settings']['code'] ?? null),
                'version' => (int) ($payload['version'] ?? 1),
                'name' => (string) ($payload['name'] ?? ''),
                'description' => $payload['description'] ?? ($payload['settings']['description'] ?? null),
                'source_module' => (string) ($payload['source_module'] ?? ($payload['settings']['source_module'] ?? 'shared')),
                'template_id' => isset($payload['template_id']) ? (int) $payload['template_id'] : null,
                'sequence_id' => isset($payload['sequence_id']) ? (int) $payload['sequence_id'] : null,
                'workflow_id' => isset($payload['workflow_id']) ? (int) $payload['workflow_id'] : null,
                'default_status' => (string) ($payload['default_status'] ?? 'draft'),
                'supports_versions' => (bool) ($payload['supports_versions'] ?? true),
                'is_active' => (bool) ($payload['is_active'] ?? true),
                'updated_at' => now(),
            ]);

        return $this->getDocumentDefinition($tenantId, $definitionId) ?? [];
    }

    public function listTemplates(int $tenantId): array
    {
        if (! Schema::hasTable('document_templates')) {
            return [];
        }

        return DB::table('document_templates')
            ->where(function ($query) use ($tenantId): void {
                $query->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
            })
            ->orderBy('template_name')
            ->get()
            ->map(static fn ($row): array => (array) $row)
            ->all();
    }

    public function getTemplate(int $tenantId, int $templateId): ?array
    {
        $row = DB::table('document_templates')
            ->where(function ($query) use ($tenantId): void {
                $query->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
            })
            ->where('id', $templateId)
            ->first();

        return $row === null ? null : (array) $row;
    }

    public function createTemplate(int $tenantId, array $payload): array
    {
        $id = DB::table('document_templates')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $payload['organization_unit_id'] ?? null,
            'document_type_id' => $payload['document_type_id'] ?? null,
            'template_code' => (string) $payload['template_code'],
            'template_name' => (string) $payload['template_name'],
            'layout_type' => (string) ($payload['layout_type'] ?? 'html'),
            'header_content' => $payload['header_content'] ?? null,
            'body_content' => $payload['body_content'] ?? null,
            'footer_content' => $payload['footer_content'] ?? null,
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'created_by' => $payload['created_by'] ?? null,
            'updated_by' => $payload['updated_by'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (array) DB::table('document_templates')->where('id', $id)->first();
    }

    public function updateTemplate(int $tenantId, int $templateId, array $payload): array
    {
        DB::table('document_templates')
            ->where('tenant_id', $tenantId)
            ->where('id', $templateId)
            ->update([
                'document_type_id' => $payload['document_type_id'] ?? null,
                'template_code' => (string) ($payload['template_code'] ?? ''),
                'template_name' => (string) ($payload['template_name'] ?? ''),
                'layout_type' => (string) ($payload['layout_type'] ?? 'html'),
                'header_content' => $payload['header_content'] ?? null,
                'body_content' => $payload['body_content'] ?? null,
                'footer_content' => $payload['footer_content'] ?? null,
                'is_active' => (bool) ($payload['is_active'] ?? true),
                'updated_by' => $payload['updated_by'] ?? null,
                'updated_at' => now(),
            ]);

        return (array) DB::table('document_templates')->where('id', $templateId)->first();
    }

    public function createRenderLog(int $tenantId, array $payload): array
    {
        $id = DB::table('document_render_logs')->insertGetId([
            'tenant_id' => $tenantId,
            'document_id' => $payload['document_id'] ?? null,
            'document_template_id' => $payload['document_template_id'] ?? null,
            'render_type' => (string) ($payload['render_type'] ?? 'preview'),
            'status' => (string) ($payload['status'] ?? 'rendered'),
            'rendered_by' => $payload['rendered_by'] ?? null,
            'rendered_at' => now(),
            'message' => $payload['message'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (array) DB::table('document_render_logs')->where('id', $id)->first();
    }

    public function createItemDefinition(int $tenantId, array $payload): array
    {
        $fields = is_array($payload['fields'] ?? null) ? $payload['fields'] : [];
        $settingsPayload = is_array($payload['settings'] ?? null) ? $payload['settings'] : [];
        $validationRules = is_array($payload['validation_rules'] ?? null) ? $payload['validation_rules'] : [];

        $id = DB::table('document_item_definitions')->insertGetId([
            'tenant_id' => $tenantId,
            'item_type_id' => (int) $payload['item_type_id'],
            'version' => (int) ($payload['version'] ?? 1),
            'name' => (string) $payload['name'],
            'calculation_rule' => null,
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($fields as $index => $field) {
            DB::table('document_item_definition_fields')->insert([
                'tenant_id' => $tenantId,
                'document_item_definition_id' => $id,
                'field_key' => (string) $field['field_key'],
                'label' => (string) ($field['label'] ?? $field['field_key']),
                'data_type' => (string) ($field['data_type'] ?? 'text'),
                'is_required' => (bool) ($field['is_required'] ?? false),
                'display_order' => (int) ($field['display_order'] ?? ($index + 1)),
                'default_value' => isset($field['default_value']) ? (string) $field['default_value'] : null,
                'validation_rule' => isset($field['validation_rule']) ? (string) $field['validation_rule'] : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $rows = [];
        foreach ($settingsPayload as $settingGroup => $settings) {
            if (! is_array($settings)) {
                continue;
            }
            foreach ($settings as $key => $value) {
                $rows[] = [
                    'tenant_id' => $tenantId,
                    'document_item_definition_id' => $id,
                    'setting_group' => (string) $settingGroup,
                    'setting_key' => (string) $key,
                    ...$this->typedValueColumns($value),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach ($validationRules as $fieldKey => $rule) {
            $rows[] = [
                'tenant_id' => $tenantId,
                'document_item_definition_id' => $id,
                'setting_group' => 'validation_rules',
                'setting_key' => (string) $fieldKey,
                ...$this->typedValueColumns((string) $rule),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($rows !== []) {
            DB::table('document_item_definition_settings')->insert($rows);
        }

        return [
            'definition' => (array) DB::table('document_item_definitions')->where('id', $id)->first(),
            'fields' => DB::table('document_item_definition_fields')
                ->where('document_item_definition_id', $id)
                ->orderBy('display_order')
                ->get()
                ->map(static fn ($row): array => (array) $row)
                ->all(),
            'settings' => DB::table('document_item_definition_settings')
                ->where('tenant_id', $tenantId)
                ->where('document_item_definition_id', $id)
                ->orderBy('id')
                ->get()
                ->map(static fn ($row): array => (array) $row)
                ->all(),
        ];
    }

    public function listItemDefinitions(int $tenantId): array
    {
        $definitions = DB::table('document_item_definitions as did')
            ->join('document_item_types as dit', 'dit.id', '=', 'did.item_type_id')
            ->where('did.tenant_id', $tenantId)
            ->orderByDesc('did.id')
            ->get(['did.*', 'dit.name as item_type_name', 'dit.code as item_type_code'])
            ->map(static fn ($row): array => (array) $row);

        if ($definitions->isEmpty()) {
            return [];
        }

        $definitionIds = $definitions->pluck('id')->all();
        $fieldsByDefinition = DB::table('document_item_definition_fields')
            ->where('tenant_id', $tenantId)
            ->whereIn('document_item_definition_id', $definitionIds)
            ->orderBy('display_order')
            ->get()
            ->groupBy('document_item_definition_id');

        return $definitions->map(function (array $definition) use ($fieldsByDefinition): array {
            $id = (int) $definition['id'];
            $definition['fields'] = ($fieldsByDefinition->get($id) ?? collect())
                ->map(static fn ($row): array => (array) $row)
                ->values()
                ->all();

            return $definition;
        })->all();
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function persistDocumentFieldValues(int $tenantId, int $documentId, array $values): void
    {
        DB::table('document_field_values')
            ->where('tenant_id', $tenantId)
            ->where('document_id', $documentId)
            ->delete();

        foreach ($values as $fieldKey => $value) {
            if (! is_scalar($value) && $value !== null) {
                continue;
            }

            DB::table('document_field_values')->insert([
                'tenant_id' => $tenantId,
                'document_id' => $documentId,
                'field_key' => (string) $fieldKey,
                ...$this->typedValueColumns($value),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function persistDocumentItemFieldValues(int $tenantId, int $documentItemId, array $values): void
    {
        DB::table('document_item_field_values')
            ->where('tenant_id', $tenantId)
            ->where('document_item_id', $documentItemId)
            ->delete();

        foreach ($values as $fieldKey => $value) {
            if (! is_scalar($value) && $value !== null) {
                continue;
            }

            DB::table('document_item_field_values')->insert([
                'tenant_id' => $tenantId,
                'document_item_id' => $documentItemId,
                'field_key' => (string) $fieldKey,
                ...$this->typedValueColumns($value),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function assertDocumentExists(int $tenantId, int $documentId): void
    {
        DB::table('documents')
            ->where('tenant_id', $tenantId)
            ->where('id', $documentId)
            ->exists() || abort(404, 'Document not found.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function listDocumentMetadata(int $tenantId, int $documentId): array
    {
        return DB::table('document_metadata_values as values')
            ->join('document_metadata_definitions as definitions', 'definitions.id', '=', 'values.metadata_definition_id')
            ->where('values.tenant_id', $tenantId)
            ->where('values.entity_type', 'document')
            ->where('values.entity_id', $documentId)
            ->orderBy('definitions.display_order')
            ->get([
                'values.id',
                'definitions.metadata_key as field_key',
                'definitions.label',
                'definitions.data_type',
                'values.value_type',
                'values.value_string',
                'values.value_integer',
                'values.value_decimal',
                'values.value_boolean',
                'values.value_date',
                'values.value_datetime',
                'values.value_text',
                'values.value_file_id',
                'values.value_reference_type',
                'values.value_reference_id',
                'values.created_at',
                'values.updated_at',
            ])
            ->map(fn ($row): array => [
                'id' => $row->id,
                'field_key' => $row->field_key,
                'label' => $row->label,
                'value_type' => $row->value_type,
                'value' => $this->fromTypedValueRecord($row),
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ])
            ->all();
    }

    private function inferValueType(mixed $value): string
    {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_float($value) => 'decimal',
            is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 => 'date',
            is_string($value) && mb_strlen($value) > 255 => 'text',
            default => 'string',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function typedValueColumns(mixed $value): array
    {
        $columns = [
            'value_type' => 'string',
            'value_string' => null,
            'value_integer' => null,
            'value_decimal' => null,
            'value_boolean' => null,
            'value_date' => null,
            'value_datetime' => null,
            'value_text' => null,
            'value_file_id' => null,
            'value_reference_type' => null,
            'value_reference_id' => null,
        ];

        if ($value === null) {
            return $columns;
        }

        if (is_bool($value)) {
            $columns['value_type'] = 'boolean';
            $columns['value_boolean'] = $value;

            return $columns;
        }

        if (is_int($value) || is_float($value)) {
            if (is_int($value)) {
                $columns['value_type'] = 'integer';
                $columns['value_integer'] = $value;

                return $columns;
            }

            $columns['value_type'] = 'decimal';
            $columns['value_decimal'] = (string) number_format((float) $value, 4, '.', '');

            return $columns;
        }

        if (is_string($value) && is_numeric($value)) {
            if (str_contains($value, '.')) {
                $columns['value_type'] = 'decimal';
                $columns['value_decimal'] = (string) number_format((float) $value, 4, '.', '');

                return $columns;
            }

            $columns['value_type'] = 'integer';
            $columns['value_integer'] = (int) $value;

            return $columns;
        }

        $stringValue = (string) $value;
        $columns['value_string'] = mb_substr($stringValue, 0, 255);
        if (mb_strlen($stringValue) > 255) {
            $columns['value_text'] = $stringValue;
            $columns['value_type'] = 'text';

            return $columns;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $stringValue) === 1) {
            $columns['value_type'] = 'date';
            $columns['value_date'] = $stringValue;

            return $columns;
        }

        if (strtotime($stringValue) !== false && str_contains($stringValue, ':')) {
            $columns['value_type'] = 'datetime';
            $columns['value_datetime'] = date('Y-m-d H:i:s', strtotime($stringValue));

            return $columns;
        }

        return $columns;
    }

    private function fromTypedValueRecord(object $row): mixed
    {
        return match ((string) ($row->value_type ?? 'string')) {
            'boolean' => $row->value_boolean !== null ? (bool) $row->value_boolean : null,
            'integer' => $row->value_integer !== null ? (int) $row->value_integer : null,
            'decimal' => $row->value_decimal !== null ? (string) $row->value_decimal : null,
            'date' => $row->value_date,
            'datetime' => $row->value_datetime,
            'text' => $row->value_text,
            'reference' => [
                'type' => $row->value_reference_type,
                'id' => $row->value_reference_id,
            ],
            'file' => $row->value_file_id,
            default => $row->value_text ?? $row->value_string,
        };
    }

    /**
     * @param  array<int|string, mixed>  $attributes
     */
    private function recordEvent(
        int $tenantId,
        int $documentId,
        string $eventType,
        array $attributes = [],
        ?int $performedBy = null,
    ): int {
        $eventId = DB::table('document_events')->insertGetId([
            'tenant_id' => $tenantId,
            'document_id' => $documentId,
            'event_type' => $eventType,
            'performed_by' => $performedBy,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $attributeRows = [];
        foreach ($this->flattenAttributes($attributes) as $key => $value) {
            $attributeRows[] = [
                'tenant_id' => $tenantId,
                'event_id' => $eventId,
                'attribute_key' => $key,
                ...$this->typedValueColumns($value),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($attributeRows !== []) {
            DB::table('document_event_attributes')->insert($attributeRows);
        }

        return $eventId;
    }

    /**
     * @param  array<int|string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function flattenAttributes(array $attributes, string $prefix = ''): array
    {
        $flat = [];

        foreach ($attributes as $key => $value) {
            $attributeKey = $prefix === '' ? (string) $key : $prefix . '.' . $key;
            if (is_array($value)) {
                $flat += $this->flattenAttributes($value, $attributeKey);
                continue;
            }

            if (! is_scalar($value) && $value !== null) {
                continue;
            }

            $flat[$attributeKey] = $value;
        }

        return $flat;
    }

    private function createVersionSnapshot(
        int $tenantId,
        int $documentId,
        ?int $forcedVersion,
        string $reason,
    ): void {
        $nextVersion = $forcedVersion;
        if ($nextVersion === null) {
            $nextVersion = ((int) DB::table('document_versions')
                ->where('tenant_id', $tenantId)
                ->where('document_id', $documentId)
                ->max('version')) + 1;
        }

        $versionId = DB::table('document_versions')->insertGetId([
            'tenant_id' => $tenantId,
            'document_id' => $documentId,
            'version' => $nextVersion,
            'changed_by' => null,
            'change_reason' => $reason,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $documentFieldValues = DB::table('document_field_values')
            ->where('tenant_id', $tenantId)
            ->where('document_id', $documentId)
            ->get();

        $documentVersionFieldRows = [];
        foreach ($documentFieldValues as $valueRow) {
            $documentVersionFieldRows[] = [
                'tenant_id' => $tenantId,
                'document_version_id' => $versionId,
                'field_definition_id' => $valueRow->field_definition_id,
                'field_key' => $valueRow->field_key,
                'value_type' => $valueRow->value_type,
                'value_string' => $valueRow->value_string,
                'value_integer' => $valueRow->value_integer,
                'value_decimal' => $valueRow->value_decimal,
                'value_boolean' => $valueRow->value_boolean,
                'value_date' => $valueRow->value_date,
                'value_datetime' => $valueRow->value_datetime,
                'value_text' => $valueRow->value_text,
                'value_file_id' => $valueRow->value_file_id,
                'value_reference_type' => $valueRow->value_reference_type,
                'value_reference_id' => $valueRow->value_reference_id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($documentVersionFieldRows !== []) {
            DB::table('document_version_field_values')->insert($documentVersionFieldRows);
        }

        $items = DB::table('document_items')
            ->where('tenant_id', $tenantId)
            ->where('document_id', $documentId)
            ->orderBy('sequence')
            ->get();

        $snapshotIdBySourceItemId = [];
        foreach ($items as $item) {
            $snapshotId = DB::table('document_version_item_snapshots')->insertGetId([
                'tenant_id' => $tenantId,
                'document_version_id' => $versionId,
                'source_item_id' => $item->id,
                'item_type' => $item->item_type,
                'description' => $item->description,
                'line_total' => $item->line_total,
                'sequence' => $item->sequence,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $snapshotIdBySourceItemId[(int) $item->id] = $snapshotId;
        }

        if ($snapshotIdBySourceItemId === []) {
            return;
        }

        $itemFieldValues = DB::table('document_item_field_values')
            ->where('tenant_id', $tenantId)
            ->whereIn('document_item_id', array_keys($snapshotIdBySourceItemId))
            ->get();

        $versionItemFieldRows = [];
        foreach ($itemFieldValues as $valueRow) {
            $snapshotId = $snapshotIdBySourceItemId[(int) $valueRow->document_item_id] ?? null;
            if ($snapshotId === null) {
                continue;
            }

            $versionItemFieldRows[] = [
                'tenant_id' => $tenantId,
                'version_item_snapshot_id' => $snapshotId,
                'field_definition_id' => $valueRow->field_definition_id,
                'field_key' => $valueRow->field_key,
                'value_type' => $valueRow->value_type,
                'value_string' => $valueRow->value_string,
                'value_integer' => $valueRow->value_integer,
                'value_decimal' => $valueRow->value_decimal,
                'value_boolean' => $valueRow->value_boolean,
                'value_date' => $valueRow->value_date,
                'value_datetime' => $valueRow->value_datetime,
                'value_text' => $valueRow->value_text,
                'value_file_id' => $valueRow->value_file_id,
                'value_reference_type' => $valueRow->value_reference_type,
                'value_reference_id' => $valueRow->value_reference_id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($versionItemFieldRows !== []) {
            DB::table('document_version_item_field_values')->insert($versionItemFieldRows);
        }
    }

    /**
     * @param  array<int, mixed>  $allowedItemTypes
     * @return array<int, array<string, mixed>>
     */
    private function resolveAllowedItemTypeRows(int $tenantId, int $definitionId, array $allowedItemTypes): array
    {
        if ($allowedItemTypes === []) {
            return [];
        }

        $codes = [];
        $ids = [];
        foreach ($allowedItemTypes as $entry) {
            if (is_int($entry) || (is_string($entry) && ctype_digit($entry))) {
                $ids[] = (int) $entry;
                continue;
            }

            if (is_string($entry) && $entry !== '') {
                $codes[] = $entry;
            }
        }

        if ($ids === [] && $codes === []) {
            return [];
        }

        $itemsQuery = DB::table('document_item_types')->select(['id', 'code']);
        if ($ids !== []) {
            $itemsQuery->orWhereIn('id', $ids);
        }
        if ($codes !== []) {
            $itemsQuery->orWhereIn('code', $codes);
        }

        /** @var Collection<int, object> $rows */
        $rows = $itemsQuery->get();
        $itemIds = $rows->pluck('id')->all();

        $result = [];
        foreach ($itemIds as $order => $itemTypeId) {
            $result[] = [
                'tenant_id' => $tenantId,
                'document_definition_id' => $definitionId,
                'item_type_id' => (int) $itemTypeId,
                'display_order' => $order + 1,
                'is_required' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    private function normalizeDefinitionSectionsPayload(array $payload): array
    {
        if (is_array($payload['sections'] ?? null)) {
            return $payload['sections'];
        }

        $layoutSections = $payload['form_layout']['sections'] ?? null;
        if (! is_array($layoutSections)) {
            return [];
        }

        return array_values(array_map(static function (array $section, int $index): array {
            return [
                'section_key' => (string) ($section['section_key'] ?? ('section_' . ($index + 1))),
                'label' => (string) ($section['title'] ?? $section['label'] ?? ('Section ' . ($index + 1))),
                'display_order' => (int) ($section['display_order'] ?? ($index + 1)),
                'is_visible' => (bool) ($section['is_visible'] ?? true),
                'field_keys' => is_array($section['fields'] ?? null) ? $section['fields'] : [],
            ];
        }, $layoutSections, array_keys($layoutSections)));
    }

    /**
     * @param  array<string, mixed>  $settings
     * @param  array<string, mixed>  $validationRules
     * @return array<int, array<string, mixed>>
     */
    private function buildTypedSettingRows(
        int $tenantId,
        int $definitionId,
        array $settings,
        array $validationRules,
    ): array {
        $rows = [];

        foreach ($settings as $group => $groupSettings) {
            if (! is_array($groupSettings)) {
                continue;
            }

            foreach ($groupSettings as $settingKey => $value) {
                $rows[] = [
                    'tenant_id' => $tenantId,
                    'document_definition_id' => $definitionId,
                    'setting_group' => (string) $group,
                    'setting_key' => (string) $settingKey,
                    ...$this->typedValueColumns($value),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach ($validationRules as $fieldKey => $rule) {
            $rows[] = [
                'tenant_id' => $tenantId,
                'document_definition_id' => $definitionId,
                'setting_group' => 'validation_rules',
                'setting_key' => (string) $fieldKey,
                ...$this->typedValueColumns((string) $rule),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $rows;
    }

    private function requireDocument(int $tenantId, int $documentId): object
    {
        $document = DB::table('documents')
            ->where('tenant_id', $tenantId)
            ->where('id', $documentId)
            ->first();

        if ($document === null) {
            throw new \RuntimeException('Document not found for current tenant context.');
        }

        return $document;
    }
}
