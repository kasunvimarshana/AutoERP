<?php

declare(strict_types=1);

namespace Modules\Metadata\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Interfaces\Http\Resources\ApiResponse;
use Modules\Metadata\Application\DTOs\CreateCustomFieldDTO;
use Modules\Metadata\Application\Services\MetadataService;

/**
 * Metadata controller.
 *
 * Input validation, authorization checks, and response formatting ONLY.
 * No business logic — all delegated to MetadataService.
 *
 * @OA\Tag(name="Metadata", description="Custom field definition management endpoints")
 */
class MetadataController extends Controller
{
    public function __construct(private readonly MetadataService $service) {}

    /**
     * @OA\Get(
     *     path="/api/v1/metadata/fields",
     *     tags={"Metadata"},
     *     summary="List all custom field definitions (paginated)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Paginated list of custom field definitions"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $paginator = $this->service->paginateFields($perPage);

        return ApiResponse::paginated($paginator, 'Custom field definitions retrieved.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/metadata/fields",
     *     tags={"Metadata"},
     *     summary="Create a new custom field definition",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"entity_type","field_name","field_label","field_type"},
     *             @OA\Property(property="entity_type", type="string", example="product"),
     *             @OA\Property(property="field_name", type="string", example="shelf_life_days"),
     *             @OA\Property(property="field_label", type="string", example="Shelf Life (days)"),
     *             @OA\Property(property="field_type", type="string", enum={"text","number","date","boolean","select","multiselect","textarea"}),
     *             @OA\Property(property="options", type="array", nullable=true, @OA\Items(type="string")),
     *             @OA\Property(property="is_required", type="boolean", default=false),
     *             @OA\Property(property="is_active", type="boolean", default=true),
     *             @OA\Property(property="sort_order", type="integer", default=0),
     *             @OA\Property(property="validation_rules", type="object", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Custom field definition created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entity_type'      => ['required', 'string', 'max:100'],
            'field_name'       => ['required', 'string', 'max:100'],
            'field_label'      => ['required', 'string', 'max:255'],
            'field_type'       => ['required', 'string', 'in:text,number,date,boolean,select,multiselect,textarea'],
            'options'          => ['nullable', 'array'],
            'is_required'      => ['nullable', 'boolean'],
            'is_active'        => ['nullable', 'boolean'],
            'sort_order'       => ['nullable', 'integer', 'min:0'],
            'validation_rules' => ['nullable', 'array'],
        ]);

        $dto = CreateCustomFieldDTO::fromArray($validated);
        $field = $this->service->createField($dto);

        return ApiResponse::created($field, 'Custom field definition created.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/metadata/fields/{id}",
     *     tags={"Metadata"},
     *     summary="Get a single custom field definition",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Custom field definition data"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $field = $this->service->showField($id);

        return ApiResponse::success($field, 'Custom field definition retrieved.');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/metadata/fields/{id}",
     *     tags={"Metadata"},
     *     summary="Update a custom field definition",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="field_label", type="string"),
     *             @OA\Property(property="field_type", type="string", enum={"text","number","date","boolean","select","multiselect","textarea"}),
     *             @OA\Property(property="options", type="array", nullable=true, @OA\Items(type="string")),
     *             @OA\Property(property="is_required", type="boolean"),
     *             @OA\Property(property="is_active", type="boolean"),
     *             @OA\Property(property="sort_order", type="integer"),
     *             @OA\Property(property="validation_rules", type="object", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Updated"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'entity_type'      => ['sometimes', 'required', 'string', 'max:100'],
            'field_name'       => ['sometimes', 'required', 'string', 'max:100'],
            'field_label'      => ['sometimes', 'required', 'string', 'max:255'],
            'field_type'       => ['sometimes', 'required', 'string', 'in:text,number,date,boolean,select,multiselect,textarea'],
            'options'          => ['nullable', 'array'],
            'is_required'      => ['nullable', 'boolean'],
            'is_active'        => ['nullable', 'boolean'],
            'sort_order'       => ['nullable', 'integer', 'min:0'],
            'validation_rules' => ['nullable', 'array'],
        ]);

        $field = $this->service->updateField($id, $validated);

        return ApiResponse::success($field, 'Custom field definition updated.');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/metadata/fields/{id}",
     *     tags={"Metadata"},
     *     summary="Delete a custom field definition",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Deleted"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $this->service->deleteField($id);

        return ApiResponse::noContent();
    }
}
