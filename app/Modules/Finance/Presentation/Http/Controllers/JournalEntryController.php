<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Controllers;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Application\Support\FinancialServiceSupport;
use Modules\Finance\Presentation\Http\Requests\ListJournalEntryRequest;
use Modules\Finance\Presentation\Http\Resources\JournalEntryResource;

final class JournalEntryController extends Controller
{
    public function __construct(private readonly FinancialServiceSupport $support) {}

    public function index(ListJournalEntryRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();
        $search = trim((string) ($filters['search'] ?? ''));
        $entries = DB::table('journal_entries')
            ->select(['id', 'entry_number', 'entry_type', 'source_module', 'source_type', 'source_reference', 'description', 'entry_date', 'status', 'total_debit', 'total_credit', 'created_at'])
            ->where('tenant_id', $this->support->tenantId())
            ->when(isset($filters['status']), fn (Builder $query): Builder => $query->where('status', (string) $filters['status']))
            ->when($search !== '', fn (Builder $query): Builder => $query->where(fn (Builder $q) => $q->where('entry_number', 'like', "%$search%")->orWhere('source_reference', 'like', "%$search%")))
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate(min((int) ($filters['per_page'] ?? 20), 200), ['*'], 'page', (int) ($filters['page'] ?? 1));

        return JournalEntryResource::collection($entries);
    }

    public function show(int $journalEntry): JournalEntryResource
    {
        $entry = DB::table('journal_entries')->where('tenant_id', $this->support->tenantId())->where('id', $journalEntry)->first();
        if ($entry === null) {
            abort(404);
        }
        $entry->lines = DB::table('journal_entry_lines')->where('tenant_id', $this->support->tenantId())->where('journal_entry_id', $journalEntry)->orderBy('line_number')->get();

        return new JournalEntryResource($entry);
    }
}
