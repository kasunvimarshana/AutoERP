<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Finance\Application\Contracts\CreateFiscalYearServiceInterface;
use Modules\Finance\Application\Contracts\DeleteFiscalYearServiceInterface;
use Modules\Finance\Application\Contracts\FindFiscalYearServiceInterface;
use Modules\Finance\Application\Contracts\UpdateFiscalYearServiceInterface;
use Modules\Finance\Domain\Entities\FiscalYear;
use Modules\Finance\Infrastructure\Http\Requests\ListFiscalYearRequest;
use Modules\Finance\Infrastructure\Http\Requests\StoreFiscalYearRequest;
use Modules\Finance\Infrastructure\Http\Requests\UpdateFiscalYearRequest;
use Modules\Finance\Infrastructure\Http\Resources\FiscalYearCollection;
use Modules\Finance\Infrastructure\Http\Resources\FiscalYearResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FiscalYearController extends AuthorizedController
{
    public function __construct(
        private readonly CreateFiscalYearServiceInterface $createFiscalYearService,
        private readonly UpdateFiscalYearServiceInterface $updateFiscalYearService,
        private readonly DeleteFiscalYearServiceInterface $deleteFiscalYearService,
        private readonly FindFiscalYearServiceInterface $findFiscalYearService,
    ) {}

    public function index(ListFiscalYearRequest $request): JsonResponse
    {
        $this->authorize('viewAny', FiscalYear::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'name' => $validated['name'] ?? null,
            'status' => $validated['status'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $fiscalYears = $this->findFiscalYearService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new FiscalYearCollection($fiscalYears))->response();
    }

    public function store(StoreFiscalYearRequest $request): JsonResponse
    {
        $this->authorize('create', FiscalYear::class);

        $fiscalYear = $this->createFiscalYearService->execute($request->validated());

        return (new FiscalYearResource($fiscalYear))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $fiscalYear): FiscalYearResource
    {
        $foundFiscalYear = $this->findFiscalYearOrFail($fiscalYear);
        $this->authorize('view', $foundFiscalYear);

        return new FiscalYearResource($foundFiscalYear);
    }

    public function update(UpdateFiscalYearRequest $request, int $fiscalYear): FiscalYearResource
    {
        $foundFiscalYear = $this->findFiscalYearOrFail($fiscalYear);
        $this->authorize('update', $foundFiscalYear);

        $payload = $request->validated();
        $payload['id'] = $fiscalYear;

        return new FiscalYearResource($this->updateFiscalYearService->execute($payload));
    }

    public function destroy(int $fiscalYear): JsonResponse
    {
        $foundFiscalYear = $this->findFiscalYearOrFail($fiscalYear);
        $this->authorize('delete', $foundFiscalYear);

        $this->deleteFiscalYearService->execute(['id' => $fiscalYear]);

        return Response::json(['message' => 'Fiscal year deleted successfully']);
    }

    private function findFiscalYearOrFail(int $fiscalYearId): FiscalYear
    {
        $fiscalYear = $this->findFiscalYearService->find($fiscalYearId);

        if (! $fiscalYear) {
            throw new NotFoundHttpException('Fiscal year not found.');
        }

        return $fiscalYear;
    }
}
