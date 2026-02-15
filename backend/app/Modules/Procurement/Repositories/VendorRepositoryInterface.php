<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Repositories;

use App\Core\Repositories\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface VendorRepositoryInterface extends RepositoryInterface
{
    /**
     * Find vendor by code
     */
    public function findByCode(string $code): mixed;

    /**
     * Find vendor by email
     */
    public function findByEmail(string $email): mixed;

    /**
     * Get active vendors
     */
    public function getActiveVendors(): Collection;

    /**
     * Get vendors by type
     */
    public function getByType(string $type): Collection;

    /**
     * Get vendors by status
     */
    public function getByStatus(string $status): Collection;

    /**
     * Get vendors by category
     */
    public function getByCategory(string $category): Collection;

    /**
     * Search vendors
     */
    public function search(string $query, array $filters = []): Collection;
}
