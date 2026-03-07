<?php

namespace App\Modules\User\Services\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

interface UserServiceInterface
{
    public function getAllUsers(array $filters): LengthAwarePaginator;
    public function getUserById(int $id);
    public function createUser(array $data);
    public function updateUser(int $id, array $data);
    public function deleteUser(int $id): bool;
}
