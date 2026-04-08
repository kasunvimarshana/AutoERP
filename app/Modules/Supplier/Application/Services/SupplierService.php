<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Supplier\Application\Contracts\SupplierServiceInterface;
use Modules\Supplier\Domain\Entities\Supplier;
use Modules\Supplier\Domain\Events\SupplierCreated;
use Modules\Supplier\Domain\Events\SupplierUpdated;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface;

class SupplierService implements SupplierServiceInterface
{
    public function __construct(
        private readonly SupplierRepositoryInterface $supplierRepository,
    ) {}

    public function getSupplier(string $tenantId, string $id): Supplier
    {
        $supplier = $this->supplierRepository->findById($tenantId, $id);
        if ($supplier === null) {
            throw new NotFoundException('Supplier', $id);
        }
        return $supplier;
    }

    public function getAllSuppliers(string $tenantId): array
    {
        return $this->supplierRepository->findAll($tenantId);
    }

    public function createSupplier(string $tenantId, array $data): Supplier
    {
        return DB::transaction(function () use ($tenantId, $data): Supplier {
            $now = now();
            $supplier = new Supplier(
                id: (string) Str::uuid(),
                tenantId: $tenantId,
                name: $data['name'],
                code: $data['code'],
                email: $data['email'] ?? null,
                phone: $data['phone'] ?? null,
                address: $data['address'] ?? null,
                taxNumber: $data['tax_number'] ?? null,
                currency: $data['currency'] ?? 'USD',
                creditLimit: (float) ($data['credit_limit'] ?? 0.0),
                isActive: (bool) ($data['is_active'] ?? true),
                createdAt: $now,
                updatedAt: $now,
            );
            $this->supplierRepository->save($supplier);
            Event::dispatch(new SupplierCreated($supplier));
            return $supplier;
        });
    }

    public function updateSupplier(string $tenantId, string $id, array $data): Supplier
    {
        return DB::transaction(function () use ($tenantId, $id, $data): Supplier {
            $existing = $this->getSupplier($tenantId, $id);
            $supplier = new Supplier(
                id: $existing->id,
                tenantId: $existing->tenantId,
                name: $data['name'] ?? $existing->name,
                code: $data['code'] ?? $existing->code,
                email: array_key_exists('email', $data) ? $data['email'] : $existing->email,
                phone: array_key_exists('phone', $data) ? $data['phone'] : $existing->phone,
                address: array_key_exists('address', $data) ? $data['address'] : $existing->address,
                taxNumber: array_key_exists('tax_number', $data) ? $data['tax_number'] : $existing->taxNumber,
                currency: $data['currency'] ?? $existing->currency,
                creditLimit: isset($data['credit_limit']) ? (float) $data['credit_limit'] : $existing->creditLimit,
                isActive: isset($data['is_active']) ? (bool) $data['is_active'] : $existing->isActive,
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );
            $this->supplierRepository->save($supplier);
            Event::dispatch(new SupplierUpdated($supplier));
            return $supplier;
        });
    }

    public function deleteSupplier(string $tenantId, string $id): void
    {
        DB::transaction(function () use ($tenantId, $id): void {
            $this->getSupplier($tenantId, $id);
            $this->supplierRepository->delete($tenantId, $id);
        });
    }
}
