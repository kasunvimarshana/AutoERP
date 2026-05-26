<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Finance\Application\Contracts\UseCases\JournalEngines\PostJournalEntryServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\JournalEngines\ReverseJournalEntryServiceInterface;
use Modules\Finance\Presentation\Http\Requests\PostJournalEntryRequest;
use Modules\Finance\Presentation\Http\Requests\ReverseJournalEntryRequest;

final class JournalEngineController extends Controller
{
    public function __construct(
        private readonly PostJournalEntryServiceInterface $postJournalEntryService,
        private readonly ReverseJournalEntryServiceInterface $reverseJournalEntryService,
    ) {
    }

    public function post(
        PostJournalEntryRequest $request,
        int|string $journalEntry,
    ): JsonResponse {
        $result = $this->postJournalEntryService->execute($journalEntry, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'FINANCE_NOT_FOUND' ? 404 : 422;

            return response()->json([
                'message' => $error->message,
                'code' => $error->code,
                'context' => $error->context,
            ], $status);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }

    public function reverse(
        ReverseJournalEntryRequest $request,
        int|string $journalEntry,
    ): JsonResponse {
        $result = $this->reverseJournalEntryService->execute($journalEntry, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'FINANCE_NOT_FOUND' ? 404 : 422;

            return response()->json([
                'message' => $error->message,
                'code' => $error->code,
                'context' => $error->context,
            ], $status);
        }

        return response()->json(['data' => $result->valueOrFail()->toArray()]);
    }
}
