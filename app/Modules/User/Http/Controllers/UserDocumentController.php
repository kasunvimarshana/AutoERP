<?php

declare(strict_types=1);

namespace Modules\User\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Core\DTOs\DataRecord;
use Modules\User\Http\Requests\Documents\DeleteUserDocumentRequest;
use Modules\User\Http\Requests\Documents\ListUserDocumentsRequest;
use Modules\User\Http\Requests\Documents\StoreUserDocumentRequest;
use Modules\User\Http\Requests\Documents\UpdateUserDocumentRequest;
use Modules\User\Http\Resources\UserRecordResource;
use Modules\User\Services\UserDocumentService;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class UserDocumentController extends AbstractUserCrudController
{
    public function __construct(private readonly UserDocumentService $documents) {}

    public function index(ListUserDocumentsRequest $request, int|string $user): JsonResponse
    {
        return $this->responseForList($this->documents->list($user, $request->validated()));
    }

    public function show(int|string $user, int|string $document): JsonResponse|UserRecordResource
    {
        return $this->responseForShow($this->documents->get($user, $document));
    }

    public function store(StoreUserDocumentRequest $request, int|string $user): JsonResponse|UserRecordResource
    {
        return $this->responseForStore($this->documents->create($user, $request->validated()));
    }

    public function update(
        UpdateUserDocumentRequest $request,
        int|string $user,
        int|string $document,
    ): JsonResponse|UserRecordResource {
        $payload = $request->validated();
        $expectedVersion = (int) $payload['expected_version'];
        unset($payload['expected_version']);

        return $this->responseForUpdate($this->documents->update($user, $document, $expectedVersion, $payload));
    }

    public function destroy(
        DeleteUserDocumentRequest $request,
        int|string $user,
        int|string $document,
    ): JsonResponse {
        return $this->responseForDelete($this->documents->delete(
            $user,
            $document,
            (int) $request->validated('expected_version'),
        ));
    }

    public function download(int|string $user, int|string $document): JsonResponse|StreamedResponse
    {
        $result = $this->documents->download($user, $document);
        if ($result->isFailure()) {
            return $this->errorResponse($result);
        }
        $payload = $result->valueOrFail();
        $record = $payload['record'] instanceof DataRecord ? $payload['record']->toArray() : [];
        $stream = $payload['stream'];
        $filename = $this->safeDownloadFilename((string) ($record['original_filename'] ?? 'document.bin'));
        $mime = (string) ($record['mime_type'] ?? 'application/octet-stream');

        return response()->streamDownload(static function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }, $filename, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function safeDownloadFilename(string $filename): string
    {
        $filename = basename(str_replace('\\', '/', trim($filename)));
        $filename = str_replace(["\0", "\r", "\n"], '', $filename);

        return $filename !== '' ? $filename : 'document.bin';
    }
}
