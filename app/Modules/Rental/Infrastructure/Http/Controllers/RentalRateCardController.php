<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Rental\Application\Contracts\CreateRentalRateCardServiceInterface;
use Modules\Rental\Application\Contracts\FindRentalRateCardServiceInterface;
use Modules\Rental\Domain\Entities\RentalRateCard;
use Modules\Rental\Infrastructure\Http\Requests\StoreRentalRateCardRequest;
use Modules\Rental\Infrastructure\Http\Resources\RentalRateCardResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class RentalRateCardController extends AuthorizedController
{
    public function __construct(
        private readonly CreateRentalRateCardServiceInterface $createRateCard,
        private readonly FindRentalRateCardServiceInterface $findRateCard,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', RentalRateCard::class);

        $tenantId = (int) request()->header('X-Tenant-ID');
        $result = $this->findRateCard->paginate(
            tenantId: $tenantId,
            filters: [],
            perPage: (int) (request()->query('per_page', 15)),
            page: (int) (request()->query('page', 1)),
        );

        return response()->json($result);
    }

    public function store(StoreRentalRateCardRequest $request): JsonResponse
    {
        $this->authorize('create', RentalRateCard::class);

        $rateCard = $this->createRateCard->execute($request->validated());

        return (new RentalRateCardResource($rateCard))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(int $rateCard): JsonResponse
    {
        $this->authorize('view', RentalRateCard::class);

        $tenantId = (int) request()->header('X-Tenant-ID');
        $found = $this->findRateCard->findById($tenantId, $rateCard);

        return (new RentalRateCardResource($found))->response();
    }

    public function update(StoreRentalRateCardRequest $request, int $rateCard): JsonResponse
    {
        $this->authorize('update', RentalRateCard::class);

        return response()->json(['message' => 'Not implemented'], HttpResponse::HTTP_NOT_IMPLEMENTED);
    }

    public function destroy(int $rateCard): JsonResponse
    {
        $this->authorize('delete', RentalRateCard::class);

        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }
}
