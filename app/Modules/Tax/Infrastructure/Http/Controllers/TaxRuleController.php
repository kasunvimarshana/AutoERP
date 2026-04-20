<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Tax\Application\Contracts\CreateTaxRuleServiceInterface;
use Modules\Tax\Application\Contracts\DeleteTaxRuleServiceInterface;
use Modules\Tax\Application\Contracts\FindTaxRuleServiceInterface;
use Modules\Tax\Application\Contracts\UpdateTaxRuleServiceInterface;
use Modules\Tax\Domain\Entities\TaxRule;
use Modules\Tax\Infrastructure\Http\Requests\ListTaxRuleRequest;
use Modules\Tax\Infrastructure\Http\Requests\StoreTaxRuleRequest;
use Modules\Tax\Infrastructure\Http\Requests\UpdateTaxRuleRequest;
use Modules\Tax\Infrastructure\Http\Resources\TaxRuleCollection;
use Modules\Tax\Infrastructure\Http\Resources\TaxRuleResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TaxRuleController extends AuthorizedController
{
    public function __construct(
        protected CreateTaxRuleServiceInterface $createTaxRuleService,
        protected UpdateTaxRuleServiceInterface $updateTaxRuleService,
        protected DeleteTaxRuleServiceInterface $deleteTaxRuleService,
        protected FindTaxRuleServiceInterface $findTaxRuleService,
    ) {}

    public function index(ListTaxRuleRequest $request, int $taxGroup): JsonResponse
    {
        $this->authorize('viewAny', TaxRule::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'tax_group_id' => $taxGroup,
            'product_category_id' => $validated['product_category_id'] ?? null,
            'party_type' => $validated['party_type'] ?? null,
            'region' => $validated['region'] ?? null,
            'priority' => $validated['priority'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $taxRules = $this->findTaxRuleService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new TaxRuleCollection($taxRules))->response();
    }

    public function store(StoreTaxRuleRequest $request, int $taxGroup): JsonResponse
    {
        $this->authorize('create', TaxRule::class);

        $payload = $request->validated();
        $payload['tax_group_id'] = $taxGroup;

        $taxRule = $this->createTaxRuleService->execute($payload);

        return (new TaxRuleResource($taxRule))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $taxGroup, int $taxRule): TaxRuleResource
    {
        $foundTaxRule = $this->findTaxRuleOrFail($taxRule);
        $this->authorize('view', $foundTaxRule);

        if ($foundTaxRule->getTaxGroupId() !== $taxGroup) {
            throw new NotFoundHttpException('Tax rule not found for this tax group.');
        }

        return new TaxRuleResource($foundTaxRule);
    }

    public function update(UpdateTaxRuleRequest $request, int $taxGroup, int $taxRule): TaxRuleResource
    {
        $foundTaxRule = $this->findTaxRuleOrFail($taxRule);
        $this->authorize('update', $foundTaxRule);

        if ($foundTaxRule->getTaxGroupId() !== $taxGroup) {
            throw new NotFoundHttpException('Tax rule not found for this tax group.');
        }

        $payload = $request->validated();
        $payload['id'] = $taxRule;
        $payload['tax_group_id'] = $taxGroup;

        return new TaxRuleResource($this->updateTaxRuleService->execute($payload));
    }

    public function destroy(int $taxGroup, int $taxRule): JsonResponse
    {
        $foundTaxRule = $this->findTaxRuleOrFail($taxRule);
        $this->authorize('delete', $foundTaxRule);

        if ($foundTaxRule->getTaxGroupId() !== $taxGroup) {
            throw new NotFoundHttpException('Tax rule not found for this tax group.');
        }

        $this->deleteTaxRuleService->execute(['id' => $taxRule]);

        return Response::json(['message' => 'Tax rule deleted successfully']);
    }

    private function findTaxRuleOrFail(int $taxRuleId): TaxRule
    {
        $taxRule = $this->findTaxRuleService->find($taxRuleId);

        if (! $taxRule) {
            throw new NotFoundHttpException('Tax rule not found.');
        }

        return $taxRule;
    }
}
