<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Returns\Domain\Entities\ReturnAuthorization;
use Modules\Returns\Domain\RepositoryInterfaces\ReturnAuthorizationRepositoryInterface;
use Modules\Returns\Infrastructure\Persistence\Eloquent\Models\ReturnAuthorizationModel;

class EloquentReturnAuthorizationRepository extends EloquentRepository implements ReturnAuthorizationRepositoryInterface
{
    public function __construct(ReturnAuthorizationModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (ReturnAuthorizationModel $m): ReturnAuthorization => $this->mapModelToDomainEntity($m));
    }

    public function save(ReturnAuthorization $auth): ReturnAuthorization
    {
        $savedModel = null;

        DB::transaction(function () use ($auth, &$savedModel) {
            $data = [
                'tenant_id'       => $auth->getTenantId(),
                'rma_number'      => $auth->getRmaNumber(),
                'return_type'     => $auth->getReturnType(),
                'party_id'        => $auth->getPartyId(),
                'party_type'      => $auth->getPartyType(),
                'reason'          => $auth->getReason(),
                'status'          => $auth->getStatus(),
                'authorized_by'   => $auth->getAuthorizedBy(),
                'authorized_at'   => $auth->getAuthorizedAt()?->format('Y-m-d H:i:s'),
                'expires_at'      => $auth->getExpiresAt()?->format('Y-m-d H:i:s'),
                'cancelled_at'    => $auth->getCancelledAt()?->format('Y-m-d H:i:s'),
                'stock_return_id' => $auth->getStockReturnId(),
                'notes'           => $auth->getNotes(),
                'metadata'        => $auth->getMetadata()->toArray(),
            ];

            if ($auth->getId()) {
                $savedModel = $this->update($auth->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof ReturnAuthorizationModel) {
            throw new \RuntimeException('Failed to save ReturnAuthorization.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function findByRmaNumber(int $tenantId, string $rmaNumber): ?ReturnAuthorization
    {
        $model = $this->model->where('tenant_id', $tenantId)->where('rma_number', $rmaNumber)->first();

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

    private function mapModelToDomainEntity(ReturnAuthorizationModel $model): ReturnAuthorization
    {
        return new ReturnAuthorization(
            tenantId:      $model->tenant_id,
            rmaNumber:     $model->rma_number,
            returnType:    $model->return_type,
            partyId:       $model->party_id,
            partyType:     $model->party_type,
            reason:        $model->reason,
            status:        $model->status,
            authorizedBy:  $model->authorized_by,
            authorizedAt:  $model->authorized_at,
            expiresAt:     $model->expires_at,
            cancelledAt:   $model->cancelled_at,
            stockReturnId: $model->stock_return_id,
            notes:         $model->notes,
            metadata:      isset($model->metadata) ? new Metadata((array) $model->metadata) : null,
            id:            $model->id,
            createdAt:     $model->created_at,
            updatedAt:     $model->updated_at,
        );
    }
}
