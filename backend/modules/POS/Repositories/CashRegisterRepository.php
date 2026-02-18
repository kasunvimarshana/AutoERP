<?php

declare(strict_types=1);

namespace Modules\POS\Repositories;

use Modules\POS\Models\CashRegister;
use Illuminate\Database\Eloquent\Collection;

class CashRegisterRepository
{
    public function findById(string $id): ?CashRegister
    {
        return CashRegister::with(['location'])->find($id);
    }

    public function all(): Collection
    {
        return CashRegister::with(['location'])->get();
    }

    public function byLocation(string $locationId): Collection
    {
        return CashRegister::where('location_id', $locationId)->get();
    }

    public function openRegisters(): Collection
    {
        return CashRegister::open()->with(['location'])->get();
    }

    public function create(array $data): CashRegister
    {
        return CashRegister::create($data);
    }

    public function update(CashRegister $cashRegister, array $data): CashRegister
    {
        $cashRegister->update($data);
        return $cashRegister->fresh();
    }

    public function delete(CashRegister $cashRegister): bool
    {
        return $cashRegister->delete();
    }
}
