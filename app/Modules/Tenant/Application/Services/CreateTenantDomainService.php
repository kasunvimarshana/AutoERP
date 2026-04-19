<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Tenant\Application\Contracts\CreateTenantDomainServiceInterface;
use Modules\Tenant\Application\DTOs\TenantDomainData;
use Modules\Tenant\Domain\Entities\TenantDomain;
use Modules\Tenant\Domain\Events\TenantDomainCreated;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantDomainRepositoryInterface;

class CreateTenantDomainService extends BaseService implements CreateTenantDomainServiceInterface
{
    public function __construct(
        private readonly TenantDomainRepositoryInterface $tenantDomainRepository,
    ) {
        parent::__construct($tenantDomainRepository);
    }

    protected function handle(array $data): TenantDomain
    {
        $dto = TenantDomainData::fromArray($data);

        $tenantDomain = new TenantDomain(
            tenantId: $dto->tenant_id,
            domain: $dto->domain,
            isPrimary: $dto->is_primary,
            isVerified: $dto->is_verified,
            verifiedAt: $dto->verified_at ? new \DateTimeImmutable($dto->verified_at) : null,
        );

        $saved = $this->tenantDomainRepository->save($tenantDomain);
        $this->addEvent(new TenantDomainCreated($saved));

        return $saved;
    }
}
