<?php
namespace App\Interfaces;

interface RepositoryInterface
{
    public function all(array $filters = [], array $relations = []);
    public function find(int $id, array $relations = []);
    public function create(array $data): mixed;
    public function update(int $id, array $data): mixed;
    public function delete(int $id): bool;
    public function paginate(int $perPage = 15, array $filters = [], array $relations = []);
}
