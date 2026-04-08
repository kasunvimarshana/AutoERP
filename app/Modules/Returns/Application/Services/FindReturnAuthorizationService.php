<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Services;

use Illuminate\Support\Collection;
use Modules\Core\Application\Services\BaseService;
use Modules\Returns\Application\Contracts\FindReturnAuthorizationServiceInterface;
use Modules\Returns\Domain\RepositoryInterfaces\ReturnAuthorizationRepositoryInterface;

class FindReturnAuthorizationService extends BaseService implements FindReturnAuthorizationServiceInterface
{
    public function __construct(private readonly ReturnAuthorizationRepositoryInterface $authorizationRepository)
    {
        parent::__construct($authorizationRepository);
    }

    public function findByRmaNumber(int $tenantId, string $rmaNumber): ?object
    {
        return $this->authorizationRepository->findByRmaNumber($tenantId, $rmaNumber);
    }

    public function findByParty(int $tenantId, int $partyId, string $partyType): Collection
    {
        return $this->authorizationRepository->findByParty($tenantId, $partyId, $partyType);
    }

    public function findByStatus(int $tenantId, string $status): Collection
    {
        return $this->authorizationRepository->findByStatus($tenantId, $status);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
