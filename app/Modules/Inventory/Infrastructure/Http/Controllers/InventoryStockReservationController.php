<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Inventory\Application\Contracts\CreateStockReservationServiceInterface;
use Modules\Inventory\Application\Contracts\FindStockReservationServiceInterface;
use Modules\Inventory\Application\Contracts\ReleaseExpiredStockReservationsServiceInterface;
use Modules\Inventory\Application\Contracts\ReleaseStockReservationServiceInterface;
use Modules\Inventory\Domain\Exceptions\InsufficientAvailableStockException;
use Modules\Inventory\Infrastructure\Http\Requests\DeleteStockReservationRequest;
use Modules\Inventory\Infrastructure\Http\Requests\ListStockReservationRequest;
use Modules\Inventory\Infrastructure\Http\Requests\ReleaseExpiredStockReservationRequest;
use Modules\Inventory\Infrastructure\Http\Requests\StoreStockReservationRequest;
use Modules\Inventory\Infrastructure\Http\Resources\StockReservationResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class InventoryStockReservationController
{
    public function __construct(
        private readonly CreateStockReservationServiceInterface $createStockReservationService,
        private readonly FindStockReservationServiceInterface $findStockReservationService,
        private readonly ReleaseStockReservationServiceInterface $releaseStockReservationService,
        private readonly ReleaseExpiredStockReservationsServiceInterface $releaseExpiredStockReservationsService,
    ) {}

    public function index(ListStockReservationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $reservations = $this->findStockReservationService->list(
            tenantId: (int) $validated['tenant_id'],
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
        );

        return response()->json($reservations);
    }

    public function show(ListStockReservationRequest $request, int $reservation): JsonResponse
    {
        $validated = $request->validated();
        $item = $this->findStockReservationService->find((int) $validated['tenant_id'], $reservation);

        if ($item === null) {
            return response()->json(['message' => 'Stock reservation not found.'], HttpResponse::HTTP_NOT_FOUND);
        }

        return (new StockReservationResource($item))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_OK);
    }

    public function store(StoreStockReservationRequest $request): JsonResponse
    {
        try {
            $reservation = $this->createStockReservationService->execute($request->validated());
        } catch (InsufficientAvailableStockException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return (new StockReservationResource($reservation))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function destroy(DeleteStockReservationRequest $request, int $reservation): JsonResponse
    {
        $validated = $request->validated();
        $deleted = $this->releaseStockReservationService->execute((int) $validated['tenant_id'], $reservation);

        if (! $deleted) {
            return response()->json(['message' => 'Stock reservation not found.'], HttpResponse::HTTP_NOT_FOUND);
        }

        return response()->json([], HttpResponse::HTTP_NO_CONTENT);
    }

    public function releaseExpired(ReleaseExpiredStockReservationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $releasedCount = $this->releaseExpiredStockReservationsService->execute(
            tenantId: (int) $validated['tenant_id'],
            expiresBefore: $validated['expires_before'] ?? null,
        );

        return response()->json([
            'released_count' => $releasedCount,
        ], HttpResponse::HTTP_OK);
    }
}
