<?php

declare(strict_types=1);

namespace Modules\SystemUser\Application\UseCases\SystemUsers;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\SystemUser\Application\Contracts\UseCases\SystemUsers\ListSystemUsersServiceInterface;
use Modules\SystemUser\Application\Repositories\SystemUserRepositoryInterface;
use Modules\SystemUser\Domain\Constants\SystemUserErrorCode;
use Throwable;

final class ListSystemUsersService implements ListSystemUsersServiceInterface
{
    public function __construct(private readonly SystemUserRepositoryInterface $systemUsers)
    {
    }

    public function execute(array $filters): Result
    {
        try {
            $result = $this->systemUsers->pageByFilters(
                isset($filters['tenant_id']) ? (int) $filters['tenant_id'] : null,
                isset($filters['organization_unit_id']) ? (int) $filters['organization_unit_id'] : null,
                isset($filters['user_id']) ? (int) $filters['user_id'] : null,
                isset($filters['status']) ? trim((string) $filters['status']) : null,
                isset($filters['search']) ? trim((string) $filters['search']) : null,
                max(
                    1,
                    (int) ($filters['per_page'] ?? (int) config('system-user.pagination.default_per_page', 20)),
                ),
                max(1, (int) ($filters['page'] ?? 1)),
            );

            return Result::success($result);
        } catch (Throwable $exception) {
            return Result::failure(new Error(SystemUserErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
