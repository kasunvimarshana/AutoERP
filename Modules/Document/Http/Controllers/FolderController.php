<?php

declare(strict_types=1);

namespace Modules\Document\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Responses\ApiResponse;
use Modules\Document\Http\Requests\StoreFolderRequest;
use Modules\Document\Http\Requests\UpdateFolderRequest;
use Modules\Document\Http\Resources\FolderResource;
use Modules\Document\Models\Folder;
use Modules\Document\Repositories\FolderRepository;
use Modules\Document\Services\FolderService;

class FolderController extends Controller
{
    public function __construct(
        private FolderRepository $folderRepository,
        private FolderService $folderService,
    ) {}

    /**
     * Display a listing of folders
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->boolean('tree')) {
            $folders = $this->folderService->getTree();

            return ApiResponse::success(
                FolderResource::collection($folders),
                'Folder tree retrieved successfully'
            );
        }

        $folders = $this->folderRepository->getRootFolders();

        return ApiResponse::success(
            FolderResource::collection($folders),
            'Folders retrieved successfully'
        );
    }

    /**
     * Create a new folder
     */
    public function store(StoreFolderRequest $request): JsonResponse
    {
        $folder = $this->folderService->createFolder(
            $request->name,
            $request->parent_folder_id,
            $request->description
        );

        return ApiResponse::success(
            new FolderResource($folder),
            'Folder created successfully',
            201
        );
    }

    /**
     * Display the specified folder
     */
    public function show(Folder $folder): JsonResponse
    {
        $this->authorize('view', $folder);

        return ApiResponse::success(
            new FolderResource($folder->load(['parent', 'children'])),
            'Folder retrieved successfully'
        );
    }

    /**
     * Update the specified folder
     */
    public function update(UpdateFolderRequest $request, Folder $folder): JsonResponse
    {
        $this->authorize('update', $folder);

        $folder = $this->folderService->updateFolder($folder->id, $request->validated());

        return ApiResponse::success(
            new FolderResource($folder),
            'Folder updated successfully'
        );
    }

    /**
     * Remove the specified folder
     */
    public function destroy(Folder $folder): JsonResponse
    {
        $this->authorize('delete', $folder);

        $this->folderService->delete($folder->id, false);

        return ApiResponse::success(null, 'Folder deleted successfully');
    }

    /**
     * Get folder breadcrumbs
     */
    public function breadcrumbs(Folder $folder): JsonResponse
    {
        $this->authorize('view', $folder);

        $breadcrumbs = $this->folderService->getBreadcrumbs($folder->id);

        return ApiResponse::success($breadcrumbs, 'Breadcrumbs retrieved successfully');
    }

    /**
     * Get folder children
     */
    public function children(Folder $folder): JsonResponse
    {
        $this->authorize('view', $folder);

        $children = $this->folderRepository->getChildren($folder->id);

        return ApiResponse::success(
            FolderResource::collection($children),
            'Children retrieved successfully'
        );
    }

    /**
     * Move folder
     */
    public function move(Request $request, Folder $folder): JsonResponse
    {
        $this->authorize('update', $folder);

        $request->validate([
            'parent_folder_id' => ['nullable', 'string', 'exists:folders,id'],
        ]);

        $folder = $this->folderService->move($folder->id, $request->parent_folder_id);

        return ApiResponse::success(
            new FolderResource($folder),
            'Folder moved successfully'
        );
    }
}
