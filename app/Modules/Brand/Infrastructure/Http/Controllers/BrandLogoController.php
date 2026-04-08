<?php

declare(strict_types=1);

namespace Modules\Brand\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Brand\Application\Contracts\DeleteBrandLogoServiceInterface;
use Modules\Brand\Application\Contracts\FindBrandLogosServiceInterface;
use Modules\Brand\Application\Contracts\UploadBrandLogoServiceInterface;
use Modules\Brand\Domain\Entities\Brand;
use Modules\Brand\Infrastructure\Http\Requests\UploadBrandLogoRequest;
use Modules\Brand\Infrastructure\Http\Resources\BrandLogoResource;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use OpenApi\Attributes as OA;

class BrandLogoController extends AuthorizedController
{
    public function __construct(
        protected UploadBrandLogoServiceInterface $uploadService,
        protected DeleteBrandLogoServiceInterface $deleteService,
        protected FindBrandLogosServiceInterface $findService,
        protected FileStorageServiceInterface $storage
    ) {}

    #[OA\Post(
        path: '/api/brands/{brandId}/logo',
        summary: 'Upload brand logo',
        tags: ['Brand Logo'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'brandId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file'],
                    properties: [
                        new OA\Property(property: 'file',     type: 'string', format: 'binary'),
                        new OA\Property(property: 'metadata', type: 'string', nullable: true, example: '{}'),
                    ],
                ),
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Logo uploaded',
                content: new OA\JsonContent(ref: '#/components/schemas/BrandLogoObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function store(UploadBrandLogoRequest $request, int $brandId): BrandLogoResource
    {
        $this->authorize('create', Brand::class);
        $file = $request->file('file');
        $fileInfo = [
            'tmp_path'  => $file->getPathname(),
            'name'      => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size'      => $file->getSize(),
        ];

        $metadataRaw = $request->input('metadata');
        $metadata = null;
        if ($metadataRaw) {
            $decoded = json_decode($metadataRaw, true);
            $metadata = is_array($decoded) ? $decoded : null;
        }

        $logo = $this->uploadService->execute([
            'brand_id' => $brandId,
            'file'     => $fileInfo,
            'metadata' => $metadata,
        ]);

        return new BrandLogoResource($logo);
    }

    #[OA\Delete(
        path: '/api/brands/{brandId}/logo/{logoId}',
        summary: 'Delete brand logo',
        tags: ['Brand Logo'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'brandId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'logoId',  in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Deleted',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function destroy(int $brandId, int $logoId): JsonResponse
    {
        $this->authorize('delete', Brand::class);
        $this->deleteService->execute(['logo_id' => $logoId]);

        return response()->json(['message' => 'Brand logo deleted successfully']);
    }

    #[OA\Get(
        path: '/api/storage/brand-logos/{uuid}',
        summary: 'Serve brand logo',
        tags: ['Brand Logo'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'File stream'),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function serve(string $uuid)
    {
        $logo = $this->findService->findByUuid($uuid);
        if (! $logo) {
            abort(404);
        }

        return $this->storage->stream($logo->getFilePath());
    }
}
