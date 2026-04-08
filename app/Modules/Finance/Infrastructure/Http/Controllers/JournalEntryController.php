<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Finance\Application\Contracts\JournalEntryServiceInterface;
use Modules\Finance\Application\DTOs\JournalEntryData;
use Modules\Finance\Infrastructure\Http\Requests\StoreJournalEntryRequest;
use Modules\Finance\Infrastructure\Http\Resources\JournalEntryResource;

/**
 * @OA\Tag(name="Finance - Journal Entries", description="Journal entry management")
 */
final class JournalEntryController extends AuthorizedController
{
    public function __construct(private readonly JournalEntryServiceInterface $service) {}

    /**
     * @OA\Get(
     *     path="/api/finance/journal-entries",
     *     tags={"Finance - Journal Entries"},
     *     summary="List journal entries for the authenticated tenant",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="X-Tenant-ID", in="header", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Paginated journal entry list")
     * )
     */
    public function index(Request $request): ResourceCollection
    {
        $filters = array_filter($request->only(['status', 'currency']));
        $perPage = (int) $request->query('per_page', 15);

        $paginated = $this->service->list($filters, $perPage);

        return JournalEntryResource::collection($paginated);
    }

    /**
     * @OA\Post(
     *     path="/api/finance/journal-entries",
     *     tags={"Finance - Journal Entries"},
     *     summary="Create a new draft journal entry",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="X-Tenant-ID", in="header", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/StoreJournalEntryRequest")),
     *     @OA\Response(response=201, description="Journal entry created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreJournalEntryRequest $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $dto      = JournalEntryData::fromArray($request->validated());
        $entry    = $this->service->create($dto, $tenantId);

        return (new JournalEntryResource($entry))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @OA\Get(
     *     path="/api/finance/journal-entries/{id}",
     *     tags={"Finance - Journal Entries"},
     *     summary="Get journal entry by ID (with lines)",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Journal entry details"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $entry = $this->service->find($id);

        return (new JournalEntryResource($entry))->response();
    }

    /**
     * @OA\Put(
     *     path="/api/finance/journal-entries/{id}",
     *     tags={"Finance - Journal Entries"},
     *     summary="Update a draft journal entry",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/StoreJournalEntryRequest")),
     *     @OA\Response(response=200, description="Journal entry updated"),
     *     @OA\Response(response=422, description="Cannot update a posted entry")
     * )
     */
    public function update(StoreJournalEntryRequest $request, int $id): JsonResponse
    {
        $dto   = JournalEntryData::fromArray($request->validated());
        $entry = $this->service->update($id, $dto);

        return (new JournalEntryResource($entry))->response();
    }

    /**
     * @OA\Delete(
     *     path="/api/finance/journal-entries/{id}",
     *     tags={"Finance - Journal Entries"},
     *     summary="Delete a draft journal entry (soft delete)",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Deleted"),
     *     @OA\Response(response=422, description="Cannot delete a posted entry")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(null, 204);
    }

    /**
     * @OA\Post(
     *     path="/api/finance/journal-entries/{id}/post",
     *     tags={"Finance - Journal Entries"},
     *     summary="Post a draft journal entry (validates balance, updates account balances)",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Journal entry posted"),
     *     @OA\Response(response=422, description="Unbalanced or already posted")
     * )
     */
    public function post(int $id): JsonResponse
    {
        $entry = $this->service->post($id);

        return (new JournalEntryResource($entry))->response();
    }

    /**
     * @OA\Post(
     *     path="/api/finance/journal-entries/{id}/void",
     *     tags={"Finance - Journal Entries"},
     *     summary="Void a journal entry",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *       required={"reason"},
     *       @OA\Property(property="reason", type="string")
     *     )),
     *     @OA\Response(response=200, description="Journal entry voided"),
     *     @OA\Response(response=422, description="Already voided")
     * )
     */
    public function void(Request $request, int $id): JsonResponse
    {
        $reason = (string) $request->input('reason', '');
        $entry  = $this->service->void($id, $reason);

        return (new JournalEntryResource($entry))->response();
    }
}
