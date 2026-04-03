<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Returns\Application\Contracts\DeleteReturnAuthorizationServiceInterface;
use Modules\Returns\Domain\Exceptions\ReturnAuthorizationNotFoundException;
use Modules\Returns\Domain\RepositoryInterfaces\ReturnAuthorizationRepositoryInterface;

class DeleteReturnAuthorizationService extends BaseService implements DeleteReturnAuthorizationServiceInterface
{
    public function __construct(private readonly ReturnAuthorizationRepositoryInterface $authorizationRepository)
    {
        parent::__construct($authorizationRepository);
    }

    protected function handle(array $data): bool
    {
        $id            = $data['id'];
        $authorization = $this->authorizationRepository->find($id);

        if (! $authorization) {
            throw new ReturnAuthorizationNotFoundException($id);
        }

        return $this->authorizationRepository->delete($id);
    }
}
