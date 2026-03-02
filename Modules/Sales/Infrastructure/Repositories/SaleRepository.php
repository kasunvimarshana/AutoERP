<?php
declare(strict_types=1);
namespace Modules\Sales\Infrastructure\Repositories;
use Modules\Sales\Domain\Contracts\SaleRepositoryInterface;
use Modules\Sales\Domain\Entities\Sale as SaleEntity;
use Modules\Sales\Domain\Enums\PaymentStatus;
use Modules\Sales\Domain\Enums\SaleStatus;
use Modules\Sales\Infrastructure\Models\Sale as SaleModel;
class SaleRepository implements SaleRepositoryInterface {
    public function findById(int $id, int $tenantId): ?SaleEntity {
        $m = SaleModel::withoutGlobalScope('tenant')->where('id',$id)->where('tenant_id',$tenantId)->first();
        return $m ? $this->toDomain($m) : null;
    }
    public function findByInvoiceNumber(string $invoiceNumber, int $tenantId): ?SaleEntity {
        $m = SaleModel::withoutGlobalScope('tenant')->where('invoice_number',$invoiceNumber)->where('tenant_id',$tenantId)->first();
        return $m ? $this->toDomain($m) : null;
    }
    public function findAll(int $tenantId, int $page = 1, int $perPage = 25): array {
        return SaleModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn(SaleModel $m): SaleEntity => $this->toDomain($m))
            ->all();
    }
    public function save(SaleEntity $sale): SaleEntity {
        $m = SaleModel::withoutGlobalScope('tenant')->updateOrCreate(
            ['id' => $sale->getId()],
            [
                'tenant_id'       => $sale->getTenantId(),
                'organisation_id' => $sale->getOrganisationId(),
                'invoice_number'  => $sale->getInvoiceNumber(),
                'customer_id'     => $sale->getCustomerId(),
                'status'          => $sale->getSaleStatus()->value,
                'payment_status'  => $sale->getPaymentStatus()->value,
                'subtotal'        => $sale->getSubtotal(),
                'discount_amount' => $sale->getDiscountAmount(),
                'tax_amount'      => $sale->getTaxAmount(),
                'total'           => $sale->getTotal(),
                'paid_amount'     => $sale->getPaidAmount(),
                'due_amount'      => $sale->getDueAmount(),
                'sale_date'       => $sale->getSaleDate(),
                'notes'           => $sale->getNotes(),
            ]
        );
        return $this->toDomain($m->fresh());
    }
    public function delete(int $id, int $tenantId): void {
        SaleModel::withoutGlobalScope('tenant')->where('id',$id)->where('tenant_id',$tenantId)->first()?->delete();
    }
    public function generateInvoiceNumber(int $tenantId, int $organisationId): string {
        $year  = date('Y');
        $month = date('m');
        $count = SaleModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('organisation_id', $organisationId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count() + 1;
        return sprintf('INV-%d-%s%s-%04d', $tenantId, $year, $month, $count);
    }
    private function toDomain(SaleModel $m): SaleEntity {
        return new SaleEntity(
            id: (int)$m->id,
            tenantId: (int)$m->tenant_id,
            organisationId: (int)$m->organisation_id,
            invoiceNumber: (string)$m->invoice_number,
            customerId: $m->customer_id ? (int)$m->customer_id : null,
            saleStatus: $m->status instanceof SaleStatus ? $m->status : SaleStatus::from((string)$m->status),
            paymentStatus: $m->payment_status instanceof PaymentStatus ? $m->payment_status : PaymentStatus::from((string)$m->payment_status),
            subtotal: (string)$m->subtotal,
            discountAmount: (string)$m->discount_amount,
            taxAmount: (string)$m->tax_amount,
            total: (string)$m->total,
            paidAmount: (string)$m->paid_amount,
            dueAmount: (string)$m->due_amount,
            saleDate: $m->sale_date?->toDateString(),
            dueDate: $m->due_date?->toDateString(),
            notes: $m->notes,
        );
    }
}
