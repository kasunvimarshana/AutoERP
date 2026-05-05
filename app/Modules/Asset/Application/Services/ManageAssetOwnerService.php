<?php declare(strict_types=1);

namespace Modules\Asset\Application\Services;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Asset\Application\Contracts\ManageAssetOwnerServiceInterface;
use Modules\Asset\Domain\Entities\AssetOwner;
use Modules\Asset\Domain\RepositoryInterfaces\AssetOwnerRepositoryInterface;

class ManageAssetOwnerService implements ManageAssetOwnerServiceInterface
{
    public function __construct(
        private readonly AssetOwnerRepositoryInterface $owners,
    ) {}

    public function create(array $data): AssetOwner
    {
        return DB::transaction(function () use ($data): AssetOwner {
            $owner = new AssetOwner(
                id: Str::uuid()->toString(),
                tenantId: (string) $data['tenant_id'],
                name: $data['name'],
                type: $data['type'] ?? 'internal',
                contactPerson: $data['contact_person'] ?? null,
                email: $data['email'] ?? null,
                phone: $data['phone'] ?? null,
                notes: $data['notes'] ?? null,
            );

            $this->owners->create($owner);
            return $owner;
        });
    }

    public function update(int $tenantId, string $id, array $data): AssetOwner
    {
        return DB::transaction(function () use ($tenantId, $id, $data): AssetOwner {
            $owner = $this->owners->findById($id);
            if (!$owner || $owner->getTenantId() !== (string) $tenantId) {
                throw new \Exception('Asset owner not found');
            }

            $this->owners->update($owner);
            return $this->owners->findById($id);
        });
    }

    public function find(int $tenantId, string $id): AssetOwner
    {
        $owner = $this->owners->findById($id);
        if (!$owner || $owner->getTenantId() !== (string) $tenantId) {
            throw new \Exception('Asset owner not found');
        }
        return $owner;
    }

    public function delete(int $tenantId, string $id): void
    {
        $this->find($tenantId, $id);
        $this->owners->delete($id);
    }

    public function list(int $tenantId, int $perPage = 15, int $page = 1): array
    {
        return $this->owners->getAllByTenant((string) $tenantId, $page, $perPage);
    }
}
