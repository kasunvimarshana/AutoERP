<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\HR\Domain\Entities\BiometricDevice;
use Modules\HR\Domain\RepositoryInterfaces\BiometricDeviceRepositoryInterface;
use Modules\HR\Domain\ValueObjects\BiometricDeviceStatus;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\BiometricDeviceModel;

class EloquentBiometricDeviceRepository extends EloquentRepository implements BiometricDeviceRepositoryInterface
{
    public function __construct(BiometricDeviceModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (BiometricDeviceModel $m): BiometricDevice => $this->mapModelToDomainEntity($m));
    }

    public function save(BiometricDevice $entity): BiometricDevice
    {
        $data = ['tenant_id' => $entity->getTenantId(), 'name' => $entity->getName(), 'code' => $entity->getCode(), 'device_type' => $entity->getDeviceType(), 'ip_address' => $entity->getIpAddress(), 'port' => $entity->getPort(), 'location' => $entity->getLocation(), 'org_unit_id' => $entity->getOrgUnitId(), 'status' => $entity->getStatus()->value, 'metadata' => $entity->getMetadata()];
        $model = $entity->getId() ? $this->update($entity->getId(), $data) : $this->create($data);

        return $this->toDomainEntity($model);
    }

    public function find(int|string $id, array $columns = ['*']): ?BiometricDevice
    {
        return parent::find($id, $columns);
    }

    public function findByTenantAndCode(int $tenantId, string $code): ?BiometricDevice
    {
        $m = $this->model->where('tenant_id', $tenantId)->where('code', $code)->first();

        return $m ? $this->toDomainEntity($m) : null;
    }

    private function mapModelToDomainEntity(BiometricDeviceModel $m): BiometricDevice
    {
        return new BiometricDevice($m->tenant_id, $m->name, $m->code, $m->device_type, $m->ip_address, (int) $m->port, $m->location, $m->org_unit_id, BiometricDeviceStatus::from($m->status), $m->metadata ?? [], $m->created_at instanceof \DateTimeInterface ? $m->created_at : new \DateTimeImmutable($m->created_at ?? 'now'), $m->updated_at instanceof \DateTimeInterface ? $m->updated_at : new \DateTimeImmutable($m->updated_at ?? 'now'), $m->id);
    }
}
