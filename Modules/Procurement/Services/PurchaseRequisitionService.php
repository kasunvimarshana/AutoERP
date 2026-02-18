<?php

declare(strict_types=1);

namespace Modules\Procurement\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Services\BaseService;
use Modules\Procurement\Entities\PurchaseRequisition;
use Modules\Procurement\Repositories\Contracts\PurchaseRequisitionLineRepositoryInterface;
use Modules\Procurement\Repositories\Contracts\PurchaseRequisitionRepositoryInterface;

/**
 * Purchase Requisition Service
 *
 * Handles business logic for requisition workflow and approval.
 */
class PurchaseRequisitionService extends BaseService
{
    /**
     * PurchaseRequisitionService constructor.
     */
    public function __construct(
        protected PurchaseRequisitionRepositoryInterface $requisitionRepository,
        protected PurchaseRequisitionLineRepositoryInterface $requisitionLineRepository
    ) {}

    /**
     * Get paginated purchase requisitions.
     */
    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->requisitionRepository->paginate($perPage);
    }

    /**
     * Create a new purchase requisition.
     */
    public function create(array $data): PurchaseRequisition
    {
        return $this->executeInTransaction(function () use ($data) {
            // Generate requisition number if not provided
            if (empty($data['requisition_number'])) {
                $data['requisition_number'] = $this->generateRequisitionNumber();
            }

            // Set default status if not provided
            if (empty($data['status'])) {
                $data['status'] = 'draft';
            }

            // Set default approval status if not provided
            if (empty($data['approval_status'])) {
                $data['approval_status'] = 'pending';
            }

            // Initialize amounts if not provided
            $data['total_amount'] = $data['total_amount'] ?? 0;

            $requisition = $this->requisitionRepository->create($data);

            $this->logInfo('Purchase requisition created', [
                'requisition_id' => $requisition->id,
                'requisition_number' => $requisition->requisition_number,
            ]);

            return $requisition;
        });
    }

    /**
     * Create a requisition with lines.
     */
    public function createWithLines(array $requisitionData, array $lines): PurchaseRequisition
    {
        return $this->executeInTransaction(function () use ($requisitionData, $lines) {
            $requisition = $this->create($requisitionData);

            // Create requisition lines
            foreach ($lines as $lineData) {
                $lineData['purchase_requisition_id'] = $requisition->id;
                $this->requisitionLineRepository->create($lineData);
            }

            // Recalculate totals
            $requisition->calculateTotals();

            return $requisition->fresh('lines');
        });
    }

    /**
     * Update an existing purchase requisition.
     */
    public function update(int $id, array $data): PurchaseRequisition
    {
        return $this->executeInTransaction(function () use ($id, $data) {
            $requisition = $this->requisitionRepository->update($id, $data);

            $this->logInfo('Purchase requisition updated', [
                'requisition_id' => $requisition->id,
            ]);

            return $requisition;
        });
    }

    /**
     * Delete a purchase requisition.
     */
    public function delete(int $id): bool
    {
        return $this->executeInTransaction(function () use ($id) {
            // Delete associated lines first
            $this->requisitionLineRepository->deleteByRequisition($id);

            $result = $this->requisitionRepository->delete($id);

            if ($result) {
                $this->logInfo('Purchase requisition deleted', [
                    'requisition_id' => $id,
                ]);
            }

            return $result;
        });
    }

    /**
     * Find purchase requisition by ID.
     */
    public function findById(int $id, bool $withLines = false): ?PurchaseRequisition
    {
        if ($withLines) {
            return $this->requisitionRepository->findWithLines($id);
        }

        return $this->requisitionRepository->findById($id);
    }

    /**
     * Approve a purchase requisition.
     */
    public function approve(int $id, int $approverId): PurchaseRequisition
    {
        return $this->executeInTransaction(function () use ($id, $approverId) {
            $requisition = $this->requisitionRepository->findById($id);

            if (! $requisition) {
                throw new \Exception("Purchase requisition with ID {$id} not found.");
            }

            if ($requisition->approval_status === 'approved') {
                throw new \Exception('Purchase requisition is already approved.');
            }

            $requisition->approve($approverId);

            $this->logInfo('Purchase requisition approved', [
                'requisition_id' => $requisition->id,
                'requisition_number' => $requisition->requisition_number,
                'approver_id' => $approverId,
            ]);

            return $requisition;
        });
    }

    /**
     * Reject a purchase requisition.
     */
    public function reject(int $id, int $approverId, string $reason): PurchaseRequisition
    {
        return $this->executeInTransaction(function () use ($id, $approverId, $reason) {
            $requisition = $this->requisitionRepository->findById($id);

            if (! $requisition) {
                throw new \Exception("Purchase requisition with ID {$id} not found.");
            }

            if ($requisition->approval_status === 'rejected') {
                throw new \Exception('Purchase requisition is already rejected.');
            }

            $requisition->reject($approverId, $reason);

            $this->logInfo('Purchase requisition rejected', [
                'requisition_id' => $requisition->id,
                'requisition_number' => $requisition->requisition_number,
                'approver_id' => $approverId,
                'reason' => $reason,
            ]);

            return $requisition;
        });
    }

    /**
     * Calculate requisition totals.
     */
    public function calculateTotals(int $id): PurchaseRequisition
    {
        return $this->executeInTransaction(function () use ($id) {
            $requisition = $this->requisitionRepository->findById($id);

            if (! $requisition) {
                throw new \Exception("Purchase requisition with ID {$id} not found.");
            }

            $requisition->calculateTotals();

            return $requisition->fresh();
        });
    }

    /**
     * Get requisitions by requester.
     */
    public function getRequisitionsByRequester(int $requesterId): Collection
    {
        return $this->requisitionRepository->getRequisitionsByRequester($requesterId);
    }

    /**
     * Get requisitions by status.
     */
    public function getRequisitionsByStatus(string $status): Collection
    {
        return $this->requisitionRepository->getRequisitionsByStatus($status);
    }

    /**
     * Get requisitions by approval status.
     */
    public function getRequisitionsByApprovalStatus(string $approvalStatus): Collection
    {
        return $this->requisitionRepository->getRequisitionsByApprovalStatus($approvalStatus);
    }

    /**
     * Search purchase requisitions.
     */
    public function search(string $query): Collection
    {
        return $this->requisitionRepository->search($query);
    }

    /**
     * Generate a unique requisition number.
     */
    protected function generateRequisitionNumber(): string
    {
        $prefix = 'PR';
        $year = date('Y');

        // Get the last requisition number for this year
        $lastRequisition = $this->requisitionRepository
            ->getModel()
            ->where('requisition_number', 'LIKE', "{$prefix}-{$year}-%")
            ->orderBy('requisition_number', 'desc')
            ->first();

        if ($lastRequisition) {
            // Extract the sequence number and increment
            $parts = explode('-', $lastRequisition->requisition_number);
            $sequence = (int) end($parts) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s-%s-%05d', $prefix, $year, $sequence);
    }
}
