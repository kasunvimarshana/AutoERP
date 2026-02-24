<?php

namespace Modules\Inventory\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Domain\Contracts\ProductVariantRepositoryInterface;

class UpdateProductVariantUseCase
{
    public function __construct(
        private ProductVariantRepositoryInterface $variantRepo,
    ) {}

    public function execute(string $id, array $data): object
    {
        $variant = $this->variantRepo->findById($id);
        if (!$variant) {
            throw new DomainException('Product variant not found.');
        }

        // BCMath normalisation for decimal fields when provided
        if (isset($data['unit_price'])) {
            $data['unit_price'] = bcadd($data['unit_price'], '0', 8);
        }
        if (isset($data['cost_price'])) {
            $data['cost_price'] = bcadd($data['cost_price'], '0', 8);
        }

        return DB::transaction(fn () => $this->variantRepo->update($id, $data));
    }
}
