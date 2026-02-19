<?php

declare(strict_types=1);

namespace Modules\JobCard\Services;

use App\Core\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Modules\JobCard\Repositories\JobPartRepository;

/**
 * JobPart Service
 *
 * Contains business logic for JobPart operations
 */
class JobPartService extends BaseService
{
    /**
     * JobPartService constructor
     */
    public function __construct(JobPartRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Create a new part
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): mixed
    {
        // Check if we\'re already in a transaction (e.g., from orchestrator or test)
        $shouldManageTransaction = DB::transactionLevel() === 0;

        try {
            if ($shouldManageTransaction) {
                DB::beginTransaction();
            }

            if (! isset($data['status'])) {
                $data['status'] = 'pending';
            }

            if (! isset($data['total_price']) && isset($data['quantity'], $data['unit_price'])) {
                $data['total_price'] = $data['quantity'] * $data['unit_price'];
            }

            $part = parent::create($data);

            DB::commit();

            return $part;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Add part to job card
     *
     * @param  array<string, mixed>  $partData
     */
    public function addToJobCard(int $jobCardId, array $partData): mixed
    {
        // Check if we\'re already in a transaction (e.g., from orchestrator or test)
        $shouldManageTransaction = DB::transactionLevel() === 0;

        try {
            if ($shouldManageTransaction) {
                DB::beginTransaction();
            }

            $partData['job_card_id'] = $jobCardId;
            $part = $this->create($partData);

            DB::commit();

            return $part;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update part
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): mixed
    {
        // Check if we\'re already in a transaction (e.g., from orchestrator or test)
        $shouldManageTransaction = DB::transactionLevel() === 0;

        try {
            if ($shouldManageTransaction) {
                DB::beginTransaction();
            }

            $part = $this->repository->findOrFail($id);

            if (isset($data['quantity']) || isset($data['unit_price'])) {
                $quantity = $data['quantity'] ?? $part->quantity;
                $unitPrice = $data['unit_price'] ?? $part->unit_price;
                $data['total_price'] = $quantity * $unitPrice;
            }

            $part = parent::update($id, $data);

            DB::commit();

            return $part;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete part
     */
    public function delete(int $id): bool
    {
        // Check if we\'re already in a transaction (e.g., from orchestrator or test)
        $shouldManageTransaction = DB::transactionLevel() === 0;

        try {
            if ($shouldManageTransaction) {
                DB::beginTransaction();
            }

            $deleted = parent::delete($id);

            DB::commit();

            return $deleted;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get parts for job card
     */
    public function getForJobCard(int $jobCardId): mixed
    {
        return $this->repository->getForJobCard($jobCardId);
    }

    /**
     * Get total for job card
     */
    public function getTotalForJobCard(int $jobCardId): float
    {
        return $this->repository->getTotalForJobCard($jobCardId);
    }
}
