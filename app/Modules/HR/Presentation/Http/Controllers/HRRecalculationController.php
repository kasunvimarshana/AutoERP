<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\HR\Application\Services\HRService;
use Modules\HR\Domain\Exceptions\HRRecordNotFoundException;
use Modules\HR\Presentation\Http\Resources\HRRecordResource;

class HRRecalculationController extends Controller
{
    public function __construct(private readonly HRService $hr) {}

    public function leaveAllocation(int|string $tenant, int|string $allocation): HRRecordResource|JsonResponse
    {
        try {
            return new HRRecordResource($this->hr->recalculateLeaveAllocation($tenant, $allocation));
        } catch (HRRecordNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }

    public function payslip(int|string $tenant, int|string $payslip): HRRecordResource|JsonResponse
    {
        try {
            return new HRRecordResource($this->hr->recalculatePayslip($tenant, $payslip));
        } catch (HRRecordNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }

    public function payrollRun(int|string $tenant, int|string $payrollRun): HRRecordResource|JsonResponse
    {
        try {
            return new HRRecordResource($this->hr->recalculatePayrollRun($tenant, $payrollRun));
        } catch (HRRecordNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }
}
