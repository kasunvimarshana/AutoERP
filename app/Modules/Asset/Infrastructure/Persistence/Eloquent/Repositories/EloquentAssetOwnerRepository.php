<?php declare(strict_types=1);

namespace Modules\Asset\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Asset\Domain\Entities\AssetOwner;
use Modules\Asset\Domain\RepositoryInterfaces\AssetOwnerRepositoryInterface;
use Modules\Asset\Infrastructure\Persistence\Eloquent\Models\AssetOwnerModel;

class EloquentAssetOwnerRepository implements AssetOwnerRepositoryInterface
{
    public function create(AssetOwner $owner): void
    {
        AssetOwnerModel::create([
            'id' => $owner->getId(),
            'tenant_id' => $owner->getTenantId(),
            'name' => $owner->getName(),
            'owner_type' => $owner->getOwnerType(),
            'commission_percentage' => $owner->getCommissionPercentage(),
            'payment_terms_days' => $owner->getPaymentTermsDays(),
            'is_active' => $owner->isActive(),
        ]);
    }

    public function findById(string $id): ?AssetOwner
    {
        $model = AssetOwnerModel::find($id);
        return $model ? $this->toDomain($model) : null;
    }

    public function findByName(string $tenantId, string $name): ?AssetOwner
    {
        $model = AssetOwnerModel::byTenant($tenantId)->where('name', $name)->first();
        return $model ? $this->toDomain($model) : null;
    }

    public function getAllByTenant(string $tenantId, int $page = 1, int $limit = 50): array
    {
        $query = AssetOwnerModel::byTenant($tenantId);
        $total = $query->count();
        $data = $query->paginate($limit, ['*'], 'page', $page)->items();

        return [
            'data' => array_map(fn($m) => $this->toDomain($m), $data),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    public function getActiveByTenant(string $tenantId, int $page = 1, int $limit = 50): array
    {
        $query = AssetOwnerModel::byTenant($tenantId)->active();
        $total = $query->count();
        $data = $query->paginate($limit, ['*'], 'page', $page)->items();

        return [
            'data' => array_map(fn($m) => $this->toDomain($m), $data),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    public function getThirdPartyOwners(string $tenantId, int $page = 1, int $limit = 50): array
    {
        $query = AssetOwnerModel::byTenant($tenantId)->thirdParty();
        $total = $query->count();
        $data = $query->paginate($limit, ['*'], 'page', $page)->items();

        return [
            'data' => array_map(fn($m) => $this->toDomain($m), $data),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    public function update(AssetOwner $owner): void
    {
        AssetOwnerModel::where('id', $owner->getId())->update([
            'is_active' => $owner->isActive(),
            'commission_percentage' => $owner->getCommissionPercentage(),
        ]);
    }

    public function delete(string $id): void
    {
        AssetOwnerModel::where('id', $id)->delete();
    }

    public function countByTenant(string $tenantId): int
    {
        return AssetOwnerModel::byTenant($tenantId)->count();
    }

    private function toDomain(AssetOwnerModel $model): AssetOwner
    {
        return new AssetOwner(
            $model->id,
            $model->tenant_id,
            $model->name,
            $model->owner_type,
            $model->contact_person,
            $model->email,
            $model->phone,
            $model->address,
            $model->city,
            $model->state,
            $model->postal_code,
            $model->country,
            $model->tax_id,
            $model->commission_percentage,
            $model->payment_terms_days,
            $model->is_active,
        );
    }
}
