<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Services;

use Illuminate\Support\Str;
use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Tenant\Application\Contracts\TenantServiceInterface;
use Modules\Tenant\Application\DTOs\TenantData;
use Modules\Tenant\Domain\Events\TenantCreated;
use Modules\Tenant\Domain\Events\TenantUpdated;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;

final class TenantService extends BaseService implements TenantServiceInterface
{
    public function __construct(TenantRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Required by BaseService — unused directly; create() is the primary entry point.
     */
    protected function handle(array $data): mixed
    {
        $dto = TenantData::fromArray($data);

        return $this->create($dto);
    }

    /**
     * {@inheritdoc}
     */
    public function create(TenantData $dto): mixed
    {
        $dto->validate($dto->toArray());

        return \Illuminate\Support\Facades\DB::transaction(function () use ($dto) {
            $payload = array_filter($dto->toArray(), fn ($v) => $v !== null);
            $payload['uuid'] = (string) Str::uuid();
            $payload['status'] = $payload['status'] ?? 'trial';
            $payload['plan'] = $payload['plan'] ?? 'free';

            $tenant = $this->repository->create($payload);

            $this->addEvent(new TenantCreated($tenant->id, $tenant->uuid, $tenant->plan));
            $this->dispatchEvents();

            return $tenant;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function findBySlug(string $slug): mixed
    {
        /** @var TenantRepositoryInterface $repo */
        $repo = $this->repository;
        $tenant = $repo->findBySlug($slug);

        if (! $tenant) {
            throw new NotFoundException('Tenant', $slug);
        }

        return $tenant;
    }

    /**
     * {@inheritdoc}
     */
    public function activate(int $id): mixed
    {
        return $this->changeStatus($id, 'active');
    }

    /**
     * {@inheritdoc}
     */
    public function suspend(int $id): mixed
    {
        return $this->changeStatus($id, 'suspended');
    }

    private function changeStatus(int $id, string $status): mixed
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($id, $status) {
            $tenant = $this->repository->find($id);

            if (! $tenant) {
                throw new NotFoundException('Tenant', $id);
            }

            $updated = $this->repository->update($id, ['status' => $status]);

            $this->addEvent(new TenantUpdated($id, ['status' => $status]));
            $this->dispatchEvents();

            return $updated;
        });
    }
}
