<?php

namespace App\Modules\Identity\Repositories;

use App\Core\BaseRepository;
use App\Modules\Identity\Models\User;

class UserRepository extends BaseRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findBy('email', $email);
    }

    public function getUsersByRole(string $role)
    {
        return $this->model->role($role)->get();
    }
}
