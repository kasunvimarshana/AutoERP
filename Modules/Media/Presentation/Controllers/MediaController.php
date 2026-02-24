<?php

namespace Modules\Media\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Application\UseCases\DeleteMediaUseCase;
use Modules\Media\Application\UseCases\UploadFileUseCase;
use Modules\Media\Domain\Contracts\MediaRepositoryInterface;
use Modules\Media\Presentation\Requests\UploadFileRequest;
use Modules\Shared\Application\ResponseFormatter;

class MediaController extends Controller
{
    public function __construct(
        private UploadFileUseCase $uploadUseCase,
        private DeleteMediaUseCase $deleteUseCase,
        private MediaRepositoryInterface $repository,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = app('current.tenant.id');
        $filters  = $request->only(['folder', 'model_type', 'model_id']);

        return ResponseFormatter::paginated($this->repository->paginate($tenantId, $filters));
    }

    public function upload(UploadFileRequest $request): JsonResponse
    {
        $media = $this->uploadUseCase->execute(array_merge($request->validated(), [
            'tenant_id'   => app('current.tenant.id'),
            'uploaded_by' => auth()->id(),
        ]));

        return ResponseFormatter::success($media, 'File uploaded.', 201);
    }

    public function show(string $id): JsonResponse
    {
        $media = $this->repository->findById($id);

        if (! $media) {
            return ResponseFormatter::error('Not found.', [], 404);
        }

        return ResponseFormatter::success($media);
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $this->deleteUseCase->execute($id);
        } catch (\DomainException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 404);
        }

        return ResponseFormatter::success(null, 'Deleted.');
    }

    public function temporaryUrl(string $id): JsonResponse
    {
        $media = $this->repository->findById($id);

        if (! $media) {
            return ResponseFormatter::error('Not found.', [], 404);
        }

        $url = Storage::disk($media->disk)->temporaryUrl($media->path, now()->addMinutes(30));

        return ResponseFormatter::success(['url' => $url, 'expires_in' => 1800]);
    }
}
