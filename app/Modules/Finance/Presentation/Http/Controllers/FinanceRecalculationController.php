<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Finance\Application\Services\FinanceService;
use Modules\Finance\Domain\Exceptions\FinanceRecordNotFoundException;
use Modules\Finance\Presentation\Http\Resources\FinanceRecordResource;

class FinanceRecalculationController extends Controller
{
    public function __construct(private readonly FinanceService $finance) {}

    public function bankAccountBalance(int|string $tenant, int|string $bankAccount): FinanceRecordResource|JsonResponse
    {
        try {
            return new FinanceRecordResource($this->finance->recalculateBankAccountBalance($tenant, $bankAccount));
        } catch (FinanceRecordNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }
}
