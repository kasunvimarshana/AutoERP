<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Returns\Application\Contracts\ExpireReturnAuthorizationServiceInterface;
use Modules\Returns\Domain\Entities\ReturnAuthorization;
use Modules\Returns\Domain\Events\ReturnAuthorizationExpired;
use Modules\Returns\Domain\Exceptions\ReturnAuthorizationNotFoundException;
use Modules\Returns\Domain\RepositoryInterfaces\ReturnAuthorizationRepositoryInterface;

class ExpireReturnAuthorizationService extends BaseService implements ExpireReturnAuthorizationServiceInterface
{
    public function __construct(private readonly ReturnAuthorizationRepositoryInterface $authorizationRepository)
    {
        parent::__construct($authorizationRepository);
    }

    protected function handle(array $data): ReturnAuthorization
    {
        $id            = $data['id'];
        $authorization = $this->authorizationRepository->find($id);

        if (! $authorization) {
            throw new ReturnAuthorizationNotFoundException($id);
        }

        $authorization->expire();

        $saved = $this->authorizationRepository->save($authorization);
        $this->addEvent(new ReturnAuthorizationExpired($saved));

        return $saved;
    }
}
