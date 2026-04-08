<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\DTOs\ProductData;
use Modules\Product\Application\ServiceInterfaces\CreateProductServiceInterface;
use Modules\Product\Application\ServiceInterfaces\UpdateProductServiceInterface;
use Modules\Product\Application\ServiceInterfaces\DeleteProductServiceInterface;
use Modules\Product\Application\ServiceInterfaces\GetProductServiceInterface;
use Modules\Product\Application\ServiceInterfaces\ListProductsServiceInterface;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Domain\Events\ProductCreated;
use Modules\Product\Domain\Events\ProductDeleted;
use Modules\Product\Domain\Events\ProductStatusChanged;
use Modules\Product\Domain\Events\ProductUpdated;
use Modules\Product\Domain\Exceptions\ProductNotFoundException;
use Modules\Product\Domain\Exceptions\ProductSkuAlreadyExistsException;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;
use Modules\Product\Domain\ValueObjects\UnitOfMeasure;

// ═══════════════════════════════════════════════════════════════════
// CreateProductService
// Confirmed from KVAutoERP PR #37 — hydrates UnitOfMeasure[], builds entity
// ═══════════════════════════════════════════════════════════════════
final class CreateProductService extends BaseService implements CreateProductServiceInterface
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
    ) {}

    protected function handle(array $data): Product
    {
        $dto = ProductData::fromArray($data);

        if ($this->repository->skuExists($dto->sku, $dto->tenant_id)) {
            throw new ProductSkuAlreadyExistsException($dto->sku);
        }

        // Hydrate UnitOfMeasure[] from DTO — confirmed PR #37 pattern
        $unitsOfMeasure = [];
        foreach ($dto->units_of_measure ?? [] as $uomData) {
            $unitsOfMeasure[] = UnitOfMeasure::fromArray($uomData);
        }

        $product = new Product(
            tenantId:                  $dto->tenant_id,
            sku:                       $dto->sku,
            name:                      $dto->name,
            price:                     $dto->price,
            currency:                  $dto->currency ?? 'USD',
            type:                      $dto->type ?? 'physical',
            status:                    $dto->status ?? 'active',
            description:               $dto->description,
            category:                  $dto->category,
            unitsOfMeasure:            $unitsOfMeasure,
            trackBatches:              (bool) ($dto->track_batches ?? false),
            trackLots:                 (bool) ($dto->track_lots ?? false),
            trackSerials:              (bool) ($dto->track_serials ?? false),
            trackExpiry:               (bool) ($dto->track_expiry ?? false),
            reorderPoint:              $dto->reorder_point,
            safetyStock:               $dto->safety_stock,
            leadTimeDays:              $dto->lead_time_days,
            standardCost:              $dto->standard_cost,
            downloadUrl:               $dto->download_url,
            downloadLimit:             $dto->download_limit,
            downloadExpiryDays:        $dto->download_expiry_days,
            subscriptionInterval:      $dto->subscription_interval,
            subscriptionIntervalCount: $dto->subscription_interval_count,
            attributes:                $dto->attributes,
            metadata:                  $dto->metadata,
        );

        $saved = $this->repository->save($product);

        // Dispatch domain event — confirmed from PR #37 imports
        Event::dispatch(new ProductCreated($saved));

        return $saved;
    }
}


// ═══════════════════════════════════════════════════════════════════
// UpdateProductService
// Confirmed from KVAutoERP PR #37 — null-safe UoM mapping
// null $unitsOfMeasure = preserve existing; [] = explicitly clear
// ═══════════════════════════════════════════════════════════════════
final class UpdateProductService extends BaseService implements UpdateProductServiceInterface
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
    ) {}

    protected function handle(array $data): Product
    {
        $dto     = ProductData::fromArray($data);
        $product = $this->repository->findById($data['id'] ?? 0);

        if ($product === null) {
            throw new ProductNotFoundException($data['id'] ?? 'unknown');
        }

        // null-safe UoM mapping — confirmed PR #37:
        // null = "not provided, keep existing"
        // []   = "explicitly clear"
        $unitsOfMeasure = null;
        if (isset($dto->units_of_measure)) {
            $unitsOfMeasure = [];
            foreach ($dto->units_of_measure as $uomData) {
                $unitsOfMeasure[] = UnitOfMeasure::fromArray($uomData);
            }
        }

        $previousType = (string) $product->getType();

        $product->updateDetails(
            name:           $dto->name,
            price:          $dto->price,
            currency:       $dto->currency ?? 'USD',
            description:    $dto->description,
            category:       $dto->category,
            type:           $dto->type ?? null,
            unitsOfMeasure: $unitsOfMeasure,
            attributes:     $dto->attributes,
            metadata:       $dto->metadata,
        );

        if (isset($dto->status)) {
            $previousStatus = (string) $product->getStatus();
            $product->changeStatus($dto->status);
            if ($previousStatus !== $dto->status) {
                Event::dispatch(new ProductStatusChanged($product, $previousStatus, $dto->status));
            }
        }

        if (
            isset($dto->track_batches) || isset($dto->track_lots)
            || isset($dto->track_serials) || isset($dto->track_expiry)
        ) {
            $product->configureTracking(
                batches: (bool) ($dto->track_batches ?? $product->isTrackBatches()),
                lots:    (bool) ($dto->track_lots ?? $product->isTrackLots()),
                serials: (bool) ($dto->track_serials ?? $product->isTrackSerials()),
                expiry:  (bool) ($dto->track_expiry ?? $product->isTrackExpiry()),
            );
        }

        $saved = $this->repository->save($product);

        Event::dispatch(new ProductUpdated($saved, ['type_was' => $previousType]));

        return $saved;
    }
}


// ═══════════════════════════════════════════════════════════════════
// DeleteProductService
// ═══════════════════════════════════════════════════════════════════
final class DeleteProductService extends BaseService implements DeleteProductServiceInterface
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
    ) {}

    protected function handle(array $data): mixed
    {
        $product = $this->repository->findById($data['id']);
        if ($product === null) {
            throw new ProductNotFoundException($data['id']);
        }

        $this->repository->delete($data['id']);
        Event::dispatch(new ProductDeleted($data['id'], $data['tenant_id']));

        return null;
    }
}


// ═══════════════════════════════════════════════════════════════════
// GetProductService
// ═══════════════════════════════════════════════════════════════════
final class GetProductService extends BaseService implements GetProductServiceInterface
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
    ) {}

    protected function handle(array $data): Product
    {
        $product = $this->repository->findById($data['id']);
        if ($product === null) {
            throw new ProductNotFoundException($data['id']);
        }
        return $product;
    }
}


// ═══════════════════════════════════════════════════════════════════
// ListProductsService
// ═══════════════════════════════════════════════════════════════════
final class ListProductsService extends BaseService implements ListProductsServiceInterface
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
    ) {}

    protected function handle(array $data): mixed
    {
        return $this->repository->findByTenant(
            tenantId: $data['tenant_id'],
            filters:  $data['filters'] ?? [],
            perPage:  $data['per_page'] ?? 25,
        );
    }
}
