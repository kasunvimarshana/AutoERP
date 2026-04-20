<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Tax\Application\Contracts\CreateTaxGroupServiceInterface;
use Modules\Tax\Application\Contracts\DeleteTaxGroupServiceInterface;
use Modules\Tax\Application\Contracts\FindTaxGroupServiceInterface;
use Modules\Tax\Application\Contracts\UpdateTaxGroupServiceInterface;
use Modules\Tax\Domain\Entities\TaxGroup;
use Modules\Tax\Infrastructure\Http\Requests\ListTaxGroupRequest;
use Modules\Tax\Infrastructure\Http\Requests\StoreTaxGroupRequest;
use Modules\Tax\Infrastructure\Http\Requests\UpdateTaxGroupRequest;
use Modules\Tax\Infrastructure\Http\Resources\TaxGroupCollection;
use Modules\Tax\Infrastructure\Http\Resources\TaxGroupResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TaxGroupController extends AuthorizedController
{
    public function __construct(
        protected CreateTaxGroupServiceInterface $createTaxGroupService,
        protected UpdateTaxGroupServiceInterface $updateTaxGroupService,
        protected DeleteTaxGroupServiceInterface $deleteTaxGroupService,
        protected FindTaxGroupServiceInterface $findTaxGroupService,
    ) {}

    public function index(ListTaxGroupRequest $request): JsonResponse
    {
        $this->authorize('viewAny', TaxGroup::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'name' => $validated['name'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $taxGroups = $this->findTaxGroupService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new TaxGroupCollection($taxGroups))->response();
    }

    public function store(StoreTaxGroupRequest $request): JsonResponse
    {
        $this->authorize('create', TaxGroup::class);

        $taxGroup = $this->createTaxGroupService->execute($request->validated());

        return (new TaxGroupResource($taxGroup))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $taxGroup): TaxGroupResource
    {
        $foundTaxGroup = $this->findTaxGroupOrFail($taxGroup);
        $this->authorize('view', $foundTaxGroup);

        return new TaxGroupResource($foundTaxGroup);
    }

    public function update(UpdateTaxGroupRequest $request, int $taxGroup): TaxGroupResource
    {
        $foundTaxGroup = $this->findTaxGroupOrFail($taxGroup);
        $this->authorize('update', $foundTaxGroup);

        $payload = $request->validated();
        $payload['id'] = $taxGroup;

        return new TaxGroupResource($this->updateTaxGroupService->execute($payload));
    }

    public function destroy(int $taxGroup): JsonResponse
    {
        $foundTaxGroup = $this->findTaxGroupOrFail($taxGroup);
        $this->authorize('delete', $foundTaxGroup);

        $this->deleteTaxGroupService->execute(['id' => $taxGroup]);

        return Response::json(['message' => 'Tax group deleted successfully']);
    }

    private function findTaxGroupOrFail(int $taxGroupId): TaxGroup
    {
        $taxGroup = $this->findTaxGroupService->find($taxGroupId);

        if (! $taxGroup) {
            throw new NotFoundHttpException('Tax group not found.');
        }

        return $taxGroup;
    }
}
