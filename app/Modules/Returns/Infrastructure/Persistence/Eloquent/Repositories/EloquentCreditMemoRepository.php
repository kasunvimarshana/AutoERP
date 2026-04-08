<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Returns\Domain\Entities\CreditMemo;
use Modules\Returns\Domain\RepositoryInterfaces\CreditMemoRepositoryInterface;
use Modules\Returns\Infrastructure\Persistence\Eloquent\Models\CreditMemoModel;

class EloquentCreditMemoRepository extends EloquentRepository implements CreditMemoRepositoryInterface
{
    public function __construct(CreditMemoModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (CreditMemoModel $m): CreditMemo => $this->mapModelToDomainEntity($m));
    }

    public function save(CreditMemo $memo): CreditMemo
    {
        $savedModel = null;

        DB::transaction(function () use ($memo, &$savedModel) {
            $data = [
                'tenant_id'        => $memo->getTenantId(),
                'reference_number' => $memo->getReferenceNumber(),
                'stock_return_id'  => $memo->getStockReturnId(),
                'party_id'         => $memo->getPartyId(),
                'party_type'       => $memo->getPartyType(),
                'status'           => $memo->getStatus(),
                'amount'           => $memo->getAmount(),
                'currency'         => $memo->getCurrency(),
                'issue_date'       => $memo->getIssueDate()?->format('Y-m-d H:i:s'),
                'applied_date'     => $memo->getAppliedDate()?->format('Y-m-d H:i:s'),
                'voided_date'      => $memo->getVoidedDate()?->format('Y-m-d H:i:s'),
                'notes'            => $memo->getNotes(),
                'metadata'         => $memo->getMetadata()->toArray(),
            ];

            if ($memo->getId()) {
                $savedModel = $this->update($memo->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof CreditMemoModel) {
            throw new \RuntimeException('Failed to save CreditMemo.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function findByStockReturn(int $tenantId, int $stockReturnId): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('stock_return_id', $stockReturnId)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
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

    private function mapModelToDomainEntity(CreditMemoModel $model): CreditMemo
    {
        return new CreditMemo(
            tenantId:        $model->tenant_id,
            referenceNumber: $model->reference_number,
            partyId:         $model->party_id,
            partyType:       $model->party_type,
            stockReturnId:   $model->stock_return_id,
            amount:          (float) $model->amount,
            currency:        $model->currency,
            issueDate:       $model->issue_date,
            appliedDate:     $model->applied_date,
            voidedDate:      $model->voided_date,
            notes:           $model->notes,
            metadata:        isset($model->metadata) ? new Metadata((array) $model->metadata) : null,
            status:          $model->status,
            id:              $model->id,
            createdAt:       $model->created_at,
            updatedAt:       $model->updated_at,
        );
    }
}
