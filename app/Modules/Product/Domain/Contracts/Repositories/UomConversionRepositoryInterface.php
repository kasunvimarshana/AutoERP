<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Contracts\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface UomConversionRepositoryInterface extends RepositoryInterface
{
    /**
     * Find a conversion rule between two UOMs, optionally scoped to a product.
     */
    public function findConversion(
        int $tenantId,
        string $fromUom,
        string $toUom,
        ?string $productId = null,
    ): mixed;

    /**
     * Return all active conversions for a given UOM within a tenant.
     */
    public function findByUom(int $tenantId, string $uom): Collection;
}
