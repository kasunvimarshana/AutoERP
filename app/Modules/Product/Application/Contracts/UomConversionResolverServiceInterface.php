<?php

declare(strict_types=1);

namespace Modules\Product\Application\Contracts;

interface UomConversionResolverServiceInterface
{
    /**
     * @return array{factor:string,path:array<int, int>}
     */
    public function resolveFactor(int $tenantId, ?int $productId, int $fromUomId, int $toUomId): array;

    /**
     * @return array{quantity:string,factor:string,path:array<int, int>,from_uom_id:int,to_uom_id:int}
     */
    public function convertQuantity(int $tenantId, ?int $productId, int $fromUomId, int $toUomId, string $quantity, int $scale = 6): array;

    /**
     * @return array{quantity:string,base_uom_id:int,factor:string,path:array<int, int>,from_uom_id:int}
     */
    public function normalizeToProductBase(int $tenantId, int $productId, int $fromUomId, string $quantity, int $scale = 6): array;
}
