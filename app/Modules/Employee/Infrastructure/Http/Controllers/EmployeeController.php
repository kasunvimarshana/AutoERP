<?php

declare(strict_types=1);

namespace Modules\Employee\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Employee\Application\Contracts\CreateEmployeeServiceInterface;
use Modules\Employee\Application\Contracts\DeleteEmployeeServiceInterface;
use Modules\Employee\Application\Contracts\FindEmployeeServiceInterface;
use Modules\Employee\Application\Contracts\UpdateEmployeeServiceInterface;
use Modules\Employee\Domain\Entities\Employee;
use Modules\Employee\Infrastructure\Http\Requests\ListEmployeeRequest;
use Modules\Employee\Infrastructure\Http\Requests\StoreEmployeeRequest;
use Modules\Employee\Infrastructure\Http\Requests\UpdateEmployeeRequest;
use Modules\Employee\Infrastructure\Http\Resources\EmployeeCollection;
use Modules\Employee\Infrastructure\Http\Resources\EmployeeResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EmployeeController extends AuthorizedController
{
    public function __construct(
        protected CreateEmployeeServiceInterface $createEmployeeService,
        protected UpdateEmployeeServiceInterface $updateEmployeeService,
        protected DeleteEmployeeServiceInterface $deleteEmployeeService,
        protected FindEmployeeServiceInterface $findEmployeeService,
    ) {}

    public function index(ListEmployeeRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Employee::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'user_id' => $validated['user_id'] ?? null,
            'org_unit_id' => $validated['org_unit_id'] ?? null,
            'employee_code' => $validated['employee_code'] ?? null,
            'job_title' => $validated['job_title'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $employees = $this->findEmployeeService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
            include: $validated['include'] ?? null,
        );

        return (new EmployeeCollection($employees))->response();
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $this->authorize('create', Employee::class);

        $payload = $request->validated();
        $avatarFile = $request->file('user.avatar');

        if ($avatarFile !== null) {
            $payload['user'] ??= [];
            $payload['user']['avatar'] = $avatarFile;
        }

        $employee = $this->createEmployeeService->execute($payload);

        return (new EmployeeResource($employee))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $employee): EmployeeResource
    {
        $foundEmployee = $this->findEmployeeOrFail($employee);
        $this->authorize('view', $foundEmployee);

        return new EmployeeResource($foundEmployee);
    }

    public function update(UpdateEmployeeRequest $request, int $employee): EmployeeResource
    {
        $foundEmployee = $this->findEmployeeOrFail($employee);
        $this->authorize('update', $foundEmployee);

        $payload = $request->validated();
        $avatarFile = $request->file('user.avatar');
        if ($avatarFile !== null) {
            $payload['user'] ??= [];
            $payload['user']['avatar'] = $avatarFile;
        }
        $payload['id'] = $employee;

        return new EmployeeResource($this->updateEmployeeService->execute($payload));
    }

    public function destroy(int $employee): JsonResponse
    {
        $foundEmployee = $this->findEmployeeOrFail($employee);
        $this->authorize('delete', $foundEmployee);

        $this->deleteEmployeeService->execute(['id' => $employee]);

        return Response::json(['message' => 'Employee deleted successfully']);
    }

    private function findEmployeeOrFail(int $employeeId): Employee
    {
        $employee = $this->findEmployeeService->find($employeeId);

        if (! $employee) {
            throw new NotFoundHttpException('Employee not found.');
        }

        return $employee;
    }
}
