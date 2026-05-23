<?php

declare(strict_types=1);

namespace Modules\Item\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Item\Application\DTOs\ItemRecordData;
use Modules\Item\Application\Services\ItemService;
use Modules\Item\Domain\Exceptions\ItemIntegrityException;
use Modules\Item\Domain\Exceptions\ItemRecordNotFoundException;
use Modules\Item\Presentation\Http\Requests\ItemRecordRequest;
use Modules\Item\Presentation\Http\Resources\ItemRecordResource;

class ItemResourceController extends Controller
{
    public function __construct(private readonly ItemService $items) {}

    public function index(Request $request, int|string $tenant, string $resource): mixed
    {
        try {
            return ItemRecordResource::collection(
                $this->items->list($resource, $tenant, $this->filters($request), $this->perPage($request))
            );
        } catch (ItemRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function store(ItemRecordRequest $request, int|string $tenant, string $resource): JsonResponse
    {
        try {
            $record = $this->items->create($resource, ItemRecordData::fromArray($tenant, $request->validated()));

            return (new ItemRecordResource($record))->response()->setStatusCode(201);
        } catch (ItemIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (ItemRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function show(int|string $tenant, string $resource, int|string $id): ItemRecordResource|JsonResponse
    {
        try {
            return new ItemRecordResource($this->items->find($resource, $tenant, $id));
        } catch (ItemRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(ItemRecordRequest $request, int|string $tenant, string $resource, int|string $id): ItemRecordResource|JsonResponse
    {
        try {
            return new ItemRecordResource($this->items->update($resource, $tenant, $id, ItemRecordData::fromArray($tenant, $request->validated())));
        } catch (ItemIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (ItemRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $tenant, string $resource, int|string $id): JsonResponse
    {
        try {
            $this->items->delete($resource, $tenant, $id);

            return response()->json(null, 204);
        } catch (ItemIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (ItemRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return collect($request->except(['page', 'per_page']))
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '')
            ->all();
    }

    private function perPage(Request $request): ?int
    {
        return $request->filled('per_page') ? max(1, min((int) $request->integer('per_page'), 100)) : null;
    }

    private function notFound(ItemRecordNotFoundException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 404);
    }

    private function unprocessable(ItemIntegrityException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 422);
    }
}
