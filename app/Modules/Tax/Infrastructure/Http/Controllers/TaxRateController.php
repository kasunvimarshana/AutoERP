<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Tax\Application\Contracts\CreateTaxRateServiceInterface;
use Modules\Tax\Application\Contracts\DeleteTaxRateServiceInterface;
use Modules\Tax\Application\Contracts\FindTaxRateServiceInterface;
use Modules\Tax\Application\Contracts\UpdateTaxRateServiceInterface;
use Modules\Tax\Domain\Entities\TaxRate;
use Modules\Tax\Infrastructure\Http\Requests\ListTaxRateRequest;
use Modules\Tax\Infrastructure\Http\Requests\StoreTaxRateRequest;
use Modules\Tax\Infrastructure\Http\Requests\UpdateTaxRateRequest;
use Modules\Tax\Infrastructure\Http\Resources\TaxRateCollection;
use Modules\Tax\Infrastructure\Http\Resources\TaxRateResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TaxRateController extends AuthorizedController
{
    public function __construct(
        protected CreateTaxRateServiceInterface $createTaxRateService,
        protected UpdateTaxRateServiceInterface $updateTaxRateService,
        protected DeleteTaxRateServiceInterface $deleteTaxRateService,
        protected FindTaxRateServiceInterface $findTaxRateService,
    ) {}

    public function index(ListTaxRateRequest $request, int $taxGroup): JsonResponse
    {
        $this->authorize('viewAny', TaxRate::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'tax_group_id' => $taxGroup,
            'name' => $validated['name'] ?? null,
            'type' => $validated['type'] ?? null,
            'is_compound' => $validated['is_compound'] ?? null,
            'is_active' => $validated['is_active'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $taxRates = $this->findTaxRateService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new TaxRateCollection($taxRates))->response();
    }

    public function store(StoreTaxRateRequest $request, int $taxGroup): JsonResponse
    {
        $this->authorize('create', TaxRate::class);

        $payload = $request->validated();
        $payload['tax_group_id'] = $taxGroup;

        $taxRate = $this->createTaxRateService->execute($payload);

        return (new TaxRateResource($taxRate))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $taxGroup, int $taxRate): TaxRateResource
    {
        $foundTaxRate = $this->findTaxRateOrFail($taxRate);
        $this->authorize('view', $foundTaxRate);

        if ($foundTaxRate->getTaxGroupId() !== $taxGroup) {
            throw new NotFoundHttpException('Tax rate not found for this tax group.');
        }

        return new TaxRateResource($foundTaxRate);
    }

    public function update(UpdateTaxRateRequest $request, int $taxGroup, int $taxRate): TaxRateResource
    {
        $foundTaxRate = $this->findTaxRateOrFail($taxRate);
        $this->authorize('update', $foundTaxRate);

        if ($foundTaxRate->getTaxGroupId() !== $taxGroup) {
            throw new NotFoundHttpException('Tax rate not found for this tax group.');
        }

        $payload = $request->validated();
        $payload['id'] = $taxRate;
        $payload['tax_group_id'] = $taxGroup;

        return new TaxRateResource($this->updateTaxRateService->execute($payload));
    }

    public function destroy(int $taxGroup, int $taxRate): JsonResponse
    {
        $foundTaxRate = $this->findTaxRateOrFail($taxRate);
        $this->authorize('delete', $foundTaxRate);

        if ($foundTaxRate->getTaxGroupId() !== $taxGroup) {
            throw new NotFoundHttpException('Tax rate not found for this tax group.');
        }

        $this->deleteTaxRateService->execute(['id' => $taxRate]);

        return Response::json(['message' => 'Tax rate deleted successfully']);
    }

    private function findTaxRateOrFail(int $taxRateId): TaxRate
    {
        $taxRate = $this->findTaxRateService->find($taxRateId);

        if (! $taxRate) {
            throw new NotFoundHttpException('Tax rate not found.');
        }

        return $taxRate;
    }
}
