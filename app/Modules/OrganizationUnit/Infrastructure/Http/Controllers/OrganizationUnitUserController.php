<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\OrganizationUnit\Application\Contracts\CreateOrganizationUnitUserServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitUserServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\FindOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\FindOrganizationUnitUserServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\UpdateOrganizationUnitUserServiceInterface;
use Modules\OrganizationUnit\Application\DTOs\OrganizationUnitUserData;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitUser;
use Modules\OrganizationUnit\Infrastructure\Http\Requests\ListOrganizationUnitUserRequest;
use Modules\OrganizationUnit\Infrastructure\Http\Requests\StoreOrganizationUnitUserRequest;
use Modules\OrganizationUnit\Infrastructure\Http\Requests\UpdateOrganizationUnitUserRequest;
use Modules\OrganizationUnit\Infrastructure\Http\Resources\OrganizationUnitUserCollection;
use Modules\OrganizationUnit\Infrastructure\Http\Resources\OrganizationUnitUserResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OrganizationUnitUserController extends AuthorizedController
{
    public function __construct(
        private readonly CreateOrganizationUnitUserServiceInterface $createOrganizationUnitUserService,
        private readonly FindOrganizationUnitUserServiceInterface $findOrganizationUnitUserService,
        private readonly UpdateOrganizationUnitUserServiceInterface $updateOrganizationUnitUserService,
        private readonly DeleteOrganizationUnitUserServiceInterface $deleteOrganizationUnitUserService,
        private readonly FindOrganizationUnitServiceInterface $findOrganizationUnitService,
    ) {}

    public function index(int $organizationUnitId, ListOrganizationUnitUserRequest $request): JsonResponse
    {
        $organizationUnit = $this->findOrganizationUnitOrFail($organizationUnitId);
        $this->authorize('view', $organizationUnit);

        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? $organizationUnit->getTenantId(),
            'org_unit_id' => $organizationUnitId,
            'user_id' => $validated['user_id'] ?? null,
            'role' => $validated['role'] ?? null,
            'is_primary' => $validated['is_primary'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $perPage = (int) ($validated['per_page'] ?? 15);
        $page = (int) ($validated['page'] ?? 1);
        $sort = $validated['sort'] ?? null;

        $organizationUnitUsers = $this->findOrganizationUnitUserService->list($filters, $perPage, $page, $sort);

        return (new OrganizationUnitUserCollection($organizationUnitUsers))->response();
    }

    public function show(int $organizationUnitId, int $organizationUnitUserId): OrganizationUnitUserResource
    {
        $organizationUnit = $this->findOrganizationUnitOrFail($organizationUnitId);
        $this->authorize('view', $organizationUnit);

        $organizationUnitUser = $this->findOrganizationUnitUserOrFail($organizationUnitUserId);
        if ($organizationUnitUser->getOrganizationUnitId() !== $organizationUnitId) {
            throw new NotFoundHttpException('Organization unit user not found.');
        }

        return new OrganizationUnitUserResource($organizationUnitUser);
    }

    public function store(int $organizationUnitId, StoreOrganizationUnitUserRequest $request): JsonResponse
    {
        $organizationUnit = $this->findOrganizationUnitOrFail($organizationUnitId);
        $this->authorize('update', $organizationUnit);

        $payload = $request->validated();
        $payload['org_unit_id'] = $organizationUnitId;
        $payload['tenant_id'] = $organizationUnit->getTenantId();

        $dto = OrganizationUnitUserData::fromArray($payload);
        $saved = $this->createOrganizationUnitUserService->execute($dto->toArray());

        return (new OrganizationUnitUserResource($saved))->response()->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function update(int $organizationUnitId, int $organizationUnitUserId, UpdateOrganizationUnitUserRequest $request): OrganizationUnitUserResource
    {
        $organizationUnit = $this->findOrganizationUnitOrFail($organizationUnitId);
        $this->authorize('update', $organizationUnit);

        $organizationUnitUser = $this->findOrganizationUnitUserOrFail($organizationUnitUserId);
        if ($organizationUnitUser->getOrganizationUnitId() !== $organizationUnitId) {
            throw new NotFoundHttpException('Organization unit user not found.');
        }

        $payload = $request->validated();
        $payload['id'] = $organizationUnitUserId;

        $saved = $this->updateOrganizationUnitUserService->execute($payload);

        return new OrganizationUnitUserResource($saved);
    }

    public function destroy(int $organizationUnitId, int $organizationUnitUserId): JsonResponse
    {
        $organizationUnit = $this->findOrganizationUnitOrFail($organizationUnitId);
        $this->authorize('delete', $organizationUnit);

        $organizationUnitUser = $this->findOrganizationUnitUserOrFail($organizationUnitUserId);
        if ($organizationUnitUser->getOrganizationUnitId() !== $organizationUnitId) {
            throw new NotFoundHttpException('Organization unit user not found.');
        }

        $this->deleteOrganizationUnitUserService->execute([
            'id' => $organizationUnitUserId,
            'tenant_id' => $organizationUnit->getTenantId(),
            'org_unit_id' => $organizationUnitId,
        ]);

        return Response::json(['message' => 'Organization unit user deleted successfully']);
    }

    private function findOrganizationUnitOrFail(int $organizationUnitId): OrganizationUnit
    {
        $organizationUnit = $this->findOrganizationUnitService->find($organizationUnitId);
        if (! $organizationUnit) {
            throw new NotFoundHttpException('Organization unit not found.');
        }

        return $organizationUnit;
    }

    private function findOrganizationUnitUserOrFail(int $organizationUnitUserId): OrganizationUnitUser
    {
        $organizationUnitUser = $this->findOrganizationUnitUserService->find($organizationUnitUserId);
        if (! $organizationUnitUser) {
            throw new NotFoundHttpException('Organization unit user not found.');
        }

        return $organizationUnitUser;
    }
}
