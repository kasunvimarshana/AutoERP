<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AccountingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    public function __construct(
        private readonly AccountingService $accountingService
    ) {}

    public function indexAccounts(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);
        $filters = $request->only(['type', 'is_active']);

        return response()->json($this->accountingService->paginateAccounts($tenantId, $filters, $perPage));
    }

    public function storeAccount(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('accounting.create'), 403);

        $data = $request->validate([
            'organization_id' => ['sometimes', 'nullable', 'uuid', 'exists:organizations,id'],
            'parent_id' => ['sometimes', 'nullable', 'uuid', 'exists:chart_of_accounts,id'],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:asset,liability,equity,revenue,expense'],
            'subtype' => ['sometimes', 'nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'description' => ['sometimes', 'nullable', 'string'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ]);

        $data['tenant_id'] = $request->user()->tenant_id;

        return response()->json($this->accountingService->createAccount($data), 201);
    }

    public function updateAccount(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('accounting.update'), 403);

        $data = $request->validate([
            'code' => ['sometimes', 'string', 'max:50'],
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', 'in:asset,liability,equity,revenue,expense'],
            'subtype' => ['sometimes', 'nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'description' => ['sometimes', 'nullable', 'string'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ]);

        return response()->json($this->accountingService->updateAccount($id, $data));
    }

    public function indexPeriods(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);

        return response()->json($this->accountingService->paginatePeriods($tenantId, $perPage));
    }

    public function storePeriod(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('accounting.create'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'fiscal_year' => ['required', 'integer', 'min:1900', 'max:2999'],
            'status' => ['sometimes', 'string', 'in:open,closed,locked'],
        ]);

        $data['tenant_id'] = $request->user()->tenant_id;

        return response()->json($this->accountingService->createPeriod($data), 201);
    }

    public function closePeriod(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('accounting.update'), 403);

        return response()->json($this->accountingService->closePeriod($id));
    }

    public function indexJournalEntries(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);
        $filters = $request->only(['status', 'date_from', 'date_to']);

        return response()->json($this->accountingService->paginateJournalEntries($tenantId, $filters, $perPage));
    }

    public function storeJournalEntry(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('accounting.create'), 403);

        $data = $request->validate([
            'organization_id' => ['sometimes', 'nullable', 'uuid', 'exists:organizations,id'],
            'accounting_period_id' => ['sometimes', 'nullable', 'uuid', 'exists:accounting_periods,id'],
            'reference_number' => ['required', 'string', 'max:100'],
            'date' => ['required', 'date'],
            'description' => ['sometimes', 'nullable', 'string'],
            'reference_type' => ['sometimes', 'nullable', 'string', 'max:100'],
            'reference_id' => ['sometimes', 'nullable', 'string', 'max:100'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'uuid', 'exists:chart_of_accounts,id'],
            'lines.*.description' => ['sometimes', 'nullable', 'string'],
            'lines.*.debit' => ['sometimes', 'numeric', 'min:0'],
            'lines.*.credit' => ['sometimes', 'numeric', 'min:0'],
            'lines.*.currency' => ['sometimes', 'string', 'size:3'],
        ]);

        $data['tenant_id'] = $request->user()->tenant_id;

        return response()->json($this->accountingService->createJournalEntry($data), 201);
    }

    public function postJournalEntry(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('accounting.update'), 403);

        return response()->json($this->accountingService->postJournalEntry($id, $request->user()->id));
    }
}
