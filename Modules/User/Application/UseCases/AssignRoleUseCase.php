<?php
namespace Modules\User\Application\UseCases;
use Modules\User\Domain\Contracts\UserRepositoryInterface;
class AssignRoleUseCase
{
    public function __construct(private UserRepositoryInterface $repo) {}
    public function execute(array $data): void
    {
        $this->repo->assignRole($data['user_id'], $data['role_id']);
    }
}
