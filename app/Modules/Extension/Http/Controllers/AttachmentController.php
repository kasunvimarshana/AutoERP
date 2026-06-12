<?php

declare(strict_types=1);

namespace Modules\Extension\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Modules\Core\Contracts\FileStorageServiceInterface;
use Modules\Extension\Constants\ExtensionErrorCode;
use Modules\Extension\Enums\AttachmentPreviewStatus;
use Modules\Extension\Http\Requests\ListAttachmentRequest;
use Modules\Extension\Http\Requests\StoreAttachmentRequest;
use Modules\Extension\Http\Requests\StoreAttachmentVersionRequest;
use Modules\Extension\Http\Requests\UpdateAttachmentRequest;
use Modules\Extension\Http\Resources\AttachmentResource;
use Modules\Extension\Models\AttachmentModel;
use Modules\Extension\Services\Attachments\AttachmentService;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AttachmentController extends Controller
{
    public function __construct(
        private readonly AttachmentService $attachments,
        private readonly FileStorageServiceInterface $files,
    ) {}

    public function index(ListAttachmentRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 0);
        $page = (int) ($validated['page'] ?? 0);
        unset($validated['per_page'], $validated['page']);

        $result = $this->attachments->list($validated, $perPage, $page);
        if ($result->isFailure()) {
            return $this->error($result->errorOrFail()->code, $result->errorOrFail()->message);
        }

        $paginator = $result->valueOrFail();

        return response()->json([
            'data' => AttachmentResource::collection($paginator->items())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(int|string $attachment): JsonResponse|AttachmentResource
    {
        $result = $this->attachments->get($attachment);
        if ($result->isFailure()) {
            return $this->error($result->errorOrFail()->code, $result->errorOrFail()->message);
        }

        return new AttachmentResource($result->valueOrFail());
    }

    public function store(StoreAttachmentRequest $request): JsonResponse|AttachmentResource
    {
        $file = $request->file('file');
        if (! $file instanceof UploadedFile) {
            return response()->json(['message' => 'A file is required.'], 422);
        }

        $result = $this->attachments->create($request->safe()->except('file'), $file);
        if ($result->isFailure()) {
            return $this->error($result->errorOrFail()->code, $result->errorOrFail()->message);
        }

        return (new AttachmentResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(
        UpdateAttachmentRequest $request,
        int|string $attachment,
    ): JsonResponse|AttachmentResource {
        $result = $this->attachments->update($attachment, $request->validated());
        if ($result->isFailure()) {
            return $this->error($result->errorOrFail()->code, $result->errorOrFail()->message);
        }

        return new AttachmentResource($result->valueOrFail());
    }

    public function storeVersion(
        StoreAttachmentVersionRequest $request,
        int|string $attachment,
    ): JsonResponse|AttachmentResource {
        $file = $request->file('file');
        if (! $file instanceof UploadedFile) {
            return response()->json(['message' => 'A file is required.'], 422);
        }

        $result = $this->attachments->createVersion(
            $attachment,
            $request->safe()->except('file'),
            $file,
        );
        if ($result->isFailure()) {
            return $this->error($result->errorOrFail()->code, $result->errorOrFail()->message);
        }

        return (new AttachmentResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function versions(int|string $attachment): JsonResponse
    {
        $result = $this->attachments->versions($attachment);
        if ($result->isFailure()) {
            return $this->error($result->errorOrFail()->code, $result->errorOrFail()->message);
        }

        return response()->json([
            'data' => AttachmentResource::collection($result->valueOrFail())->resolve(),
        ]);
    }

    public function download(int|string $attachment): JsonResponse|StreamedResponse
    {
        $result = $this->attachments->get($attachment);
        if ($result->isFailure()) {
            return $this->error($result->errorOrFail()->code, $result->errorOrFail()->message);
        }

        return $this->stream($result->valueOrFail(), false);
    }

    public function preview(int|string $attachment): JsonResponse|StreamedResponse
    {
        $result = $this->attachments->get($attachment);
        if ($result->isFailure()) {
            return $this->error($result->errorOrFail()->code, $result->errorOrFail()->message);
        }

        $model = $result->valueOrFail();
        if (
            ! $model instanceof AttachmentModel
            || $model->preview_status !== AttachmentPreviewStatus::Ready
            || $model->preview_path === null
        ) {
            return response()->json(['message' => 'Preview is not available for this attachment.'], 404);
        }

        return $this->stream($model, true);
    }

    public function destroy(int|string $attachment): JsonResponse
    {
        $result = $this->attachments->delete($attachment);
        if ($result->isFailure()) {
            return $this->error($result->errorOrFail()->code, $result->errorOrFail()->message);
        }

        return response()->json(null, 204);
    }

    private function stream(AttachmentModel $attachment, bool $inline): JsonResponse|StreamedResponse
    {
        $path = $inline ? (string) $attachment->preview_path : (string) $attachment->file_path;
        $stream = $this->files->readStream($path, (string) $attachment->disk);
        if (! is_resource($stream)) {
            return response()->json(['message' => 'Attachment content is unavailable.'], 404);
        }

        $headers = [
            'Content-Type' => (string) ($attachment->mime_type ?: 'application/octet-stream'),
            'Content-Length' => (string) $attachment->size,
            'X-Content-Type-Options' => 'nosniff',
        ];

        if ($inline) {
            $headers['Content-Disposition'] = 'inline; filename="'.addslashes((string) $attachment->original_file_name).'"';

            return response()->stream(static function () use ($stream): void {
                try {
                    fpassthru($stream);
                } finally {
                    fclose($stream);
                }
            }, 200, $headers);
        }

        return response()->streamDownload(static function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, (string) $attachment->original_file_name, $headers);
    }

    private function error(string $code, string $message): JsonResponse
    {
        $status = match ($code) {
            ExtensionErrorCode::NOT_FOUND => 404,
            ExtensionErrorCode::FORBIDDEN => 403,
            ExtensionErrorCode::CONFLICT => 409,
            default => 422,
        };

        return response()->json(['message' => $message, 'code' => $code], $status);
    }
}
