<?php

namespace Modules\Returns\Application\Services;

use Modules\Returns\Application\Contracts\CancelReturnAuthorizationServiceInterface;
use Modules\Returns\Domain\Entities\ReturnAuthorization;
use Modules\Returns\Domain\RepositoryInterfaces\ReturnAuthorizationRepositoryInterface;
use Modules\Returns\Domain\ValueObjects\RmaStatus;

class CancelReturnAuthorizationService implements CancelReturnAuthorizationServiceInterface
{
    public function __construct(
        private readonly ReturnAuthorizationRepositoryInterface $repository,
    ) {}

    public function execute(ReturnAuthorization $rma): ReturnAuthorization
    {
        return $this->repository->update($rma, [
            'status' => RmaStatus::CANCELLED,
        ]);
    }
}
