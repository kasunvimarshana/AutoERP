<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Finance\Application\Services\FinanceService;
use Modules\Finance\Domain\Exceptions\FinanceIntegrityException;
use Modules\Finance\Domain\Exceptions\FinanceRecordNotFoundException;
use Modules\Finance\Presentation\Http\Requests\PostJournalEntryRequest;
use Modules\Finance\Presentation\Http\Resources\FinanceRecordResource;

class FinanceJournalEntryController extends Controller
{
    public function __construct(private readonly FinanceService $finance) {}

    public function post(PostJournalEntryRequest $request, int|string $tenant, int|string $journalEntry): FinanceRecordResource|JsonResponse
    {
        try {
            return new FinanceRecordResource($this->finance->postJournalEntry(
                tenantId: $tenant,
                id: $journalEntry,
                postedBy: $request->integer('posted_by') ?: null,
            ));
        } catch (FinanceIntegrityException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (FinanceRecordNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }
}
