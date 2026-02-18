<?php

declare(strict_types=1);

namespace Modules\Procurement\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Services\BaseService;
use Modules\Procurement\Entities\Supplier;
use Modules\Procurement\Repositories\Contracts\SupplierRepositoryInterface;

/**
 * Supplier Service
 *
 * Handles business logic for supplier management, rating, and evaluation.
 */
class SupplierService extends BaseService
{
    /**
     * SupplierService constructor.
     */
    public function __construct(
        protected SupplierRepositoryInterface $supplierRepository
    ) {}

    /**
     * Get paginated suppliers.
     */
    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->supplierRepository->paginate($perPage);
    }

    /**
     * Create a new supplier.
     */
    public function create(array $data): Supplier
    {
        return $this->executeInTransaction(function () use ($data) {
            // Generate supplier code if not provided
            if (empty($data['code'])) {
                $data['code'] = $this->generateSupplierCode();
            }

            // Set default status if not provided
            if (empty($data['status'])) {
                $data['status'] = 'active';
            }

            // Set default rating if not provided
            if (empty($data['rating'])) {
                $data['rating'] = 0;
            }

            $supplier = $this->supplierRepository->create($data);

            $this->logInfo('Supplier created', [
                'supplier_id' => $supplier->id,
                'code' => $supplier->code,
            ]);

            return $supplier;
        });
    }

    /**
     * Update an existing supplier.
     */
    public function update(int $id, array $data): Supplier
    {
        return $this->executeInTransaction(function () use ($id, $data) {
            $supplier = $this->supplierRepository->update($id, $data);

            $this->logInfo('Supplier updated', [
                'supplier_id' => $supplier->id,
            ]);

            return $supplier;
        });
    }

    /**
     * Delete a supplier.
     */
    public function delete(int $id): bool
    {
        return $this->executeInTransaction(function () use ($id) {
            $result = $this->supplierRepository->delete($id);

            if ($result) {
                $this->logInfo('Supplier deleted', [
                    'supplier_id' => $id,
                ]);
            }

            return $result;
        });
    }

    /**
     * Find supplier by ID.
     */
    public function findById(int $id): ?Supplier
    {
        return $this->supplierRepository->findById($id);
    }

    /**
     * Find supplier by code.
     */
    public function findByCode(string $code): ?Supplier
    {
        return $this->supplierRepository->findByCode($code);
    }

    /**
     * Get suppliers by status.
     */
    public function getSuppliersByStatus(string $status): Collection
    {
        return $this->supplierRepository->getSuppliersByStatus($status);
    }

    /**
     * Get suppliers by rating.
     */
    public function getSuppliersByRating(float $minRating): Collection
    {
        return $this->supplierRepository->getSuppliersByRating($minRating);
    }

    /**
     * Search suppliers.
     */
    public function search(string $query): Collection
    {
        return $this->supplierRepository->search($query);
    }

    /**
     * Rate a supplier.
     */
    public function rateSupplier(int $id, float $rating): Supplier
    {
        return $this->executeInTransaction(function () use ($id, $rating) {
            $supplier = $this->supplierRepository->findById($id);

            if (! $supplier) {
                throw new \Exception("Supplier with ID {$id} not found.");
            }

            $supplier->updateRating($rating);

            $this->logInfo('Supplier rated', [
                'supplier_id' => $supplier->id,
                'rating' => $rating,
            ]);

            event(new \Modules\Procurement\Events\SupplierRated($supplier));

            return $supplier;
        });
    }

    /**
     * Evaluate supplier performance.
     */
    public function evaluateSupplierPerformance(int $id): array
    {
        $supplier = $this->supplierRepository->findById($id);

        if (! $supplier) {
            throw new \Exception("Supplier with ID {$id} not found.");
        }

        // Calculate performance metrics
        $totalOrders = $supplier->purchaseOrders()->count();
        $completedOrders = $supplier->purchaseOrders()->where('status', 'closed')->count();
        $onTimeDeliveries = $supplier->purchaseOrders()
            ->whereHas('goodsReceipts', function ($query) {
                $query->where('status', 'confirmed');
            })
            ->count();

        $completionRate = $totalOrders > 0 ? ($completedOrders / $totalOrders) * 100 : 0;
        $onTimeRate = $totalOrders > 0 ? ($onTimeDeliveries / $totalOrders) * 100 : 0;

        return [
            'supplier_id' => $supplier->id,
            'supplier_name' => $supplier->name,
            'total_orders' => $totalOrders,
            'completed_orders' => $completedOrders,
            'on_time_deliveries' => $onTimeDeliveries,
            'completion_rate' => round($completionRate, 2),
            'on_time_rate' => round($onTimeRate, 2),
            'current_rating' => $supplier->rating,
        ];
    }

    /**
     * Generate a unique supplier code.
     */
    protected function generateSupplierCode(): string
    {
        $prefix = 'SUP';

        // Get the last supplier code
        $lastSupplier = $this->supplierRepository
            ->getModel()
            ->where('code', 'LIKE', "{$prefix}%")
            ->orderBy('code', 'desc')
            ->first();

        if ($lastSupplier) {
            // Extract the sequence number and increment
            $parts = explode('-', $lastSupplier->code);
            $sequence = (int) end($parts) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s-%05d', $prefix, $sequence);
    }
}
