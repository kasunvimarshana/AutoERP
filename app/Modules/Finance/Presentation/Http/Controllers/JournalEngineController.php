<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Finance\Application\Contracts\UseCases\JournalEngines\PostJournalEntryServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\JournalEngines\PreviewJournalEntryPostingServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\JournalEngines\ReverseJournalEntryServiceInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Modules\Finance\Presentation\Http\Requests\PostJournalEntryRequest;
use Modules\Finance\Presentation\Http\Requests\ReverseJournalEntryRequest;

final class JournalEngineController extends Controller
{
    public function __construct(
        private readonly PostJournalEntryServiceInterface $postJournalEntryService,
        private readonly PreviewJournalEntryPostingServiceInterface $previewJournalEntryPostingService,
        private readonly ReverseJournalEntryServiceInterface $reverseJournalEntryService,
    ) {}

    public function previewPost(
        PostJournalEntryRequest $request,
        int|string $journalEntry,
    ): JsonResponse {
        $result = $this->previewJournalEntryPostingService->execute($journalEntry, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === FinanceErrorCode::NOT_FOUND ? 404 : 422;

            return response()->json([
                'message' => $error->message,
                'code' => $error->code,
                'context' => $error->context,
            ], $status);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }

    public function previewSourcePosting(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'journal_entry_id' => ['nullable', 'integer', 'min:1', 'exists:journal_entries,id'],
            'source_module' => ['nullable', 'string', 'max:100'],
            'source_type' => ['nullable', 'string', 'max:100'],
            'source_id' => ['nullable', 'integer', 'min:1'],
            'source_reference' => ['nullable', 'string', 'max:255'],
            'posting_date' => ['nullable', 'date'],
            'expected_row_version' => ['nullable', 'integer', 'min:1'],
        ]);

        if (isset($validated['journal_entry_id'])) {
            $result = $this->previewJournalEntryPostingService->execute(
                (int) $validated['journal_entry_id'],
                $validated,
            );

            if ($result->isFailure()) {
                $error = $result->errorOrFail();
                $status = $error->code === FinanceErrorCode::NOT_FOUND ? 404 : 422;

                return response()->json([
                    'message' => $error->message,
                    'code' => $error->code,
                    'context' => $error->context,
                ], $status);
            }

            return response()->json(['data' => $result->valueOrFail()]);
        }

        return response()->json([
            'input' => $validated,
            'calculated' => [
                'can_preview' => false,
                'posting_eligibility' => 'journal_entry_id_required',
            ],
            'breakdown' => [],
            'warnings' => ['Provide journal_entry_id for backend journal posting preview.'],
            'errors' => [],
        ]);
    }

    public function post(
        PostJournalEntryRequest $request,
        int|string $journalEntry,
    ): JsonResponse {
        $result = $this->postJournalEntryService->execute($journalEntry, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === FinanceErrorCode::NOT_FOUND ? 404 : 422;

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
            $status = $error->code === FinanceErrorCode::NOT_FOUND ? 404 : 422;

            return response()->json([
                'message' => $error->message,
                'code' => $error->code,
                'context' => $error->context,
            ], $status);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }
}
