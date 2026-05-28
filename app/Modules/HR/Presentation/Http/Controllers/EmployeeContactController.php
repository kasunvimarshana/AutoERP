<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\HR\Application\Contracts\Services\HrEmployeeManagementServiceInterface;
use Modules\HR\Presentation\Http\Requests\ListEmployeeContactRequest;
use Modules\HR\Presentation\Http\Requests\UpsertEmployeeContactRequest;

final class EmployeeContactController extends Controller
{
    public function __construct(
        private readonly HrEmployeeManagementServiceInterface $service,
    ) {
    }

    public function index(ListEmployeeContactRequest $request, int|string $employeeId): JsonResponse
    {
        $result = $this->service->listEmployeeContacts($employeeId);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }

    public function show(int|string $employeeId, int|string $contactId): JsonResponse
    {
        $result = $this->service->listEmployeeContacts($employeeId);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        foreach ((array) $result->valueOrFail() as $contact) {
            if ((int) ($contact['id'] ?? 0) === (int) $contactId) {
                return response()->json(['data' => $contact]);
            }
        }

        return response()->json(['message' => 'Employee contact not found.'], 404);
    }

    public function store(UpsertEmployeeContactRequest $request, int|string $employeeId): JsonResponse
    {
        $result = $this->service->createEmployeeContact($employeeId, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(['data' => $result->valueOrFail()], 201);
    }

    public function update(
        UpsertEmployeeContactRequest $request,
        int|string $employeeId,
        int|string $contactId,
    ): JsonResponse {
        $result = $this->service->updateEmployeeContact($employeeId, $contactId, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }

    public function destroy(int|string $employeeId, int|string $contactId): JsonResponse
    {
        $result = $this->service->deactivateEmployeeContact($employeeId, $contactId);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }
}
