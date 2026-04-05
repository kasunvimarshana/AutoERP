<?php
declare(strict_types=1);
namespace Modules\Currency\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Currency\Domain\Entities\ExchangeRate;
use Modules\Currency\Domain\RepositoryInterfaces\ExchangeRateRepositoryInterface;
use Modules\Currency\Infrastructure\Persistence\Eloquent\Models\ExchangeRateModel;

class EloquentExchangeRateRepository implements ExchangeRateRepositoryInterface
{
    public function __construct(private readonly ExchangeRateModel $model) {}

    public function findActive(int $tenantId, string $from, string $to, \DateTimeInterface $at): ?ExchangeRate
    {
        $dateStr = $at->format('Y-m-d H:i:s');
        $m = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('from_currency', $from)
            ->where('to_currency', $to)
            ->where(fn($q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', $dateStr))
            ->where(fn($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>=', $dateStr))
            ->orderByDesc('valid_from')
            ->first();
        return $m ? $this->toEntity($m) : null;
    }

    public function findAllByTenant(int $tenantId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderBy('from_currency')
            ->orderBy('to_currency')
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function create(array $data): ExchangeRate
    {
        $m = $this->model->newQuery()->create($data);
        return $this->toEntity($m);
    }

    public function update(int $id, array $data): ?ExchangeRate
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) { return null; }
        $m->update($data);
        return $this->toEntity($m->fresh());
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model->newQuery()->find($id)?->delete();
    }

    private function toEntity(ExchangeRateModel $m): ExchangeRate
    {
        return new ExchangeRate(
            $m->id, $m->tenant_id,
            $m->from_currency, $m->to_currency,
            (float) $m->rate, $m->source,
            $m->valid_from, $m->valid_to,
            $m->created_at, $m->updated_at,
        );
    }
}
