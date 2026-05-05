<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Rental\Domain\Entities\RentalRateCard;
use Modules\Rental\Domain\RepositoryInterfaces\RentalRateCardRepositoryInterface;
use Modules\Rental\Infrastructure\Persistence\Eloquent\Models\RentalRateCardModel;

class EloquentRentalRateCardRepository implements RentalRateCardRepositoryInterface
{
    public function __construct(
        private readonly RentalRateCardModel $model,
    ) {}

    public function save(RentalRateCard $rateCard): RentalRateCard
    {
        if ($rateCard->getId() !== null) {
            /** @var RentalRateCardModel $record */
            $record = $this->model->newQuery()->findOrFail($rateCard->getId());
            $record->update($this->toArray($rateCard));
            $record->refresh();
        } else {
            /** @var RentalRateCardModel $record */
            $record = $this->model->newQuery()->create($this->toArray($rateCard));
        }

        return $this->mapToEntity($record);
    }

    public function findById(int $tenantId, int $id): ?RentalRateCard
    {
        /** @var RentalRateCardModel|null $record */
        $record = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $record !== null ? $this->mapToEntity($record) : null;
    }

    public function findByCode(int $tenantId, string $code): ?RentalRateCard
    {
        /** @var RentalRateCardModel|null $record */
        $record = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();

        return $record !== null ? $this->mapToEntity($record) : null;
    }

    public function paginate(int $tenantId, array $filters, int $perPage, int $page): array
    {
        $query = $this->model->newQuery()->where('tenant_id', $tenantId);

        foreach ($filters as $field => $value) {
            if ($value !== null && $value !== '') {
                $query->where($field, $value);
            }
        }

        $paginator = $query->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => array_map(fn (RentalRateCardModel $m): RentalRateCard => $this->mapToEntity($m), $paginator->items()),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
        ];
    }

    public function existsByCode(int $tenantId, string $code, ?int $excludeId = null): bool
    {
        $query = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('code', $code);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    private function toArray(RentalRateCard $rateCard): array
    {
        return [
            'tenant_id' => $rateCard->getTenantId(),
            'org_unit_id' => $rateCard->getOrgUnitId(),
            'row_version' => $rateCard->getRowVersion(),
            'code' => $rateCard->getCode(),
            'name' => $rateCard->getName(),
            'asset_id' => $rateCard->getAssetId(),
            'product_id' => $rateCard->getProductId(),
            'customer_id' => $rateCard->getCustomerId(),
            'billing_uom' => $rateCard->getBillingUom(),
            'rate' => $rateCard->getRate(),
            'deposit_percentage' => $rateCard->getDepositPercentage(),
            'priority' => $rateCard->getPriority(),
            'valid_from' => $rateCard->getValidFrom(),
            'valid_to' => $rateCard->getValidTo(),
            'status' => $rateCard->getStatus(),
            'notes' => $rateCard->getNotes(),
        ];
    }

    private function mapToEntity(RentalRateCardModel $model): RentalRateCard
    {
        return new RentalRateCard(
            tenantId: (int) $model->tenant_id,
            code: (string) $model->code,
            name: (string) $model->name,
            billingUom: (string) $model->billing_uom,
            rate: (string) $model->rate,
            orgUnitId: $model->org_unit_id !== null ? (int) $model->org_unit_id : null,
            assetId: $model->asset_id !== null ? (int) $model->asset_id : null,
            productId: $model->product_id !== null ? (int) $model->product_id : null,
            customerId: $model->customer_id !== null ? (int) $model->customer_id : null,
            depositPercentage: $model->deposit_percentage !== null ? (string) $model->deposit_percentage : null,
            priority: (int) $model->priority,
            validFrom: $model->valid_from,
            validTo: $model->valid_to,
            status: (string) $model->status,
            notes: $model->notes,
            rowVersion: (int) $model->row_version,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
            id: (int) $model->id,
        );
    }
}
