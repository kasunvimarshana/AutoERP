<?php

namespace App\Services;

use App\DTOs\WarehouseDTO;
use App\Models\Warehouse;
use App\Repositories\Interfaces\WarehouseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class WarehouseService
{
    public function __construct(
        private readonly WarehouseRepositoryInterface $warehouseRepository,
    ) {}

    public function list(array $filters = [], ?int $perPage = null): LengthAwarePaginator|Collection
    {
        return $this->warehouseRepository->paginate($filters, $perPage);
    }

    public function findById(int $id): Warehouse
    {
        return $this->warehouseRepository->findById($id)
            ?? throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Warehouse not found.');
    }

    public function create(array $data): Warehouse
    {
        return DB::transaction(function () use ($data) {
            $dto = WarehouseDTO::fromArray($data);

            return $this->warehouseRepository->create($dto->toArray());
        });
    }

    public function update(int $id, array $data): Warehouse
    {
        return DB::transaction(function () use ($id, $data) {
            $warehouse = $this->findById($id);

            return $this->warehouseRepository->update($warehouse, $data);
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $warehouse = $this->findById($id);
            $this->warehouseRepository->delete($warehouse);
        });
    }
}
