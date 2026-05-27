<?php

namespace Modules\Document\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
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
            'data' => $aggregate->document->data,
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
                'data' => $item->data,
            ]);

            $this->persistDocumentItemFieldValues($aggregate->document->tenantId, $itemModel->id, $item->data);
        }

        DB::table('document_versions')->insert([
            'tenant_id' => $aggregate->document->tenantId,
            'document_id' => $model->id,
            'version' => 1,
            'document_snapshot' => json_encode($model->fresh()->toArray(), JSON_THROW_ON_ERROR),
            'items_snapshot' => json_encode($model->items()->get()->toArray(), JSON_THROW_ON_ERROR),
            'changed_by' => null,
            'change_reason' => 'initial_create',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('document_events')->insert([
            'tenant_id' => $aggregate->document->tenantId,
            'document_id' => $model->id,
            'event_type' => 'document.created',
            'payload' => json_encode(['status' => $model->status], JSON_THROW_ON_ERROR),
            'performed_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
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
            data: $model->data ?? [],
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
            data: $item->data ?? [],
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
            'data' => $aggregate->document->data,
            'notes' => $aggregate->document->notes,
            'updated_by' => $aggregate->document->updatedBy,
        ]);

        $this->persistDocumentFieldValues($aggregate->document->tenantId, $model->id, $aggregate->document->data);

        $nextVersion = (int) DB::table('document_versions')
            ->where('document_id', $model->id)
            ->max('version') + 1;

        DB::table('document_versions')->insert([
            'tenant_id' => $aggregate->document->tenantId,
            'document_id' => $model->id,
            'version' => $nextVersion,
            'document_snapshot' => json_encode($model->fresh()->toArray(), JSON_THROW_ON_ERROR),
            'items_snapshot' => json_encode($model->items()->get()->toArray(), JSON_THROW_ON_ERROR),
            'changed_by' => null,
            'change_reason' => 'update',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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

        DB::table('document_events')->insert([
            'tenant_id' => $aggregate->document->tenantId,
            'document_id' => $model->id,
            'event_type' => 'document.status_changed',
            'payload' => json_encode([
                'from_status' => $previousStatus,
                'to_status' => $aggregate->document->status,
            ], JSON_THROW_ON_ERROR),
            'performed_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
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

        DB::table('document_events')->insert([
            'tenant_id' => $document->tenant_id,
            'document_id' => $documentId,
            'event_type' => 'document.attachment_uploaded',
            'payload' => json_encode(['attachment_id' => $attachment->id], JSON_THROW_ON_ERROR),
            'performed_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
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

        $id = DB::table('document_events')->insertGetId([
            'tenant_id' => $document->tenant_id,
            'document_id' => $documentId,
            'event_type' => (string) $payload['event_type'],
            'payload' => json_encode($payload['payload'] ?? [], JSON_THROW_ON_ERROR),
            'performed_by' => $payload['performed_by'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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

    public function createDocumentType(int $tenantId, array $payload): array
    {
        $id = DB::table('document_types')->insertGetId([
            'name' => (string) $payload['name'],
            'code' => (string) $payload['code'],
            'default_status' => (string) ($payload['default_status'] ?? 'draft'),
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'requires_source' => (bool) ($payload['requires_source'] ?? false),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (array) DB::table('document_types')->where('id', $id)->first();
    }

    public function listDocumentTypes(): array
    {
        return DB::table('document_types')
            ->orderBy('name')
            ->get()
            ->map(static fn ($row): array => (array) $row)
            ->all();
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
        $allowedItemTypes = is_array($payload['allowed_item_types'] ?? null)
            ? $payload['allowed_item_types']
            : [];

        $headerSchema = [];
        foreach ($fields as $field) {
            $key = (string) ($field['field_key'] ?? '');
            if ($key === '') {
                continue;
            }

            $headerSchema[$key] = [
                'required' => (bool) ($field['is_required'] ?? false),
                'type' => (string) ($field['data_type'] ?? 'text'),
            ];
        }

        $id = DB::table('document_definitions')->insertGetId([
            'tenant_id' => $tenantId,
            'document_type_id' => (int) $payload['document_type_id'],
            'version' => (int) ($payload['version'] ?? 1),
            'name' => (string) $payload['name'],
            'header_schema' => json_encode($headerSchema, JSON_THROW_ON_ERROR),
            'allowed_item_types' => json_encode($allowedItemTypes, JSON_THROW_ON_ERROR),
            'validation_rules' => json_encode($payload['validation_rules'] ?? [], JSON_THROW_ON_ERROR),
            'form_layout' => json_encode($payload['form_layout'] ?? [], JSON_THROW_ON_ERROR),
            'is_active' => (bool) ($payload['is_active'] ?? true),
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
                'display_order' => (int) ($field['display_order'] ?? ($index + 1)),
                'default_value' => isset($field['default_value']) ? (string) $field['default_value'] : null,
                'validation_rule' => isset($field['validation_rule']) ? (string) $field['validation_rule'] : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return [
            'definition' => (array) DB::table('document_definitions')->where('id', $id)->first(),
            'fields' => DB::table('document_definition_fields')
                ->where('document_definition_id', $id)
                ->orderBy('display_order')
                ->get()
                ->map(static fn ($row): array => (array) $row)
                ->all(),
        ];
    }

    public function listDocumentDefinitions(int $tenantId): array
    {
        return DB::table('document_definitions as dd')
            ->join('document_types as dt', 'dt.id', '=', 'dd.document_type_id')
            ->where('dd.tenant_id', $tenantId)
            ->orderByDesc('dd.id')
            ->get(['dd.*', 'dt.name as document_type_name', 'dt.code as document_type_code'])
            ->map(static fn ($row): array => (array) $row)
            ->all();
    }

    public function createItemDefinition(int $tenantId, array $payload): array
    {
        $fields = is_array($payload['fields'] ?? null) ? $payload['fields'] : [];
        $fieldSchema = [];

        foreach ($fields as $field) {
            $key = (string) ($field['field_key'] ?? '');
            if ($key === '') {
                continue;
            }

            $fieldSchema[$key] = [
                'required' => (bool) ($field['is_required'] ?? false),
                'type' => (string) ($field['data_type'] ?? 'text'),
            ];
        }

        $id = DB::table('document_item_definitions')->insertGetId([
            'tenant_id' => $tenantId,
            'item_type_id' => (int) $payload['item_type_id'],
            'version' => (int) ($payload['version'] ?? 1),
            'name' => (string) $payload['name'],
            'field_schema' => json_encode($fieldSchema, JSON_THROW_ON_ERROR),
            'validation_rules' => json_encode($payload['validation_rules'] ?? [], JSON_THROW_ON_ERROR),
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

        return [
            'definition' => (array) DB::table('document_item_definitions')->where('id', $id)->first(),
            'fields' => DB::table('document_item_definition_fields')
                ->where('document_item_definition_id', $id)
                ->orderBy('display_order')
                ->get()
                ->map(static fn ($row): array => (array) $row)
                ->all(),
        ];
    }

    public function listItemDefinitions(int $tenantId): array
    {
        return DB::table('document_item_definitions as did')
            ->join('document_item_types as dit', 'dit.id', '=', 'did.item_type_id')
            ->where('did.tenant_id', $tenantId)
            ->orderByDesc('did.id')
            ->get(['did.*', 'dit.name as item_type_name', 'dit.code as item_type_code'])
            ->map(static fn ($row): array => (array) $row)
            ->all();
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

    /**
     * @return array<string, mixed>
     */
    private function typedValueColumns(mixed $value): array
    {
        $columns = [
            'value_type' => 'string',
            'value_string' => null,
            'value_number' => null,
            'value_boolean' => null,
            'value_date' => null,
            'value_datetime' => null,
            'value_text' => null,
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
            $columns['value_type'] = 'number';
            $columns['value_number'] = (string) number_format((float) $value, 4, '.', '');

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
