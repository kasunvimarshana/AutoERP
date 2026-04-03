<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Returns\Domain\Entities\StockReturn;
use Modules\Returns\Domain\RepositoryInterfaces\StockReturnRepositoryInterface;
use Modules\Returns\Infrastructure\Persistence\Eloquent\Models\StockReturnModel;

class EloquentStockReturnRepository extends EloquentRepository implements StockReturnRepositoryInterface
{
    public function __construct(StockReturnModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (StockReturnModel $m): StockReturn => $this->mapModelToDomainEntity($m));
    }

    public function save(StockReturn $return): StockReturn
    {
        $savedModel = null;

        DB::transaction(function () use ($return, &$savedModel) {
            $data = [
                'tenant_id'              => $return->getTenantId(),
                'reference_number'       => $return->getReferenceNumber(),
                'return_type'            => $return->getReturnType(),
                'status'                 => $return->getStatus(),
                'party_id'               => $return->getPartyId(),
                'party_type'             => $return->getPartyType(),
                'original_reference_id'  => $return->getOriginalReferenceId(),
                'original_reference_type'=> $return->getOriginalReferenceType(),
                'return_reason'          => $return->getReturnReason(),
                'total_amount'           => $return->getTotalAmount(),
                'currency'               => $return->getCurrency(),
                'restock'                => $return->getRestock(),
                'restock_location_id'    => $return->getRestockLocationId(),
                'restocking_fee'         => $return->getRestockingFee(),
                'credit_memo_issued'     => $return->getCreditMemoIssued(),
                'credit_memo_reference'  => $return->getCreditMemoReference(),
                'approved_by'            => $return->getApprovedBy(),
                'approved_at'            => $return->getApprovedAt()?->format('Y-m-d H:i:s'),
                'processed_by'           => $return->getProcessedBy(),
                'processed_at'           => $return->getProcessedAt()?->format('Y-m-d H:i:s'),
                'notes'                  => $return->getNotes(),
                'metadata'               => $return->getMetadata()->toArray(),
            ];

            if ($return->getId()) {
                $savedModel = $this->update($return->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof StockReturnModel) {
            throw new \RuntimeException('Failed to save StockReturn.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function findByReferenceNumber(int $tenantId, string $ref): ?StockReturn
    {
        $model = $this->model->where('tenant_id', $tenantId)->where('reference_number', $ref)->first();

        return $model ? $this->mapModelToDomainEntity($model) : null;
    }

    public function findByParty(int $tenantId, int $partyId, string $partyType): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('party_id', $partyId)
            ->where('party_type', $partyType)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    public function findByStatus(int $tenantId, string $status): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('status', $status)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    private function mapModelToDomainEntity(StockReturnModel $model): StockReturn
    {
        return new StockReturn(
            tenantId:              $model->tenant_id,
            referenceNumber:       $model->reference_number,
            returnType:            $model->return_type,
            partyId:               $model->party_id,
            partyType:             $model->party_type,
            originalReferenceId:   $model->original_reference_id,
            originalReferenceType: $model->original_reference_type,
            returnReason:          $model->return_reason,
            totalAmount:           (float) $model->total_amount,
            currency:              $model->currency,
            restock:               (bool) $model->restock,
            restockLocationId:     $model->restock_location_id,
            restockingFee:         (float) $model->restocking_fee,
            creditMemoIssued:      (bool) $model->credit_memo_issued,
            creditMemoReference:   $model->credit_memo_reference,
            approvedBy:            $model->approved_by,
            approvedAt:            $model->approved_at,
            processedBy:           $model->processed_by,
            processedAt:           $model->processed_at,
            notes:                 $model->notes,
            metadata:              isset($model->metadata) ? new Metadata((array) $model->metadata) : null,
            status:                $model->status,
            id:                    $model->id,
            createdAt:             $model->created_at,
            updatedAt:             $model->updated_at,
        );
    }
}
