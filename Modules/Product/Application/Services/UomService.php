<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use Modules\Core\Domain\Contracts\ServiceContract;
use Modules\Product\Application\DTOs\AddUomConversionDTO;
use Modules\Product\Application\DTOs\CreateUomDTO;
use Modules\Product\Domain\Contracts\UomRepositoryContract;
use Modules\Product\Domain\Entities\UomConversion;

/**
 * UOM service.
 *
 * Orchestrates all unit-of-measure use cases.
 * All mutations are wrapped in DB::transaction to ensure atomicity.
 * No business logic in controllers — everything is delegated here.
 * All UOM conversion factors are stored as BCMath-safe strings — never float.
 */
class UomService implements ServiceContract
{
    public function __construct(
        private readonly UomRepositoryContract $uomRepository,
    ) {}

    /**
     * Return all UOMs for the current tenant.
     */
    public function listUoms(): Collection
    {
        return $this->uomRepository->all();
    }

    /**
     * Create a new unit of measure.
     */
    public function createUom(CreateUomDTO $dto): Model
    {
        return DB::transaction(function () use ($dto): Model {
            return $this->uomRepository->create([
                'name'      => $dto->name,
                'symbol'    => $dto->symbol,
                'is_active' => $dto->isActive,
            ]);
        });
    }

    /**
     * Show a single UOM by ID.
     */
    public function showUom(int|string $id): Model
    {
        return $this->uomRepository->findOrFail($id);
    }

    /**
     * Update an existing UOM.
     *
     * @param array<string, mixed> $data
     */
    public function updateUom(int|string $id, array $data): Model
    {
        return DB::transaction(function () use ($id, $data): Model {
            return $this->uomRepository->update($id, $data);
        });
    }

    /**
     * Delete a UOM.
     */
    public function deleteUom(int|string $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            return $this->uomRepository->delete($id);
        });
    }

    /**
     * Add a product-specific UOM conversion factor.
     *
     * The factor is stored as a BCMath-safe string.
     * Per AGENT.md: no implicit/global UOM conversions — product-specific factors only.
     */
    public function addConversion(AddUomConversionDTO $dto): UomConversion
    {
        return DB::transaction(function () use ($dto): UomConversion {
            /** @var UomConversion $conversion */
            $conversion = UomConversion::create([
                'product_id'  => $dto->productId,
                'from_uom_id' => $dto->fromUomId,
                'to_uom_id'   => $dto->toUomId,
                'factor'      => $dto->factor,
            ]);

            return $conversion;
        });
    }

    /**
     * List all UOM conversions for a given product.
     */
    public function listConversions(int $productId): Collection
    {
        return UomConversion::query()
            ->where('product_id', $productId)
            ->get();
    }

    /**
     * Show a single UOM conversion by ID.
     */
    public function showConversion(int|string $id): Model
    {
        /** @var Model $conversion */
        $conversion = UomConversion::query()->findOrFail($id);

        return $conversion;
    }

    /**
     * Delete a UOM conversion.
     */
    public function deleteConversion(int|string $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            /** @var \Illuminate\Database\Eloquent\Model $conversion */
            $conversion = UomConversion::query()->findOrFail($id);

            return (bool) $conversion->delete();
        });
    }
}
