<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\Results\Result;
use Modules\Finance\Application\Contracts\UseCases\FinanceCore\CloseFiscalPeriodServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\FinanceCore\GenerateJournalEntryFromEventServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\FinanceCore\PostJournalToLedgerServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\FinanceCore\RecalculateLedgerBalancesServiceInterface;
use Modules\Finance\Presentation\Http\Requests\CloseFiscalPeriodRequest;
use Modules\Finance\Presentation\Http\Requests\GenerateJournalEntryFromEventRequest;
use Modules\Finance\Presentation\Http\Requests\PostJournalToLedgerRequest;
use Modules\Finance\Presentation\Http\Requests\RecalculateLedgerBalancesRequest;

final class FinanceCoreController extends Controller
{
    public function __construct(
        private readonly GenerateJournalEntryFromEventServiceInterface $generateJournalEntryFromEvent,
        private readonly PostJournalToLedgerServiceInterface $postJournalToLedger,
        private readonly CloseFiscalPeriodServiceInterface $closeFiscalPeriod,
        private readonly RecalculateLedgerBalancesServiceInterface $recalculateLedgerBalances,
    ) {
    }

    public function generateJournalEntryFromEvent(GenerateJournalEntryFromEventRequest $request): JsonResponse
    {
        $result = $this->generateJournalEntryFromEvent->execute($request->validated());

        return $this->toJson($result, 201);
    }

    public function postJournalEntryToLedger(
        PostJournalToLedgerRequest $request,
        int|string $journalEntry,
    ): JsonResponse {
        $result = $this->postJournalToLedger->execute($journalEntry, $request->validated());

        return $this->toJson($result);
    }

    public function closeFiscalPeriod(CloseFiscalPeriodRequest $request, int|string $fiscalPeriod): JsonResponse
    {
        $result = $this->closeFiscalPeriod->execute($fiscalPeriod, $request->validated());

        return $this->toJson($result);
    }

    public function recalculateLedgerBalances(RecalculateLedgerBalancesRequest $request): JsonResponse
    {
        $result = $this->recalculateLedgerBalances->execute($request->validated());

        return $this->toJson($result);
    }

    private function toJson(Result $result, int $successStatus = 200): JsonResponse
    {
        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = str_contains($error->code, 'NOT_FOUND') ? 404 : 422;

            return response()->json([
                'message' => $error->message,
                'code' => $error->code,
                'context' => $error->context,
            ], $status);
        }

        $value = $result->valueOrFail();
        if (is_object($value) && method_exists($value, 'toArray')) {
            $value = $value->toArray();
        }

        return response()->json(['data' => $value], $successStatus);
    }
}
