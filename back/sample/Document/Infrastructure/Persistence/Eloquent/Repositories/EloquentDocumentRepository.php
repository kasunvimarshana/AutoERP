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

        foreach ($aggregate->items as $item) {
            DocumentItemModel::create([
                'document_id' => $model->id,
                'item_type' => $item->itemType,
                'description' => $item->description,
                'line_total' => $item->lineTotal,
                'sequence' => $item->sequence,
                'data' => $item->data,
            ]);
        }

        DB::table('document_versions')->insert([
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
            'document_id' => $model->id,
            'event_type' => 'document.created',
            'payload' => json_encode(['status' => $model->status], JSON_THROW_ON_ERROR),
            'performed_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('document_status_histories')->insert([
            'document_id' => $model->id,
            'from_status' => null,
            'to_status' => $model->status,
            'action_name' => 'create',
            'performed_by' => null,
            'reason' => 'Document created',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $saved = $this->findById($model->id);

        return $saved ?? $aggregate;
    }

    public function findById(int $id): ?DocumentAggregate
    {
        $model = DocumentModel::query()
            ->with(['items', 'attachments'])
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
        $model = DocumentModel::query()->findOrFail($aggregate->document->id);
        $previousStatus = $model->status;

        $model->update([
            'status' => $aggregate->document->status,
            'data' => $aggregate->document->data,
            'notes' => $aggregate->document->notes,
            'updated_by' => $aggregate->document->updatedBy,
        ]);

        $nextVersion = (int) DB::table('document_versions')
            ->where('document_id', $model->id)
            ->max('version') + 1;

        DB::table('document_versions')->insert([
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

        return $this->findById($model->id) ?? $aggregate;
    }

    public function delete(int $id): bool
    {
        $model = DocumentModel::query()->findOrFail($id);

        return (bool) $model->delete();
    }

    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return DocumentModel::query()
            ->with('type')
            ->when(isset($filters['tenant_id']), fn ($query) => $query->where('tenant_id', $filters['tenant_id']))
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
        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $payload['uploaded_file'];
        $path = $uploadedFile->storeAs(
            $payload['directory'],
            $payload['stored_name'],
            $payload['disk'],
        );

        $attachment = DocumentAttachmentModel::create([
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
}
