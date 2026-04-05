<?php
declare(strict_types=1);
namespace Modules\User\Application\Contracts;

use Modules\User\Application\DTOs\ChangePasswordData;

interface ChangePasswordServiceInterface
{
    public function execute(int $id, ChangePasswordData $data): bool;
}
