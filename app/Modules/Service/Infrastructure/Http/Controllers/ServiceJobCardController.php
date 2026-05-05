<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Service\Application\Contracts\CompleteServiceJobCardServiceInterface;
use Modules\Service\Application\Contracts\CreateServiceJobCardServiceInterface;
use Modules\Service\Application\Contracts\FindServiceJobCardServiceInterface;
use Modules\Service\Application\Contracts\UpdateServiceJobCardStatusServiceInterface;
use Modules\Service\Domain\Entities\ServiceJobCard;
use Modules\Service\Infrastructure\Http\Requests\StoreServiceJobCardRequest;
use Modules\Service\Infrastructure\Http\Resources\ServiceJobCardResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ServiceJobCardController extends AuthorizedController
{
    public function __construct(
        private readonly CreateServiceJobCardServiceInterface $createJobCard,
        private readonly FindServiceJobCardServiceInterface $findJobCard,
        private readonly UpdateServiceJobCardStatusServiceInterface $updateStatus,
        private readonly CompleteServiceJobCardServiceInterface $completeJobCard,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', ServiceJobCard::class);

        $tenantId = (int) request()->header('X-Tenant-ID');
        $result = $this->findJobCard->paginate(
            tenantId: $tenantId,
            filters: array_filter([
                'status' => request()->query('status'),
                'asset_id' => request()->query('asset_id'),
            ], static fn (mixed $v): bool => $v !== null),
            perPage: (int) (request()->query('per_page', 15)),
            page: (int) (request()->query('page', 1)),
        );

        return response()->json($result);
    }

    public function store(StoreServiceJobCardRequest $request): JsonResponse
    {
        $this->authorize('create', ServiceJobCard::class);

        $jobCard = $this->createJobCard->execute($request->validated());

        return (new ServiceJobCardResource($jobCard))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(int $jobCard): JsonResponse
    {
        $this->authorize('view', ServiceJobCard::class);

        $tenantId = (int) request()->header('X-Tenant-ID');
        $found = $this->findJobCard->findById($tenantId, $jobCard);

        return (new ServiceJobCardResource($found))->response();
    }

    public function updateStatus(int $jobCard): JsonResponse
    {
        $this->authorize('update', ServiceJobCard::class);

        $tenantId = (int) request()->header('X-Tenant-ID');
        $status = (string) request()->input('status', '');
        $updated = $this->updateStatus->execute($tenantId, $jobCard, $status);

        return (new ServiceJobCardResource($updated))->response();
    }

    public function complete(int $jobCard): JsonResponse
    {
        $this->authorize('update', ServiceJobCard::class);

        $tenantId = (int) request()->header('X-Tenant-ID');
        $completed = $this->completeJobCard->execute($tenantId, $jobCard);

        return (new ServiceJobCardResource($completed))->response();
    }

    public function destroy(int $jobCard): JsonResponse
    {
        $this->authorize('delete', ServiceJobCard::class);

        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }
}
