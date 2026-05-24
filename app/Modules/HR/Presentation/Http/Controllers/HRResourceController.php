<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HR\Application\DTOs\HRRecordData;
use Modules\HR\Application\Services\HRService;
use Modules\HR\Domain\Exceptions\HRIntegrityException;
use Modules\HR\Domain\Exceptions\HRRecordNotFoundException;
use Modules\HR\Presentation\Http\Requests\HRRecordRequest;
use Modules\HR\Presentation\Http\Resources\HRRecordResource;

class HRResourceController extends Controller
{
    public function __construct(private readonly HRService $hr) {}

    public function index(Request $request, int|string $tenant, string $resource): mixed
    {
        try {
            return HRRecordResource::collection(
                $this->hr->list($resource, $tenant, $this->filters($request), $this->perPage($request)),
            );
        } catch (HRRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function store(HRRecordRequest $request, int|string $tenant, string $resource): JsonResponse
    {
        try {
            $record = $this->hr->create($resource, HRRecordData::fromArray($tenant, $request->validated()));

            return (new HRRecordResource($record))->response()->setStatusCode(201);
        } catch (HRIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (HRRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function show(int|string $tenant, string $resource, int|string $id): HRRecordResource|JsonResponse
    {
        try {
            return new HRRecordResource($this->hr->find($resource, $tenant, $id));
        } catch (HRRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(HRRecordRequest $request, int|string $tenant, string $resource, int|string $id): HRRecordResource|JsonResponse
    {
        try {
            return new HRRecordResource(
                $this->hr->update($resource, $tenant, $id, HRRecordData::fromArray($tenant, $request->validated())),
            );
        } catch (HRIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (HRRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $tenant, string $resource, int|string $id): JsonResponse
    {
        try {
            $this->hr->delete($resource, $tenant, $id);

            return response()->json(null, 204);
        } catch (HRIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (HRRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return collect($request->only([
            'organization_unit_id',
            'parent_id',
            'name',
            'code',
            'registration_number',
            'employee_id',
            'department_id',
            'designation_id',
            'employment_type_id',
            'status',
            'is_active',
            'shift_id',
            'attendance_date',
            'leave_type_id',
            'leave_policy_id',
            'year',
            'payroll_run_id',
            'payslip_id',
            'salary_structure_id',
            'salary_component_id',
            'cycle_id',
            'reviewer_id',
            'holiday_date',
            'holiday_type',
            'type',
        ]))->filter(fn (mixed $value): bool => $value !== null && $value !== '')->all();
    }

    private function perPage(Request $request): ?int
    {
        return $request->filled('per_page') ? max(1, min((int) $request->integer('per_page'), 100)) : null;
    }

    private function notFound(HRRecordNotFoundException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 404);
    }

    private function unprocessable(HRIntegrityException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 422);
    }
}
