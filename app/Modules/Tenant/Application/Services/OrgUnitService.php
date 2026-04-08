<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Services;

use Illuminate\Support\Str;
use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Tenant\Application\Contracts\OrgUnitServiceInterface;
use Modules\Tenant\Application\DTOs\OrgUnitData;
use Modules\Tenant\Domain\RepositoryInterfaces\OrgUnitRepositoryInterface;

final class OrgUnitService extends BaseService implements OrgUnitServiceInterface
{
    public function __construct(OrgUnitRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Required by BaseService — unused directly; create() is the primary entry point.
     */
    protected function handle(array $data): mixed
    {
        $dto = OrgUnitData::fromArray($data);

        return $this->create($dto);
    }

    /**
     * {@inheritdoc}
     */
    public function create(OrgUnitData $dto): mixed
    {
        $dto->validate($dto->toArray());

        return \Illuminate\Support\Facades\DB::transaction(function () use ($dto) {
            $payload = array_filter($dto->toArray(), fn ($v) => $v !== null);
            $payload['uuid'] = (string) Str::uuid();
            $payload['is_active'] = $dto->is_active ?? true;
            $payload['sort_order'] = $dto->sort_order ?? 0;

            $orgUnit = $this->repository->create($payload);
            $this->dispatchEvents();

            return $orgUnit;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getTree(int $tenantId): array
    {
        /** @var OrgUnitRepositoryInterface $repo */
        $repo = $this->repository;

        return $repo->getTree($tenantId);
    }

    /**
     * {@inheritdoc}
     */
    public function move(int $id, ?int $parentId): mixed
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($id, $parentId) {
            $orgUnit = $this->repository->find($id);

            if (! $orgUnit) {
                throw new NotFoundException('OrgUnit', $id);
            }

            $updated = $this->repository->update($id, ['parent_id' => $parentId]);
            $this->dispatchEvents();

            return $updated;
        });
    }
}
