<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\Entities\ProductIdentifier;
use Modules\Product\Domain\RepositoryInterfaces\ProductIdentifierRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductIdentifierModel;

class EloquentProductIdentifierRepository extends EloquentRepository implements ProductIdentifierRepositoryInterface
{
    public function __construct(ProductIdentifierModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (ProductIdentifierModel $model): ProductIdentifier => $this->mapModelToDomainEntity($model));
    }

    public function save(ProductIdentifier $productIdentifier): ProductIdentifier
    {
        $data = [
            'tenant_id' => $productIdentifier->getTenantId(),
            'product_id' => $productIdentifier->getProductId(),
            'variant_id' => $productIdentifier->getVariantId(),
            'batch_id' => $productIdentifier->getBatchId(),
            'serial_id' => $productIdentifier->getSerialId(),
            'technology' => $productIdentifier->getTechnology(),
            'format' => $productIdentifier->getFormat(),
            'value' => $productIdentifier->getValue(),
            'gs1_company_prefix' => $productIdentifier->getGs1CompanyPrefix(),
            'gs1_application_identifiers' => $productIdentifier->getGs1ApplicationIdentifiers(),
            'is_primary' => $productIdentifier->isPrimary(),
            'is_active' => $productIdentifier->isActive(),
            'format_config' => $productIdentifier->getFormatConfig(),
            'metadata' => $productIdentifier->getMetadata(),
        ];

        if ($productIdentifier->getId()) {
            $model = $this->update($productIdentifier->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var ProductIdentifierModel $model */

        return $this->toDomainEntity($model);
    }

    public function findByTenantAndValue(int $tenantId, string $value): ?ProductIdentifier
    {
        /** @var ProductIdentifierModel|null $model */
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('value', $value)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function find($id, array $columns = ['*']): ?ProductIdentifier
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(ProductIdentifierModel $model): ProductIdentifier
    {
        return new ProductIdentifier(
            id: (int) $model->id,
            tenantId: (int) $model->tenant_id,
            productId: (int) $model->product_id,
            variantId: $model->variant_id !== null ? (int) $model->variant_id : null,
            batchId: $model->batch_id !== null ? (int) $model->batch_id : null,
            serialId: $model->serial_id !== null ? (int) $model->serial_id : null,
            technology: (string) $model->technology,
            format: $model->format,
            value: (string) $model->value,
            gs1CompanyPrefix: $model->gs1_company_prefix,
            gs1ApplicationIdentifiers: is_array($model->gs1_application_identifiers) ? $model->gs1_application_identifiers : null,
            isPrimary: (bool) $model->is_primary,
            isActive: (bool) $model->is_active,
            formatConfig: is_array($model->format_config) ? $model->format_config : null,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
