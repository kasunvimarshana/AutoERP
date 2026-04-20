<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Purchase\Domain\Entities\PurchaseInvoiceLine;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseInvoiceLineRepositoryInterface;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseInvoiceLineModel;

class EloquentPurchaseInvoiceLineRepository extends EloquentRepository implements PurchaseInvoiceLineRepositoryInterface
{
    public function __construct(PurchaseInvoiceLineModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (PurchaseInvoiceLineModel $m): PurchaseInvoiceLine => $this->mapToDomain($m));
    }

    public function save(PurchaseInvoiceLine $entity): PurchaseInvoiceLine
    {
        $data = [
            'tenant_id' => $entity->getTenantId(),
            'purchase_invoice_id' => $entity->getPurchaseInvoiceId(),
            'grn_line_id' => $entity->getGrnLineId(),
            'product_id' => $entity->getProductId(),
            'variant_id' => $entity->getVariantId(),
            'description' => $entity->getDescription(),
            'uom_id' => $entity->getUomId(),
            'quantity' => $entity->getQuantity(),
            'unit_price' => $entity->getUnitPrice(),
            'discount_pct' => $entity->getDiscountPct(),
            'tax_group_id' => $entity->getTaxGroupId(),
            'tax_amount' => $entity->getTaxAmount(),
            'line_total' => $entity->getLineTotal(),
            'account_id' => $entity->getAccountId(),
        ];

        if ($entity->getId()) {
            $model = $this->update($entity->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        return $this->toDomainEntity($model);
    }

    public function find(int|string $id, array $columns = ['*']): ?PurchaseInvoiceLine
    {
        return parent::find($id, $columns);
    }

    private function mapToDomain(PurchaseInvoiceLineModel $m): PurchaseInvoiceLine
    {
        return new PurchaseInvoiceLine(
            tenantId: (int) $m->tenant_id,
            purchaseInvoiceId: (int) $m->purchase_invoice_id,
            productId: (int) $m->product_id,
            uomId: (int) $m->uom_id,
            quantity: (string) $m->quantity,
            unitPrice: (string) $m->unit_price,
            lineTotal: (string) $m->line_total,
            discountPct: (string) $m->discount_pct,
            taxAmount: (string) $m->tax_amount,
            grnLineId: $m->grn_line_id !== null ? (int) $m->grn_line_id : null,
            variantId: $m->variant_id !== null ? (int) $m->variant_id : null,
            description: $m->description !== null ? (string) $m->description : null,
            taxGroupId: $m->tax_group_id !== null ? (int) $m->tax_group_id : null,
            accountId: $m->account_id !== null ? (int) $m->account_id : null,
            id: (int) $m->id,
            createdAt: $m->created_at,
            updatedAt: $m->updated_at,
        );
    }
}
