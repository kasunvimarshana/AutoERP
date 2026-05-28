<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\HR\Application\Contracts\Services\HrEmployeeManagementServiceInterface;
use Modules\HR\Presentation\Http\Requests\UpsertEmployeeAddressRequest;

final class EmployeeAddressController extends Controller
{
    public function __construct(private readonly HrEmployeeManagementServiceInterface $service)
    {
    }

    public function index(int|string $employeeId): JsonResponse
    {
        $result = $this->service->listEmployeeAddresses($employeeId);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }

    public function store(UpsertEmployeeAddressRequest $request, int|string $employeeId): JsonResponse
    {
        $result = $this->service->createEmployeeAddress($employeeId, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(['data' => $result->valueOrFail()], 201);
    }

    public function update(
        UpsertEmployeeAddressRequest $request,
        int|string $employeeId,
        int|string $addressId,
    ): JsonResponse {
        $result = $this->service->updateEmployeeAddress($employeeId, $addressId, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }

    public function destroy(int|string $employeeId, int|string $addressId): JsonResponse
    {
        $result = $this->service->deactivateEmployeeAddress($employeeId, $addressId);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }
}
