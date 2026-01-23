<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use App\Core\Exceptions\ServiceException;
use App\Core\Services\BaseService;
use Illuminate\Validation\ValidationException;
use Modules\Inventory\Models\Supplier;
use Modules\Inventory\Repositories\SupplierRepository;

/**
 * Supplier Service
 *
 * Contains business logic for Supplier operations
 */
class SupplierService extends BaseService
{
    /**
     * SupplierService constructor
     */
    public function __construct(SupplierRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Create a new supplier
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     * @throws ServiceException
     */
    public function create(array $data): mixed
    {
        // Validate supplier code uniqueness
        if (isset($data['supplier_code']) && $this->repository->supplierCodeExists($data['supplier_code'])) {
            throw ValidationException::withMessages([
                'supplier_code' => ['The supplier code has already been taken.'],
            ]);
        }

        // Generate unique supplier code if not provided
        if (! isset($data['supplier_code'])) {
            $data['supplier_code'] = $this->generateUniqueSupplierCode();
        }

        return parent::create($data);
    }

    /**
     * Update supplier
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function update(int $id, array $data): mixed
    {
        // Validate supplier code uniqueness if provided
        if (isset($data['supplier_code']) && $this->repository->supplierCodeExists($data['supplier_code'], $id)) {
            throw ValidationException::withMessages([
                'supplier_code' => ['The supplier code has already been taken.'],
            ]);
        }

        return parent::update($id, $data);
    }

    /**
     * Get active suppliers
     */
    public function getActive(): mixed
    {
        return $this->repository->getActive();
    }

    /**
     * Search suppliers
     *
     * @param  array<string, mixed>  $filters
     */
    public function search(array $filters): mixed
    {
        return $this->repository->search($filters);
    }

    /**
     * Generate unique supplier code
     *
     * @throws ServiceException
     */
    private function generateUniqueSupplierCode(): string
    {
        $prefix = 'SUP';
        $attempts = 0;
        $maxAttempts = 100;

        do {
            $number = str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
            $code = $prefix.$number;
            $attempts++;

            if ($attempts >= $maxAttempts) {
                throw new ServiceException('Unable to generate unique supplier code');
            }
        } while ($this->repository->supplierCodeExists($code));

        return $code;
    }
}
