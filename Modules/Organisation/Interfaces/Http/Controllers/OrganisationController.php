<?php

declare(strict_types=1);

namespace Modules\Organisation\Interfaces\Http\Controllers;

use App\Shared\Abstractions\BaseController;
use Illuminate\Http\JsonResponse;
use Modules\Organisation\Application\Commands\CreateOrganisationCommand;
use Modules\Organisation\Application\Commands\DeleteOrganisationCommand;
use Modules\Organisation\Application\Commands\UpdateOrganisationCommand;
use Modules\Organisation\Application\Services\OrganisationService;
use Modules\Organisation\Interfaces\Http\Requests\CreateOrganisationRequest;
use Modules\Organisation\Interfaces\Http\Requests\UpdateOrganisationRequest;
use Modules\Organisation\Interfaces\Http\Resources\OrganisationResource;

class OrganisationController extends BaseController
{
    public function __construct(
        private readonly OrganisationService $organisationService,
    ) {}

    public function index(): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $page = (int) request('page', 1);
        $perPage = (int) request('per_page', 25);

        $result = $this->organisationService->listOrganisations($tenantId, $page, $perPage);

        return $this->success(
            data: array_map(
                fn ($org) => (new OrganisationResource($org))->resolve(),
                $result['items']
            ),
            message: 'Organisation nodes retrieved successfully',
            meta: [
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
            ],
        );
    }

    public function store(CreateOrganisationRequest $request): JsonResponse
    {
        try {
            $organisation = $this->organisationService->createOrganisation(new CreateOrganisationCommand(
                tenantId: $request->validated('tenant_id'),
                type: $request->validated('type'),
                name: $request->validated('name'),
                code: $request->validated('code'),
                parentId: $request->validated('parent_id'),
                description: $request->validated('description'),
                meta: $request->validated('meta'),
            ));
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }

        return $this->success(
            data: (new OrganisationResource($organisation))->resolve(),
            message: 'Organisation node created successfully',
            status: 201,
        );
    }

    public function show(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $organisation = $this->organisationService->findOrganisationById($id, $tenantId);

        if ($organisation === null) {
            return $this->error('Organisation node not found', status: 404);
        }

        return $this->success(
            data: (new OrganisationResource($organisation))->resolve(),
            message: 'Organisation node retrieved successfully',
        );
    }

    public function update(UpdateOrganisationRequest $request, int $id): JsonResponse
    {
        try {
            $organisation = $this->organisationService->updateOrganisation(new UpdateOrganisationCommand(
                id: $id,
                tenantId: $request->validated('tenant_id'),
                name: $request->validated('name'),
                parentId: $request->validated('parent_id'),
                description: $request->validated('description'),
                status: $request->validated('status'),
                meta: $request->validated('meta'),
            ));
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }

        return $this->success(
            data: (new OrganisationResource($organisation))->resolve(),
            message: 'Organisation node updated successfully',
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $this->organisationService->deleteOrganisation(new DeleteOrganisationCommand($id, $tenantId));

            return $this->success(message: 'Organisation node deleted successfully');
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 404);
        }
    }

    public function children(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $children = $this->organisationService->listChildren($id, $tenantId);

        return $this->success(
            data: array_map(
                fn ($org) => (new OrganisationResource($org))->resolve(),
                $children
            ),
            message: 'Child organisation nodes retrieved successfully',
        );
    }
}
