<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\FileManagerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FileManagerController extends Controller
{
    public function __construct(
        private readonly FileManagerService $fileManagerService
    ) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('files.view'), 403);

        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);
        $filters = $request->only(['category_id', 'attachable_type', 'mime_type']);

        return response()->json($this->fileManagerService->paginate($tenantId, $filters, $perPage));
    }

    public function upload(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('files.upload'), 403);

        $request->validate([
            'file' => 'required|file|max:102400',
            'category_id' => 'nullable|uuid|exists:file_categories,id',
            'organization_id' => 'nullable|uuid|exists:organizations,id',
            'disk' => 'nullable|string|max:255',
            'is_public' => 'nullable|boolean',
            'attachable_type' => 'nullable|string|max:255',
            'attachable_id' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ]);

        $data = $request->except('file');
        $data['user_id'] = $request->user()->id;

        $file = $this->fileManagerService->upload(
            $request->user()->tenant_id,
            $request->file('file'),
            $data
        );

        return response()->json($file, 201);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('files.delete'), 403);

        $this->fileManagerService->delete($id);

        return response()->json(null, 204);
    }
}
