<?php
declare(strict_types=1);
namespace Modules\Procurement\Infrastructure\Repositories;
use Modules\Procurement\Domain\Contracts\PurchaseRepositoryInterface;
use Modules\Procurement\Domain\Entities\PurchaseOrder as POEntity;
use Modules\Procurement\Domain\Enums\PurchaseStatus;
use Modules\Procurement\Infrastructure\Models\PurchaseOrder as POModel;
class PurchaseRepository implements PurchaseRepositoryInterface {
    public function findById(int $id, int $tenantId): ?POEntity {
        $m = POModel::withoutGlobalScope('tenant')->where('id',$id)->where('tenant_id',$tenantId)->first();
        return $m ? $this->toDomain($m) : null;
    }
    public function findByPoNumber(string $poNumber, int $tenantId): ?POEntity {
        $m = POModel::withoutGlobalScope('tenant')->where('po_number',$poNumber)->where('tenant_id',$tenantId)->first();
        return $m ? $this->toDomain($m) : null;
    }
    public function save(POEntity $order): POEntity {
        $m = POModel::withoutGlobalScope('tenant')->updateOrCreate(
            ['id' => $order->getId()],
            [
                'tenant_id'  => $order->getTenantId(),
                'vendor_id'  => $order->getVendorId(),
                'po_number'  => $order->getPoNumber(),
                'status'     => $order->getStatus()->value,
                'subtotal'   => $order->getSubtotal(),
                'tax_amount' => $order->getTaxAmount(),
                'total'      => $order->getTotal(),
                'expected_delivery_date' => $order->getExpectedDeliveryDate(),
                'notes'      => $order->getNotes(),
            ]
        );
        return $this->toDomain($m->fresh());
    }
    public function delete(int $id, int $tenantId): void {
        POModel::withoutGlobalScope('tenant')->where('id',$id)->where('tenant_id',$tenantId)->first()?->delete();
    }
    public function generatePoNumber(int $tenantId): string {
        $year  = date('Y');
        $month = date('m');
        $count = POModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count() + 1;
        return sprintf('PO-%d-%s%s-%04d', $tenantId, $year, $month, $count);
    }
    private function toDomain(POModel $m): POEntity {
        return new POEntity(
            id: (int)$m->id,
            tenantId: (int)$m->tenant_id,
            vendorId: (int)$m->vendor_id,
            poNumber: (string)$m->po_number,
            status: $m->status instanceof PurchaseStatus ? $m->status : PurchaseStatus::from((string)$m->status),
            subtotal: (string)$m->subtotal,
            taxAmount: (string)$m->tax_amount,
            total: (string)$m->total,
            expectedDeliveryDate: $m->expected_delivery_date?->toDateString(),
            notes: $m->notes,
            createdBy: $m->created_by ? (int)$m->created_by : null,
        );
    }
}
