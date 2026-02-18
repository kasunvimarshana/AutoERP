<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Events\FiscalYearClosed;
use App\Modules\Finance\Models\FiscalYear;
use App\Modules\Finance\Repositories\FiscalYearRepository;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Fiscal Year Service
 *
 * Business logic for fiscal year management operations.
 */
class FiscalYearService extends BaseService
{
    public function __construct(
        protected FiscalYearRepository $repository
    ) {}

    /**
     * Get paginated list of fiscal years
     */
    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    /**
     * Create a new fiscal year
     */
    public function create(array $data): FiscalYear
    {
        return $this->transaction(function () use ($data) {
            $data['tenant_id'] = Auth::user()->tenant_id;
            $data['is_closed'] = false;

            $fiscalYear = $this->repository->create($data);

            $this->logInfo('Fiscal year created', ['fiscal_year_id' => $fiscalYear->id]);

            return $fiscalYear;
        });
    }

    /**
     * Get fiscal year by ID
     */
    public function find(int $id): ?FiscalYear
    {
        return $this->repository->findOrFail($id);
    }

    /**
     * Update fiscal year
     *
     * @throws \Exception if fiscal year is closed
     */
    public function update(int $id, array $data): FiscalYear
    {
        return $this->transaction(function () use ($id, $data) {
            $fiscalYear = $this->repository->findOrFail($id);

            if ($fiscalYear->is_closed) {
                throw new \Exception('Cannot update closed fiscal year');
            }

            $this->repository->update($id, $data);
            $fiscalYear->refresh();

            $this->logInfo('Fiscal year updated', ['fiscal_year_id' => $id]);

            return $fiscalYear;
        });
    }

    /**
     * Delete fiscal year
     *
     * @throws \Exception if fiscal year is closed
     */
    public function delete(int $id): void
    {
        $this->transaction(function () use ($id) {
            $fiscalYear = $this->repository->findOrFail($id);

            if ($fiscalYear->is_closed) {
                throw new \Exception('Cannot delete closed fiscal year');
            }

            $this->repository->delete($id);

            $this->logInfo('Fiscal year deleted', ['fiscal_year_id' => $id]);
        });
    }

    /**
     * Close fiscal year
     *
     * @throws \Exception if fiscal year is already closed
     */
    public function close(int $id): FiscalYear
    {
        return $this->transaction(function () use ($id) {
            $fiscalYear = $this->repository->findOrFail($id);

            if ($fiscalYear->is_closed) {
                throw new \Exception('Fiscal year is already closed');
            }

            $this->repository->update($id, ['is_closed' => true]);
            $fiscalYear->refresh();

            event(new FiscalYearClosed($fiscalYear));

            $this->logInfo('Fiscal year closed', ['fiscal_year_id' => $id]);

            return $fiscalYear;
        });
    }

    /**
     * Get all open fiscal years
     */
    public function getOpenFiscalYears(): Collection
    {
        return $this->repository->getOpenFiscalYears();
    }

    /**
     * Get all closed fiscal years
     */
    public function getClosedFiscalYears(): Collection
    {
        return $this->repository->getClosedFiscalYears();
    }

    /**
     * Get current fiscal year
     */
    public function getCurrentFiscalYear(): ?FiscalYear
    {
        return $this->repository->getCurrentFiscalYear();
    }
}
