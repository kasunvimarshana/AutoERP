<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Returns\Application\Contracts\ReturnServiceInterface;
use Modules\Returns\Application\DTOs\ReturnData;
use Modules\Returns\Infrastructure\Http\Requests\StoreReturnRequest;
use Modules\Returns\Infrastructure\Http\Requests\UpdateReturnRequest;
use Modules\Returns\Infrastructure\Http\Resources\ReturnResource;

/**
 * @OA\Tag(name="Returns", description="Purchase & Sales Returns management")
 */
final class ReturnController extends AuthorizedController
{
    public function __construct(private readonly ReturnServiceInterface $service) {}

    /**
     * @OA\Get(
     *     path="/api/return/returns",
     *     tags={"Returns"},
     *     summary="List returns",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="X-Tenant-ID", in="header", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="type", in="query", @OA\Schema(type="string", enum={"purchase_return","sale_return"})),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"draft","submitted","approved","processing","completed","rejected","cancelled"})),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Paginated list of returns")
     * )
     */
    public function index(Request $request): ResourceCollection
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $perPage  = (int) $request->query('per_page', 15);
        $filters  = array_filter(
            $request->only(['type', 'status', 'reason', 'supplier_id', 'customer_id']),
            static fn ($v) => $v !== null && $v !== ''
        );
        $filters['tenant_id'] = $tenantId;

        return ReturnResource::collection(
            $this->service->list($filters, $perPage)
        );
    }

    /**
     * @OA\Post(
     *     path="/api/return/returns",
     *     tags={"Returns"},
     *     summary="Create a return",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="X-Tenant-ID", in="header", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/StoreReturnRequest")),
     *     @OA\Response(response=201, description="Return created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreReturnRequest $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $dto      = ReturnData::fromArray($request->validated());
        $return   = $this->service->create($dto, $tenantId);

        return (new ReturnResource($return))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @OA\Get(
     *     path="/api/return/returns/{id}",
     *     tags={"Returns"},
     *     summary="Get a return by ID",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Return details"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        return (new ReturnResource($this->service->find($id)))->response();
    }

    /**
     * @OA\Put(
     *     path="/api/return/returns/{id}",
     *     tags={"Returns"},
     *     summary="Update a return",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UpdateReturnRequest")),
     *     @OA\Response(response=200, description="Return updated"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(UpdateReturnRequest $request, int $id): JsonResponse
    {
        $dto    = ReturnData::fromArray($request->validated());
        $return = $this->service->update($id, $dto);

        return (new ReturnResource($return))->response();
    }

    /**
     * @OA\Delete(
     *     path="/api/return/returns/{id}",
     *     tags={"Returns"},
     *     summary="Delete a return",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Deleted"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(null, 204);
    }

    /**
     * @OA\Post(
     *     path="/api/return/returns/{id}/approve",
     *     tags={"Returns"},
     *     summary="Approve a return",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="X-Tenant-ID", in="header", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Return approved"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $userId   = (int) $request->user()?->id;
        $return   = $this->service->approve($id, $userId, $tenantId);

        return (new ReturnResource($return))->response();
    }

    /**
     * @OA\Post(
     *     path="/api/return/returns/{id}/reject",
     *     tags={"Returns"},
     *     summary="Reject a return",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="X-Tenant-ID", in="header", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"reason"},
     *         @OA\Property(property="reason", type="string", example="Items are in good condition")
     *     )),
     *     @OA\Response(response=200, description="Return rejected"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate(['reason' => ['required', 'string']]);

        $tenantId = (int) $request->header('X-Tenant-ID');
        $return   = $this->service->reject($id, $request->input('reason'), $tenantId);

        return (new ReturnResource($return))->response();
    }

    /**
     * @OA\Post(
     *     path="/api/return/returns/{id}/process",
     *     tags={"Returns"},
     *     summary="Process a return",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="X-Tenant-ID", in="header", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Return processed"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function process(Request $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $userId   = (int) $request->user()?->id;
        $return   = $this->service->process($id, $userId, $tenantId);

        return (new ReturnResource($return))->response();
    }
}
