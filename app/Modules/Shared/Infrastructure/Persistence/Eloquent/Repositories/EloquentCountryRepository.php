<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Shared\Domain\Entities\Country;
use Modules\Shared\Domain\RepositoryInterfaces\CountryRepositoryInterface;
use Modules\Shared\Infrastructure\Persistence\Eloquent\Models\CountryModel;

class EloquentCountryRepository extends EloquentRepository implements CountryRepositoryInterface
{
    public function __construct(CountryModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (CountryModel $m): Country => $this->mapToDomain($m));
    }

    public function findByCode(string $code): ?Country
    {
        $model = $this->model->where('code', $code)->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function save(Country $country): Country
    {
        $data = [
            'code' => $country->getCode(),
            'name' => $country->getName(),
            'phone_code' => $country->getPhoneCode(),
        ];

        $model = $country->getId()
            ? $this->update($country->getId(), $data)
            : $this->create($data);

        /** @var CountryModel $model */
        return $this->mapToDomain($model);
    }

    private function mapToDomain(CountryModel $model): Country
    {
        return new Country(
            code: $model->code,
            name: $model->name,
            phoneCode: $model->phone_code,
            id: $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
