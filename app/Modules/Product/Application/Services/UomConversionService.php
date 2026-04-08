<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\UomConversionServiceInterface;
use Modules\Product\Domain\Contracts\Repositories\UomConversionRepositoryInterface;

class UomConversionService extends BaseService implements UomConversionServiceInterface
{
    public function __construct(UomConversionRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Default execute handler — creates a conversion rule.
     */
    protected function handle(array $data): mixed
    {
        return $this->upsertConversion($data);
    }

    /**
     * Convert a quantity from one UOM to another.
     *
     * Supports bidirectional lookup: if `from → to` is not found, attempts `to → from` (reciprocal).
     *
     * @throws InvalidArgumentException
     */
    public function convert(
        int $tenantId,
        float $quantity,
        string $fromUom,
        string $toUom,
        ?string $productId = null,
    ): float {
        if ($fromUom === $toUom) {
            return $quantity;
        }

        /** @var UomConversionRepositoryInterface $repo */
        $repo = $this->repository;

        // Try direct conversion
        $rule = $repo->findConversion($tenantId, $fromUom, $toUom, $productId);
        if ($rule !== null) {
            return round($quantity * (float) $rule->factor, 10);
        }

        // Try inverse conversion
        $inverseRule = $repo->findConversion($tenantId, $toUom, $fromUom, $productId);
        if ($inverseRule !== null) {
            $factor = (float) $inverseRule->factor;
            if (abs($factor) < PHP_FLOAT_EPSILON) {
                throw new InvalidArgumentException(
                    "UOM conversion factor for {$toUom} → {$fromUom} is zero."
                );
            }

            return round($quantity / $factor, 10);
        }

        throw new InvalidArgumentException(
            "No UOM conversion rule found for {$fromUom} → {$toUom} (tenant: {$tenantId})."
        );
    }

    /**
     * Create or update a UOM conversion rule (upsert by tenant + from_uom + to_uom + product_id).
     */
    public function upsertConversion(array $data): mixed
    {
        return DB::transaction(function () use ($data) {
            /** @var UomConversionRepositoryInterface $repo */
            $repo = $this->repository;

            $existing = $repo->findConversion(
                (int) $data['tenant_id'],
                $data['from_uom'],
                $data['to_uom'],
                $data['product_id'] ?? null,
            );

            if ($existing !== null) {
                return $repo->update($existing->id, $data);
            }

            return $repo->create($data);
        });
    }

    /**
     * List all active conversions for a UOM within a tenant.
     */
    public function listByUom(int $tenantId, string $uom): Collection
    {
        /** @var UomConversionRepositoryInterface $repo */
        $repo = $this->repository;

        return $repo->findByUom($tenantId, $uom);
    }
}
