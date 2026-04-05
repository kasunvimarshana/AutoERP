<?php
declare(strict_types=1);
namespace Modules\Currency\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Currency\Domain\Entities\Currency;
use Modules\Currency\Domain\RepositoryInterfaces\CurrencyRepositoryInterface;
use Modules\Currency\Infrastructure\Persistence\Eloquent\Models\CurrencyModel;

class EloquentCurrencyRepository implements CurrencyRepositoryInterface
{
    public function __construct(private readonly CurrencyModel $model) {}

    public function findByCode(string $code): ?Currency
    {
        $m = $this->model->newQuery()->find($code);
        return $m ? $this->toEntity($m) : null;
    }

    public function findAll(bool $activeOnly = true): array
    {
        $q = $this->model->newQuery()->orderBy('code');
        if ($activeOnly) { $q->where('is_active', true); }
        return $q->get()->map(fn($m) => $this->toEntity($m))->all();
    }

    public function findBaseCurrency(): ?Currency
    {
        $m = $this->model->newQuery()->where('is_base', true)->first();
        return $m ? $this->toEntity($m) : null;
    }

    public function create(array $data): Currency
    {
        $m = $this->model->newQuery()->create($data);
        return $this->toEntity($m);
    }

    public function update(string $code, array $data): ?Currency
    {
        $m = $this->model->newQuery()->find($code);
        if (!$m) { return null; }
        $m->update($data);
        return $this->toEntity($m->fresh());
    }

    private function toEntity(CurrencyModel $m): Currency
    {
        return new Currency(
            null, $m->code, $m->name, $m->symbol,
            (int) $m->decimal_places, (bool) $m->is_base, (bool) $m->is_active,
            $m->created_at, $m->updated_at,
        );
    }
}
