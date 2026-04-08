<?php

declare(strict_types=1);

namespace Modules\Taxation\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Taxation\Application\Contracts\ActivateTaxRateServiceInterface;
use Modules\Taxation\Application\Contracts\CreateTaxRateServiceInterface;
use Modules\Taxation\Application\Contracts\DeactivateTaxRateServiceInterface;
use Modules\Taxation\Application\Contracts\DeleteTaxRateServiceInterface;
use Modules\Taxation\Application\Contracts\FindTaxRateServiceInterface;
use Modules\Taxation\Application\Contracts\UpdateTaxRateServiceInterface;
use Modules\Taxation\Application\DTOs\TaxRateData;
use Modules\Taxation\Application\DTOs\UpdateTaxRateData;
use Modules\Taxation\Infrastructure\Http\Requests\StoreTaxRateRequest;
use Modules\Taxation\Infrastructure\Http\Requests\UpdateTaxRateRequest;
use Modules\Taxation\Infrastructure\Http\Resources\TaxRateCollection;
use Modules\Taxation\Infrastructure\Http\Resources\TaxRateResource;

class TaxRateController extends AuthorizedController
{
    public function __construct(
        protected FindTaxRateServiceInterface $findService,
        protected CreateTaxRateServiceInterface $createService,
        protected UpdateTaxRateServiceInterface $updateService,
        protected DeleteTaxRateServiceInterface $deleteService,
        protected ActivateTaxRateServiceInterface $activateService,
        protected DeactivateTaxRateServiceInterface $deactivateService,
    ) {}

    public function index(Request $request): TaxRateCollection
    {
        $filters = $request->only(['tenant_id', 'tax_type', 'jurisdiction', 'is_active']);

        return new TaxRateCollection(
            $this->findService->list($filters, $request->integer('per_page', 15), $request->integer('page', 1))
        );
    }

    public function store(StoreTaxRateRequest $request): JsonResponse
    {
        $v = $request->validated();

        $dto = TaxRateData::fromArray([
            'tenantId'          => $v['tenant_id'],
            'name'              => $v['name'],
            'code'              => $v['code'],
            'taxType'           => $v['tax_type'],
            'calculationMethod' => $v['calculation_method'],
            'rate'              => $v['rate'],
            'jurisdiction'      => $v['jurisdiction'] ?? null,
            'isActive'          => $v['is_active'] ?? true,
            'description'       => $v['description'] ?? null,
            'effectiveFrom'     => $v['effective_from'] ?? null,
            'effectiveTo'       => $v['effective_to'] ?? null,
            'metadata'          => $v['metadata'] ?? null,
        ]);

        $taxRate = $this->createService->execute($dto->toArray());

        return (new TaxRateResource($taxRate))->response()->setStatusCode(201);
    }

    public function show(int $id): TaxRateResource|JsonResponse
    {
        $taxRate = $this->findService->find($id);

        if (!$taxRate) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return new TaxRateResource($taxRate);
    }

    public function update(UpdateTaxRateRequest $request, int $id): TaxRateResource
    {
        $v = $request->validated();

        $dto = UpdateTaxRateData::fromArray(array_merge(['id' => $id], [
            'name'              => $v['name'] ?? null,
            'code'              => $v['code'] ?? null,
            'taxType'           => $v['tax_type'] ?? null,
            'calculationMethod' => $v['calculation_method'] ?? null,
            'rate'              => $v['rate'] ?? null,
            'jurisdiction'      => $v['jurisdiction'] ?? null,
            'isActive'          => $v['is_active'] ?? null,
            'description'       => $v['description'] ?? null,
            'effectiveFrom'     => $v['effective_from'] ?? null,
            'effectiveTo'       => $v['effective_to'] ?? null,
            'metadata'          => $v['metadata'] ?? null,
        ]));

        return new TaxRateResource($this->updateService->execute($dto->toArray()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Tax rate deleted successfully']);
    }

    public function activate(int $id): TaxRateResource
    {
        return new TaxRateResource($this->activateService->execute(['id' => $id]));
    }

    public function deactivate(int $id): TaxRateResource
    {
        return new TaxRateResource($this->deactivateService->execute(['id' => $id]));
    }
}
