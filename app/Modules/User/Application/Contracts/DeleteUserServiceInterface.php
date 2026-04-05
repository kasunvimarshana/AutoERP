<?php
declare(strict_types=1);
namespace Modules\User\Application\Contracts;

interface DeleteUserServiceInterface
{
    public function execute(int $id): bool;
}
