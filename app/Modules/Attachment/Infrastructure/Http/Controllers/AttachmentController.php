<?php

namespace Modules\Attachment\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Attachment\Application\Contracts\DeleteAttachmentServiceInterface;
use Modules\Attachment\Application\Contracts\FindAttachmentServiceInterface;
use Modules\Attachment\Application\Contracts\GetAttachmentsServiceInterface;
use Modules\Attachment\Application\Contracts\UploadAttachmentServiceInterface;
use Modules\Attachment\Application\DTOs\UploadAttachmentData;
use Modules\Attachment\Domain\ValueObjects\AttachableType;
use Modules\Attachment\Infrastructure\Http\Resources\AttachmentResource;

class AttachmentController extends Controller
{
    public function __construct(
        private readonly UploadAttachmentServiceInterface $uploader,
        private readonly DeleteAttachmentServiceInterface $deleter,
        private readonly GetAttachmentsServiceInterface $getter,
        private readonly FindAttachmentServiceInterface $finder,
    ) {}

    /** @return list<string> */
    private static function allowedAttachableTypes(): array
    {
        return [
            AttachableType::PURCHASE_ORDER,
            AttachableType::GOODS_RECEIPT,
            AttachableType::SALES_ORDER,
            AttachableType::STOCK_RETURN,
            AttachableType::PRODUCT,
            AttachableType::SUPPLIER,
            AttachableType::CUSTOMER,
            AttachableType::DISPATCH,
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'attachable_type' => 'required|string',
            'attachable_id'   => 'required|integer',
        ]);

        $attachments = $this->getter->execute(
            $request->attachable_type,
            (int) $request->attachable_id,
        );

        return response()->json([
            'data' => array_map(
                fn($a) => (new AttachmentResource($a))->toArray($request),
                $attachments,
            ),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id'       => 'required|integer',
            'attachable_type' => ['required', 'string', 'in:' . implode(',', self::allowedAttachableTypes())],
            'attachable_id'   => 'required|integer',
            'files'           => 'required|array|min:1',
            'files.*'         => 'required|file|max:20480',
            'label'           => 'nullable|string|max:255',
        ]);

        $disk    = config('filesystems.default', 'local');
        $results = [];

        foreach ($request->file('files') as $file) {
            $path = $file->store(
                'attachments/' . $validated['attachable_type'] . '/' . $validated['attachable_id'],
                $disk,
            );

            $dto = new UploadAttachmentData(
                tenantId:       (int) $validated['tenant_id'],
                attachableType: $validated['attachable_type'],
                attachableId:   (int) $validated['attachable_id'],
                disk:           $disk,
                path:           $path,
                originalName:   $file->getClientOriginalName(),
                mimeType:       $file->getMimeType() ?? 'application/octet-stream',
                size:           $file->getSize(),
                label:          $validated['label'] ?? null,
                uploadedBy:     $request->user()?->id,
            );

            $results[] = (new AttachmentResource($this->uploader->execute($dto)))->toArray($request);
        }

        return response()->json(['data' => $results], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $attachment = $this->finder->execute($id);

        if ($attachment === null) {
            return response()->json(['message' => 'Attachment not found'], 404);
        }

        $this->deleter->execute($attachment);

        return response()->json(['message' => 'Attachment deleted'], 200);
    }
}
