<?php

declare(strict_types=1);

namespace Modules\Configuration\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Configuration\Domain\Entities\Timezone;
use Modules\Configuration\Domain\RepositoryInterfaces\TimezoneRepositoryInterface;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\TimezoneModel;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;

class EloquentTimezoneRepository extends EloquentRepository implements TimezoneRepositoryInterface
{
    public function __construct(TimezoneModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (TimezoneModel $m): Timezone => $this->mapToDomain($m));
    }

    public function findByName(string $name): ?Timezone
    {
        $model = $this->model->where('name', $name)->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function save(Timezone $timezone): Timezone
    {
        $data = [
            'name' => $timezone->getName(),
            'offset' => $timezone->getOffset(),
        ];

        $model = $timezone->getId()
            ? $this->update($timezone->getId(), $data)
            : $this->create($data);

        /** @var TimezoneModel $model */
        return $this->mapToDomain($model);
    }

    private function mapToDomain(TimezoneModel $model): Timezone
    {
        return new Timezone(
            name: $model->name,
            offset: (string) $model->offset,
            id: $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
