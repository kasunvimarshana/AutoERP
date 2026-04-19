<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Tenant\Application\Contracts\UpdateTenantDomainServiceInterface;
use Modules\Tenant\Domain\Entities\TenantDomain;
use Modules\Tenant\Domain\Events\TenantDomainUpdated;
use Modules\Tenant\Domain\Exceptions\TenantDomainNotFoundException;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantDomainRepositoryInterface;

class UpdateTenantDomainService extends BaseService implements UpdateTenantDomainServiceInterface
{
    public function __construct(
        private readonly TenantDomainRepositoryInterface $tenantDomainRepository,
    ) {
        parent::__construct($tenantDomainRepository);
    }

    protected function handle(array $data): TenantDomain
    {
        $id = (int) $data['id'];

        $existing = $this->tenantDomainRepository->find($id);
        if (! $existing) {
            throw new TenantDomainNotFoundException($id);
        }

        $domain = array_key_exists('domain', $data)
            ? (string) $data['domain']
            : $existing->getDomain();

        $isPrimary = array_key_exists('is_primary', $data)
            ? (bool) $data['is_primary']
            : $existing->isPrimary();

        $isVerified = array_key_exists('is_verified', $data)
            ? (bool) $data['is_verified']
            : $existing->isVerified();

        $verifiedAt = array_key_exists('verified_at', $data)
            ? ($data['verified_at'] ? new \DateTimeImmutable((string) $data['verified_at']) : null)
            : $existing->getVerifiedAt();

        $existing->update(
            domain: $domain,
            isPrimary: $isPrimary,
            isVerified: $isVerified,
            verifiedAt: $verifiedAt,
        );

        $saved = $this->tenantDomainRepository->save($existing);
        $this->addEvent(new TenantDomainUpdated($saved));

        return $saved;
    }
}
