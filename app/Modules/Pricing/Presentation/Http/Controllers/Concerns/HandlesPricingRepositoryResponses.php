<?php

declare(strict_types=1);

namespace Modules\Pricing\Presentation\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;
use Modules\Pricing\Presentation\Http\Resources\PricingRecordResource;
use Throwable;

trait HandlesPricingRepositoryResponses
{
    private function listRecords(RepositoryPortInterface $repository, array $criteria): JsonResponse
    {
        $perPage = (int) ($criteria['per_page'] ?? 25);
        $page = (int) ($criteria['page'] ?? 1);
        unset($criteria['per_page'], $criteria['page']);

        try {
            $pageResult = $repository->page($criteria, $perPage, $page);
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        if (! $pageResult instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected list response.'], 500);
        }

        return response()->json([
            'data' => PricingRecordResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    private function showRecord(RepositoryPortInterface $repository, int|string $id): JsonResponse|PricingRecordResource
    {
        $record = $repository->findById($id);

        if ($record === null) {
            return response()->json(['message' => 'Pricing record not found.'], 404);
        }

        return new PricingRecordResource($record);
    }

    private function createRecord(RepositoryPortInterface $repository, array $payload): JsonResponse
    {
        try {
            $payload = $this->withDefaultRowVersion($payload);
            $record = $repository->create($payload);
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return (new PricingRecordResource($record))->response()->setStatusCode(201);
    }

    private function updateRecord(
        RepositoryPortInterface $repository,
        int|string $id,
        array $payload,
    ): JsonResponse|PricingRecordResource {
        try {
            $record = $repository->update($id, $payload);
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return new PricingRecordResource($record);
    }

    private function deleteRecord(RepositoryPortInterface $repository, int|string $id): JsonResponse
    {
        try {
            $repository->delete($id);
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        return response()->json(null, 204);
    }

    /** @param array<string, mixed> $payload */
    private function withDefaultRowVersion(array $payload): array
    {
        if (! array_key_exists('row_version', $payload)) {
            $payload['row_version'] = 1;
        }

        return $payload;
    }
}
