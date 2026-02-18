<?php

declare(strict_types=1);

namespace Modules\Core\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Base Repository Interface
 *
 * Defines the contract for all repository implementations following the Repository Pattern
 * from Domain-Driven Design. This ensures consistency across all data access layers and
 * enables the Dependency Inversion Principle from SOLID.
 *
 * This interface abstracts data access, allowing:
 * - Easy testing with mock implementations
 * - Swapping data sources without changing business logic
 * - Consistent API across all repositories
 *
 * @see https://martinfowler.com/eaaCatalog/repository.html
 */
interface BaseRepositoryInterface
{
    /**
     * Find a model by its primary key.
     */
    public function findById(int|string $id): ?Model;

    /**
     * Find a model by specific criteria.
     *
     * @param  array<string, mixed>  $criteria
     */
    public function findBy(array $criteria): ?Model;

    /**
     * Get all models.
     *
     * @param  array<string>  $columns
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Get paginated models.
     *
     * @param  array<string>  $columns
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * Create a new model.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model;

    /**
     * Update a model.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int|string $id, array $data): Model;

    /**
     * Delete a model.
     */
    public function delete(int|string $id): bool;

    /**
     * Find models matching criteria.
     *
     * @param  array<string, mixed>  $criteria
     * @param  array<string>  $columns
     */
    public function findWhere(array $criteria, array $columns = ['*']): Collection;

    /**
     * Find models matching criteria with pagination.
     *
     * @param  array<string, mixed>  $criteria
     * @param  array<string>  $columns
     */
    public function findWherePaginated(array $criteria, int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;
}
