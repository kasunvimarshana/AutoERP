<?php

declare(strict_types=1);

namespace Modules\POS\Repositories;

use Modules\POS\Models\BusinessLocation;
use Illuminate\Database\Eloquent\Collection;

class BusinessLocationRepository
{
    public function findById(string $id): ?BusinessLocation
    {
        return BusinessLocation::find($id);
    }

    public function all(): Collection
    {
        return BusinessLocation::all();
    }

    public function active(): Collection
    {
        return BusinessLocation::active()->get();
    }

    public function create(array $data): BusinessLocation
    {
        return BusinessLocation::create($data);
    }

    public function update(BusinessLocation $location, array $data): BusinessLocation
    {
        $location->update($data);
        return $location->fresh();
    }

    public function delete(BusinessLocation $location): bool
    {
        return $location->delete();
    }
}
