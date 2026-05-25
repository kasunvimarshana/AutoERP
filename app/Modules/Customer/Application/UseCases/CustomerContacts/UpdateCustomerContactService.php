<?php

declare(strict_types=1);

namespace Modules\Customer\Application\UseCases\CustomerContacts;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Customer\Application\Contracts\UseCases\CustomerContacts\UpdateCustomerContactServiceInterface;
use Modules\Customer\Application\Repositories\CustomerContactRepositoryInterface;
use Modules\Customer\Domain\Constants\CustomerErrorCode;
use Throwable;

final class UpdateCustomerContactService implements UpdateCustomerContactServiceInterface
{
    public function __construct(private readonly CustomerContactRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(CustomerErrorCode::NOT_FOUND, 'CustomerContact not found.'));
            }

            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(CustomerErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}