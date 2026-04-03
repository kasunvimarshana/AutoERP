<?php

declare(strict_types=1);

namespace Modules\GS1\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\GS1\Domain\Entities\Gs1Barcode;
use Modules\GS1\Domain\RepositoryInterfaces\Gs1BarcodeRepositoryInterface;
use Modules\GS1\Infrastructure\Persistence\Eloquent\Models\Gs1BarcodeModel;

class EloquentGs1BarcodeRepository extends EloquentRepository implements Gs1BarcodeRepositoryInterface
{
    public function __construct(Gs1BarcodeModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (Gs1BarcodeModel $m): Gs1Barcode => $this->mapModelToDomainEntity($m));
    }

    public function save(Gs1Barcode $barcode): Gs1Barcode
    {
        $savedModel = null;
        DB::transaction(function () use ($barcode, &$savedModel) {
            $data = [
                'tenant_id'               => $barcode->getTenantId(),
                'gs1_identifier_id'       => $barcode->getGs1IdentifierId(),
                'barcode_type'            => $barcode->getBarcodeType(),
                'barcode_data'            => $barcode->getBarcodeData(),
                'application_identifiers' => $barcode->getApplicationIdentifiers(),
                'is_primary'              => $barcode->isPrimary(),
                'is_active'               => $barcode->isActive(),
                'metadata'                => $barcode->getMetadata()->toArray(),
            ];
            if ($barcode->getId()) {
                $savedModel = $this->update($barcode->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof Gs1BarcodeModel) {
            throw new \RuntimeException('Failed to save Gs1Barcode.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function findByIdentifier(int $tenantId, int $identifierId): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('gs1_identifier_id', $identifierId)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    public function findPrimary(int $tenantId, int $identifierId): ?Gs1Barcode
    {
        $model = $this->model
            ->where('tenant_id', $tenantId)
            ->where('gs1_identifier_id', $identifierId)
            ->where('is_primary', true)
            ->first();

        return $model ? $this->mapModelToDomainEntity($model) : null;
    }

    private function mapModelToDomainEntity(Gs1BarcodeModel $model): Gs1Barcode
    {
        return new Gs1Barcode(
            tenantId:               $model->tenant_id,
            gs1IdentifierId:        $model->gs1_identifier_id,
            barcodeType:            $model->barcode_type,
            barcodeData:            $model->barcode_data,
            applicationIdentifiers: $model->application_identifiers,
            isPrimary:              (bool) $model->is_primary,
            isActive:               (bool) $model->is_active,
            metadata:               isset($model->metadata) ? new Metadata((array) $model->metadata) : null,
            id:                     $model->id,
            createdAt:              $model->created_at,
            updatedAt:              $model->updated_at,
        );
    }
}
