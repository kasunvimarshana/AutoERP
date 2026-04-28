<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\OrganizationUnit\Application\Contracts\CreateOrganizationUnitTypeServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitTypeServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\FindOrganizationUnitTypeServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\UpdateOrganizationUnitTypeServiceInterface;
use Modules\OrganizationUnit\Application\DTOs\OrganizationUnitTypeData;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitType;
use Modules\OrganizationUnit\Infrastructure\Http\Requests\ListOrganizationUnitTypeRequest;
use Modules\OrganizationUnit\Infrastructure\Http\Requests\StoreOrganizationUnitTypeRequest;
use Modules\OrganizationUnit\Infrastructure\Http\Requests\UpdateOrganizationUnitTypeRequest;
use Modules\OrganizationUnit\Infrastructure\Http\Resources\OrganizationUnitTypeCollection;
use Modules\OrganizationUnit\Infrastructure\Http\Resources\OrganizationUnitTypeResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OrganizationUnitTypeController extends AuthorizedController
{
    public function __construct(
        private readonly CreateOrganizationUnitTypeServiceInterface $createOrganizationUnitTypeService,
        private readonly FindOrganizationUnitTypeServiceInterface $findOrganizationUnitTypeService,
        private readonly UpdateOrganizationUnitTypeServiceInterface $updateOrganizationUnitTypeService,
        private readonly DeleteOrganizationUnitTypeServiceInterface $deleteOrganizationUnitTypeService,
    ) {}

    public function index(ListOrganizationUnitTypeRequest $request): JsonResponse
    {
        $this->authorize('viewAny', OrganizationUnit::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'name' => $validated['name'] ?? null,
            'level' => $validated['level'] ?? null,
            'is_active' => $validated['is_active'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $perPage = (int) ($validated['per_page'] ?? 15);
        $page = (int) ($validated['page'] ?? 1);
        $sort = $validated['sort'] ?? null;

        $organizationUnitTypes = $this->findOrganizationUnitTypeService->list($filters, $perPage, $page, $sort);

        return (new OrganizationUnitTypeCollection($organizationUnitTypes))->response();
    }

    public function show(int $organizationUnitTypeId): OrganizationUnitTypeResource
    {
        $this->authorize('viewAny', OrganizationUnit::class);

        return new OrganizationUnitTypeResource($this->findOrganizationUnitTypeOrFail($organizationUnitTypeId));
    }

    public function store(StoreOrganizationUnitTypeRequest $request): JsonResponse
    {
        $this->authorize('create', OrganizationUnit::class);

        $dto = OrganizationUnitTypeData::fromArray($request->validated());
        $saved = $this->createOrganizationUnitTypeService->execute($dto->toArray());

        return (new OrganizationUnitTypeResource($saved))->response()->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function update(UpdateOrganizationUnitTypeRequest $request, int $organizationUnitTypeId): OrganizationUnitTypeResource
    {
        $this->authorize('update', OrganizationUnit::class);
        $this->findOrganizationUnitTypeOrFail($organizationUnitTypeId);

        $payload = $request->validated();
        $payload['id'] = $organizationUnitTypeId;

        $saved = $this->updateOrganizationUnitTypeService->execute($payload);

        return new OrganizationUnitTypeResource($saved);
    }

    public function destroy(int $organizationUnitTypeId): JsonResponse
    {
        $this->authorize('delete', OrganizationUnit::class);
        $organizationUnitType = $this->findOrganizationUnitTypeOrFail($organizationUnitTypeId);

        $this->deleteOrganizationUnitTypeService->execute([
            'id' => $organizationUnitTypeId,
            'tenant_id' => $organizationUnitType->getTenantId(),
        ]);

        return Response::json(['message' => 'Organization unit type deleted successfully']);
    }

    private function findOrganizationUnitTypeOrFail(int $organizationUnitTypeId): OrganizationUnitType
    {
        $organizationUnitType = $this->findOrganizationUnitTypeService->find($organizationUnitTypeId);
        if (! $organizationUnitType) {
            throw new NotFoundHttpException('Organization unit type not found.');
        }

        return $organizationUnitType;
    }
}
