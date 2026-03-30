<?php

declare(strict_types=1);

namespace Modules\Category\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Category\Application\Contracts\DeleteCategoryImageServiceInterface;
use Modules\Category\Application\Contracts\FindCategoryImagesServiceInterface;
use Modules\Category\Application\Contracts\UploadCategoryImageServiceInterface;
use Modules\Category\Domain\Entities\Category;
use Modules\Category\Infrastructure\Http\Requests\UploadCategoryImageRequest;
use Modules\Category\Infrastructure\Http\Resources\CategoryImageResource;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use OpenApi\Attributes as OA;

class CategoryImageController extends AuthorizedController
{
    public function __construct(
        protected UploadCategoryImageServiceInterface $uploadService,
        protected DeleteCategoryImageServiceInterface $deleteService,
        protected FindCategoryImagesServiceInterface $findService,
        protected FileStorageServiceInterface $storage
    ) {}

    #[OA\Post(
        path: '/api/categories/{categoryId}/image',
        summary: 'Upload category image',
        tags: ['Category Images'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'categoryId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
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
            new OA\Response(response: 200, description: 'Image uploaded',
                content: new OA\JsonContent(ref: '#/components/schemas/CategoryImageObject')),
            new OA\Response(response: 401, description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function store(UploadCategoryImageRequest $request, int $categoryId): CategoryImageResource
    {
        $this->authorize('create', Category::class);
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

        $image = $this->uploadService->execute([
            'category_id' => $categoryId,
            'file'        => $fileInfo,
            'metadata'    => $metadata,
        ]);

        return new CategoryImageResource($image);
    }

    #[OA\Delete(
        path: '/api/categories/{categoryId}/image/{imageId}',
        summary: 'Delete category image',
        tags: ['Category Images'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'categoryId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'imageId',    in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
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
    public function destroy(int $categoryId, int $imageId): JsonResponse
    {
        $this->authorize('delete', Category::class);
        $this->deleteService->execute(['image_id' => $imageId]);

        return response()->json(['message' => 'Category image deleted successfully']);
    }

    #[OA\Get(
        path: '/api/storage/category-images/{uuid}',
        summary: 'Serve category image',
        tags: ['Category Images'],
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
        $image = $this->findService->findByUuid($uuid);
        if (! $image) {
            abort(404);
        }

        return $this->storage->stream($image->getFilePath());
    }
}
