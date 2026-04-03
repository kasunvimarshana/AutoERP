<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Returns\Application\Contracts\ApproveReturnAuthorizationServiceInterface;
use Modules\Returns\Domain\Entities\ReturnAuthorization;
use Modules\Returns\Domain\Events\ReturnAuthorizationApproved;
use Modules\Returns\Domain\Exceptions\ReturnAuthorizationNotFoundException;
use Modules\Returns\Domain\RepositoryInterfaces\ReturnAuthorizationRepositoryInterface;

class ApproveReturnAuthorizationService extends BaseService implements ApproveReturnAuthorizationServiceInterface
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

        $expiresAt = isset($data['expires_at']) ? new \DateTimeImmutable($data['expires_at']) : null;

        $authorization->approve((int) $data['authorized_by'], $expiresAt);

        $saved = $this->authorizationRepository->save($authorization);
        $this->addEvent(new ReturnAuthorizationApproved($saved));

        return $saved;
    }
}
