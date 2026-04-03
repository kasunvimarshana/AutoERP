<?php

declare(strict_types=1);

namespace Modules\Taxation\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Taxation\Application\Contracts\CreateTaxRuleServiceInterface;
use Modules\Taxation\Application\Contracts\DeleteTaxRuleServiceInterface;
use Modules\Taxation\Application\Contracts\FindTaxRuleServiceInterface;
use Modules\Taxation\Application\Contracts\UpdateTaxRuleServiceInterface;
use Modules\Taxation\Application\DTOs\TaxRuleData;
use Modules\Taxation\Application\DTOs\UpdateTaxRuleData;
use Modules\Taxation\Infrastructure\Http\Requests\StoreTaxRuleRequest;
use Modules\Taxation\Infrastructure\Http\Requests\UpdateTaxRuleRequest;
use Modules\Taxation\Infrastructure\Http\Resources\TaxRuleCollection;
use Modules\Taxation\Infrastructure\Http\Resources\TaxRuleResource;

class TaxRuleController extends AuthorizedController
{
    public function __construct(
        protected FindTaxRuleServiceInterface $findService,
        protected CreateTaxRuleServiceInterface $createService,
        protected UpdateTaxRuleServiceInterface $updateService,
        protected DeleteTaxRuleServiceInterface $deleteService,
    ) {}

    public function index(Request $request): TaxRuleCollection
    {
        $filters = $request->only(['tenant_id', 'entity_type', 'entity_id', 'is_active', 'tax_rate_id']);

        return new TaxRuleCollection(
            $this->findService->list($filters, $request->integer('per_page', 15), $request->integer('page', 1))
        );
    }

    public function store(StoreTaxRuleRequest $request): JsonResponse
    {
        $v = $request->validated();

        $dto = TaxRuleData::fromArray([
            'tenantId'    => $v['tenant_id'],
            'name'        => $v['name'],
            'taxRateId'   => $v['tax_rate_id'],
            'entityType'  => $v['entity_type'],
            'entityId'    => $v['entity_id'] ?? null,
            'jurisdiction'=> $v['jurisdiction'] ?? null,
            'priority'    => $v['priority'] ?? 0,
            'isActive'    => $v['is_active'] ?? true,
            'description' => $v['description'] ?? null,
            'metadata'    => $v['metadata'] ?? null,
        ]);

        $taxRule = $this->createService->execute($dto->toArray());

        return (new TaxRuleResource($taxRule))->response()->setStatusCode(201);
    }

    public function show(int $id): TaxRuleResource|JsonResponse
    {
        $taxRule = $this->findService->find($id);

        if (!$taxRule) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return new TaxRuleResource($taxRule);
    }

    public function update(UpdateTaxRuleRequest $request, int $id): TaxRuleResource
    {
        $v = $request->validated();

        $dto = UpdateTaxRuleData::fromArray(array_merge(['id' => $id], [
            'name'        => $v['name'] ?? null,
            'taxRateId'   => $v['tax_rate_id'] ?? null,
            'entityType'  => $v['entity_type'] ?? null,
            'entityId'    => $v['entity_id'] ?? null,
            'jurisdiction'=> $v['jurisdiction'] ?? null,
            'priority'    => $v['priority'] ?? null,
            'isActive'    => $v['is_active'] ?? null,
            'description' => $v['description'] ?? null,
            'metadata'    => $v['metadata'] ?? null,
        ]));

        return new TaxRuleResource($this->updateService->execute($dto->toArray()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Tax rule deleted successfully']);
    }
}
