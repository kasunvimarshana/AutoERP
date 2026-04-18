<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Finance\Application\Contracts\CreateFiscalPeriodServiceInterface;
use Modules\Finance\Application\Contracts\DeleteFiscalPeriodServiceInterface;
use Modules\Finance\Application\Contracts\FindFiscalPeriodServiceInterface;
use Modules\Finance\Application\Contracts\UpdateFiscalPeriodServiceInterface;
use Modules\Finance\Domain\Entities\FiscalPeriod;
use Modules\Finance\Infrastructure\Http\Requests\ListFiscalPeriodRequest;
use Modules\Finance\Infrastructure\Http\Requests\StoreFiscalPeriodRequest;
use Modules\Finance\Infrastructure\Http\Requests\UpdateFiscalPeriodRequest;
use Modules\Finance\Infrastructure\Http\Resources\FiscalPeriodCollection;
use Modules\Finance\Infrastructure\Http\Resources\FiscalPeriodResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FiscalPeriodController extends AuthorizedController
{
    public function __construct(
        private readonly CreateFiscalPeriodServiceInterface $createFiscalPeriodService,
        private readonly UpdateFiscalPeriodServiceInterface $updateFiscalPeriodService,
        private readonly DeleteFiscalPeriodServiceInterface $deleteFiscalPeriodService,
        private readonly FindFiscalPeriodServiceInterface $findFiscalPeriodService,
    ) {}

    public function index(ListFiscalPeriodRequest $request): JsonResponse
    {
        $this->authorize('viewAny', FiscalPeriod::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'fiscal_year_id' => $validated['fiscal_year_id'] ?? null,
            'period_number' => $validated['period_number'] ?? null,
            'name' => $validated['name'] ?? null,
            'status' => $validated['status'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $fiscalPeriods = $this->findFiscalPeriodService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new FiscalPeriodCollection($fiscalPeriods))->response();
    }

    public function store(StoreFiscalPeriodRequest $request): JsonResponse
    {
        $this->authorize('create', FiscalPeriod::class);

        $fiscalPeriod = $this->createFiscalPeriodService->execute($request->validated());

        return (new FiscalPeriodResource($fiscalPeriod))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $fiscalPeriod): FiscalPeriodResource
    {
        $foundFiscalPeriod = $this->findFiscalPeriodOrFail($fiscalPeriod);
        $this->authorize('view', $foundFiscalPeriod);

        return new FiscalPeriodResource($foundFiscalPeriod);
    }

    public function update(UpdateFiscalPeriodRequest $request, int $fiscalPeriod): FiscalPeriodResource
    {
        $foundFiscalPeriod = $this->findFiscalPeriodOrFail($fiscalPeriod);
        $this->authorize('update', $foundFiscalPeriod);

        $payload = $request->validated();
        $payload['id'] = $fiscalPeriod;

        return new FiscalPeriodResource($this->updateFiscalPeriodService->execute($payload));
    }

    public function destroy(int $fiscalPeriod): JsonResponse
    {
        $foundFiscalPeriod = $this->findFiscalPeriodOrFail($fiscalPeriod);
        $this->authorize('delete', $foundFiscalPeriod);

        $this->deleteFiscalPeriodService->execute(['id' => $fiscalPeriod]);

        return Response::json(['message' => 'Fiscal period deleted successfully']);
    }

    private function findFiscalPeriodOrFail(int $fiscalPeriodId): FiscalPeriod
    {
        $fiscalPeriod = $this->findFiscalPeriodService->find($fiscalPeriodId);

        if (! $fiscalPeriod) {
            throw new NotFoundHttpException('Fiscal period not found.');
        }

        return $fiscalPeriod;
    }
}
