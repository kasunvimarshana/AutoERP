<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Accounting\Enums\FiscalPeriodStatus;
use Modules\Accounting\Events\FiscalPeriodClosed;
use Modules\Accounting\Events\FiscalPeriodCreated;
use Modules\Accounting\Events\FiscalPeriodReopened;
use Modules\Accounting\Http\Requests\StoreFiscalPeriodRequest;
use Modules\Accounting\Http\Requests\UpdateFiscalPeriodRequest;
use Modules\Accounting\Http\Resources\FiscalPeriodResource;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Repositories\FiscalPeriodRepository;
use Modules\Core\Helpers\TransactionHelper;
use Modules\Core\Http\Responses\ApiResponse;

class FiscalPeriodController extends Controller
{
    public function __construct(
        private FiscalPeriodRepository $fiscalPeriodRepository
    ) {}

    /**
     * Display a listing of fiscal periods
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', FiscalPeriod::class);

        $query = FiscalPeriod::query()->with(['fiscalYear', 'organization']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        if ($request->has('fiscal_year_id')) {
            $query->where('fiscal_year_id', $request->fiscal_year_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $periods = $query->orderBy('start_date')->paginate($perPage);

        return ApiResponse::paginated(
            $periods->setCollection(
                $periods->getCollection()->map(fn ($period) => new FiscalPeriodResource($period))
            ),
            'Fiscal periods retrieved successfully'
        );
    }

    /**
     * Store a newly created fiscal period
     */
    public function store(StoreFiscalPeriodRequest $request): JsonResponse
    {
        $data = $request->validated();

        $data['tenant_id'] = $request->user()->currentTenant()->id;
        $data['organization_id'] = $data['organization_id'] ?? $request->user()->currentOrganization()->id;
        $data['status'] = $data['status'] ?? FiscalPeriodStatus::Open;

        $period = TransactionHelper::execute(function () use ($data) {
            $period = $this->fiscalPeriodRepository->create($data);
            event(new FiscalPeriodCreated($period));

            return $period;
        });

        return ApiResponse::success(
            new FiscalPeriodResource($period->load('fiscalYear')),
            'Fiscal period created successfully',
            201
        );
    }

    /**
     * Display the specified fiscal period
     */
    public function show(Request $request, FiscalPeriod $fiscalPeriod): JsonResponse
    {
        $this->authorize('view', $fiscalPeriod);

        $fiscalPeriod->load(['fiscalYear', 'organization']);

        return ApiResponse::success(
            new FiscalPeriodResource($fiscalPeriod),
            'Fiscal period retrieved successfully'
        );
    }

    /**
     * Update the specified fiscal period
     */
    public function update(UpdateFiscalPeriodRequest $request, FiscalPeriod $fiscalPeriod): JsonResponse
    {
        $data = $request->validated();

        $period = TransactionHelper::execute(function () use ($fiscalPeriod, $data) {
            return $this->fiscalPeriodRepository->update($fiscalPeriod->id, $data);
        });

        return ApiResponse::success(
            new FiscalPeriodResource($period->load('fiscalYear')),
            'Fiscal period updated successfully'
        );
    }

    /**
     * Remove the specified fiscal period
     */
    public function destroy(Request $request, FiscalPeriod $fiscalPeriod): JsonResponse
    {
        $this->authorize('delete', $fiscalPeriod);

        if ($fiscalPeriod->journalEntries()->exists()) {
            return ApiResponse::error(
                'Cannot delete fiscal period with journal entries',
                400
            );
        }

        TransactionHelper::execute(function () use ($fiscalPeriod) {
            $this->fiscalPeriodRepository->delete($fiscalPeriod->id);
        });

        return ApiResponse::success(
            null,
            'Fiscal period deleted successfully'
        );
    }

    /**
     * Close a fiscal period
     */
    public function close(Request $request, FiscalPeriod $fiscalPeriod): JsonResponse
    {
        $this->authorize('close', $fiscalPeriod);

        if (! $fiscalPeriod->isOpen()) {
            return ApiResponse::error(
                'Only open fiscal periods can be closed',
                400
            );
        }

        $period = TransactionHelper::execute(function () use ($fiscalPeriod, $request) {
            $updated = $this->fiscalPeriodRepository->update($fiscalPeriod->id, [
                'status' => FiscalPeriodStatus::Closed,
                'closed_at' => now(),
                'closed_by' => $request->user()->id,
            ]);

            event(new FiscalPeriodClosed($updated));

            return $updated;
        });

        return ApiResponse::success(
            new FiscalPeriodResource($period->load('fiscalYear')),
            'Fiscal period closed successfully'
        );
    }

    /**
     * Reopen a fiscal period
     */
    public function reopen(Request $request, FiscalPeriod $fiscalPeriod): JsonResponse
    {
        $this->authorize('reopen', $fiscalPeriod);

        if (! $fiscalPeriod->isClosed()) {
            return ApiResponse::error(
                'Only closed fiscal periods can be reopened',
                400
            );
        }

        if ($fiscalPeriod->isLocked()) {
            return ApiResponse::error(
                'Locked fiscal periods cannot be reopened',
                400
            );
        }

        $period = TransactionHelper::execute(function () use ($fiscalPeriod) {
            $updated = $this->fiscalPeriodRepository->update($fiscalPeriod->id, [
                'status' => FiscalPeriodStatus::Open,
                'closed_at' => null,
                'closed_by' => null,
            ]);

            event(new FiscalPeriodReopened($updated));

            return $updated;
        });

        return ApiResponse::success(
            new FiscalPeriodResource($period->load('fiscalYear')),
            'Fiscal period reopened successfully'
        );
    }

    /**
     * Lock a fiscal period
     */
    public function lock(Request $request, FiscalPeriod $fiscalPeriod): JsonResponse
    {
        $this->authorize('lock', $fiscalPeriod);

        if (! $fiscalPeriod->isClosed()) {
            return ApiResponse::error(
                'Only closed fiscal periods can be locked',
                400
            );
        }

        if ($fiscalPeriod->isLocked()) {
            return ApiResponse::error(
                'Fiscal period is already locked',
                400
            );
        }

        $period = TransactionHelper::execute(function () use ($fiscalPeriod, $request) {
            return $this->fiscalPeriodRepository->update($fiscalPeriod->id, [
                'status' => FiscalPeriodStatus::Locked,
                'locked_at' => now(),
                'locked_by' => $request->user()->id,
            ]);
        });

        return ApiResponse::success(
            new FiscalPeriodResource($period->load('fiscalYear')),
            'Fiscal period locked successfully'
        );
    }
}
