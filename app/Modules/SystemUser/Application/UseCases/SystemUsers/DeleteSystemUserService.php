<?php

declare(strict_types=1);

namespace Modules\SystemUser\Application\UseCases\SystemUsers;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\SystemUser\Application\Contracts\UseCases\SystemUsers\DeleteSystemUserServiceInterface;
use Modules\SystemUser\Application\Repositories\SystemUserRepositoryInterface;
use Modules\SystemUser\Domain\Constants\SystemUserErrorCode;
use Throwable;

final class DeleteSystemUserService implements DeleteSystemUserServiceInterface
{
    public function __construct(private readonly SystemUserRepositoryInterface $systemUsers)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if ($this->systemUsers->findById($id) === null) {
                return Result::failure(new Error(SystemUserErrorCode::NOT_FOUND, 'System user not found.'));
            }

            return Result::success($this->systemUsers->delete($id));
        } catch (Throwable $exception) {
            return Result::failure(new Error(SystemUserErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
