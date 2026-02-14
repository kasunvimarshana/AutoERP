<?php

namespace App\Modules\Vendor\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Vendor\Models\Vendor;
use Illuminate\Database\Eloquent\Collection;

class VendorRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    protected function model(): string
    {
        return Vendor::class;
    }

    /**
     * Get active vendors
     */
    public function getActive(): Collection
    {
        return $this->model->where('is_active', true)->get();
    }

    /**
     * Find vendor by email
     */
    public function findByEmail(string $email): ?Vendor
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Get vendors by type
     */
    public function getByType(string $type): Collection
    {
        return $this->model->where('vendor_type', $type)->get();
    }
}
