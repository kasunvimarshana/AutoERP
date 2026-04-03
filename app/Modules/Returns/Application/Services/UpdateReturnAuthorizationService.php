<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Returns\Application\Contracts\UpdateReturnAuthorizationServiceInterface;
use Modules\Returns\Application\DTOs\UpdateReturnAuthorizationData;
use Modules\Returns\Domain\Entities\ReturnAuthorization;
use Modules\Returns\Domain\Exceptions\ReturnAuthorizationNotFoundException;
use Modules\Returns\Domain\RepositoryInterfaces\ReturnAuthorizationRepositoryInterface;

class UpdateReturnAuthorizationService extends BaseService implements UpdateReturnAuthorizationServiceInterface
{
    public function __construct(private readonly ReturnAuthorizationRepositoryInterface $authorizationRepository)
    {
        parent::__construct($authorizationRepository);
    }

    protected function handle(array $data): ReturnAuthorization
    {
        $dto           = UpdateReturnAuthorizationData::fromArray($data);
        $authorization = $this->authorizationRepository->find($dto->id);

        if (! $authorization) {
            throw new ReturnAuthorizationNotFoundException($dto->id);
        }

        $authorization->updateDetails($dto->reason, $dto->notes, $dto->metadata);

        return $this->authorizationRepository->save($authorization);
    }
}
