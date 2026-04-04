<?php

namespace Modules\Returns\Application\Services;

use Modules\Returns\Application\Contracts\ExpireReturnAuthorizationServiceInterface;
use Modules\Returns\Domain\Entities\ReturnAuthorization;
use Modules\Returns\Domain\RepositoryInterfaces\ReturnAuthorizationRepositoryInterface;
use Modules\Returns\Domain\ValueObjects\RmaStatus;

class ExpireReturnAuthorizationService implements ExpireReturnAuthorizationServiceInterface
{
    public function __construct(
        private readonly ReturnAuthorizationRepositoryInterface $repository,
    ) {}

    public function execute(ReturnAuthorization $rma): ReturnAuthorization
    {
        return $this->repository->update($rma, [
            'status' => RmaStatus::EXPIRED,
        ]);
    }
}
