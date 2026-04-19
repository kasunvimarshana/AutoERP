<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Shared\Domain\Entities\Currency;
use Modules\Shared\Domain\RepositoryInterfaces\CurrencyRepositoryInterface;
use Modules\Shared\Infrastructure\Persistence\Eloquent\Models\CurrencyModel;

class EloquentCurrencyRepository extends EloquentRepository implements CurrencyRepositoryInterface
{
    public function __construct(CurrencyModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (CurrencyModel $m): Currency => $this->mapToDomain($m));
    }

    public function findByCode(string $code): ?Currency
    {
        $model = $this->model->where('code', $code)->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function save(Currency $currency): Currency
    {
        $data = [
            'code' => $currency->getCode(),
            'name' => $currency->getName(),
            'symbol' => $currency->getSymbol(),
            'decimal_places' => $currency->getDecimalPlaces(),
            'is_active' => $currency->isActive(),
        ];

        $model = $currency->getId()
            ? $this->update($currency->getId(), $data)
            : $this->create($data);

        /** @var CurrencyModel $model */
        return $this->mapToDomain($model);
    }

    private function mapToDomain(CurrencyModel $model): Currency
    {
        return new Currency(
            code: $model->code,
            name: $model->name,
            symbol: $model->symbol,
            decimalPlaces: (int) $model->decimal_places,
            isActive: (bool) $model->is_active,
            id: $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
