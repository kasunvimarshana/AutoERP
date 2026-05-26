<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Invoice\Infrastructure\Persistence\Eloquent\Models\InvoiceLineModel;
use Modules\Invoice\Infrastructure\Persistence\Eloquent\Models\InvoiceModel;
use Modules\Invoice\Infrastructure\Persistence\Eloquent\Models\InvoiceReferenceModel;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\PaymentAllocationModel;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\PaymentModel;
use Modules\Purchase\Domain\Exceptions\ConcurrencyException;
use Modules\Purchase\Domain\Repositories\PurchaseAggregateRepositoryInterface;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\GrnHeaderModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\GrnLineModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderLineModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseReturnLineModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseReturnModel;

final class EloquentPurchaseAggregateRepository implements PurchaseAggregateRepositoryInterface
{
    /** @param array<string, mixed> $payload */
    public function createPurchaseOrder(array $payload): array
    {
        $lines = $payload['lines'] ?? [];
        unset($payload['lines']);

        $order = PurchaseOrderModel::query()->create($payload);
        $this->replacePurchaseOrderLines((int) $order->getKey(), is_array($lines) ? $lines : []);

        return $this->findPurchaseOrder((int) $order->getKey()) ?? [];
    }

    /** @param array<string, mixed> $payload */
    public function updatePurchaseOrder(int $id, array $payload, ?int $expectedVersion = null): array
    {
        $lines = $payload['lines'] ?? null;
        unset($payload['lines']);

        $order = PurchaseOrderModel::query()->lockForUpdate()->findOrFail($id);
        $this->assertVersion($order, $expectedVersion);
        $order->fill(array_merge($payload, ['row_version' => (int) $order->row_version + 1]));
        $order->save();

        if (is_array($lines)) {
            $this->replacePurchaseOrderLines($id, $lines);
        }

        return $this->findPurchaseOrder($id) ?? [];
    }

    public function findPurchaseOrderForUpdate(int $id): ?array
    {
        $order = PurchaseOrderModel::query()->with('lines')->lockForUpdate()->find($id);

        return $order instanceof PurchaseOrderModel ? $this->modelToArray($order) : null;
    }

    public function findPurchaseOrder(int $id): ?array
    {
        $order = PurchaseOrderModel::query()->with('lines')->find($id);

        return $order instanceof PurchaseOrderModel ? $this->modelToArray($order) : null;
    }

    /** @param array<string, mixed> $attributes */
    public function updatePurchaseOrderHeader(int $id, array $attributes): array
    {
        $order = PurchaseOrderModel::query()->lockForUpdate()->findOrFail($id);
        $order->fill(array_merge($attributes, ['row_version' => (int) $order->row_version + 1]));
        $order->save();

        return $this->findPurchaseOrder($id) ?? [];
    }

    /** @param array<int, array<string, mixed>> $lines */
    public function replacePurchaseOrderLines(int $purchaseOrderId, array $lines): void
    {
        PurchaseOrderLineModel::query()->where('purchase_order_id', $purchaseOrderId)->delete();

        foreach ($lines as $line) {
            PurchaseOrderLineModel::query()->create(array_merge($line, [
                'purchase_order_id' => $purchaseOrderId,
                'row_version' => $line['row_version'] ?? 1,
            ]));
        }
    }

    /** @param array<string, mixed> $payload */
    public function createGrn(array $payload): array
    {
        $lines = $payload['lines'] ?? [];
        unset($payload['lines']);

        $grn = GrnHeaderModel::query()->create($payload);
        foreach (is_array($lines) ? $lines : [] as $line) {
            GrnLineModel::query()->create(array_merge($line, [
                'grn_header_id' => (int) $grn->getKey(),
                'row_version' => $line['row_version'] ?? 1,
            ]));
        }

        return $this->modelToArray(GrnHeaderModel::query()->with('lines')->findOrFail((int) $grn->getKey()));
    }

    public function findGrnForUpdate(int $id): ?array
    {
        $grn = GrnHeaderModel::query()->with('lines')->lockForUpdate()->find($id);

        return $grn instanceof GrnHeaderModel ? $this->modelToArray($grn) : null;
    }

    /** @param array<string, mixed> $attributes */
    public function updateGrnHeader(int $id, array $attributes): array
    {
        $grn = GrnHeaderModel::query()->lockForUpdate()->findOrFail($id);
        $grn->fill(array_merge($attributes, ['row_version' => (int) $grn->row_version + 1]));
        $grn->save();

        return $this->modelToArray(GrnHeaderModel::query()->with('lines')->findOrFail($id));
    }

    /** @param array<string, mixed> $attributes */
    public function updatePurchaseOrderLine(int $id, array $attributes): array
    {
        $line = PurchaseOrderLineModel::query()->lockForUpdate()->findOrFail($id);
        $line->fill(array_merge($attributes, ['row_version' => (int) $line->row_version + 1]));
        $line->save();

        return $line->attributesToArray();
    }

    /** @param array<string, mixed> $payload */
    public function createPurchaseReturn(array $payload): array
    {
        $lines = $payload['lines'] ?? [];
        unset($payload['lines']);

        $purchaseReturn = PurchaseReturnModel::query()->create($payload);
        foreach (is_array($lines) ? $lines : [] as $line) {
            PurchaseReturnLineModel::query()->create(array_merge($line, [
                'purchase_return_id' => (int) $purchaseReturn->getKey(),
                'row_version' => $line['row_version'] ?? 1,
            ]));
        }

        return $this->modelToArray(PurchaseReturnModel::query()->with('lines')->findOrFail((int) $purchaseReturn->getKey()));
    }

    public function findPurchaseReturnForUpdate(int $id): ?array
    {
        $purchaseReturn = PurchaseReturnModel::query()->with('lines')->lockForUpdate()->find($id);

        return $purchaseReturn instanceof PurchaseReturnModel ? $this->modelToArray($purchaseReturn) : null;
    }

    /** @param array<string, mixed> $attributes */
    public function updatePurchaseReturnHeader(int $id, array $attributes): array
    {
        $purchaseReturn = PurchaseReturnModel::query()->lockForUpdate()->findOrFail($id);
        $purchaseReturn->fill(array_merge($attributes, ['row_version' => (int) $purchaseReturn->row_version + 1]));
        $purchaseReturn->save();

        return $this->modelToArray(PurchaseReturnModel::query()->with('lines')->findOrFail($id));
    }

    /** @param array<string, mixed> $payload */
    public function createPurchaseInvoice(array $payload): array
    {
        $lines = $payload['lines'] ?? [];
        $references = $payload['references'] ?? [];
        unset($payload['lines'], $payload['references']);

        $invoice = InvoiceModel::query()->create($payload);
        foreach (is_array($references) ? $references : [] as $reference) {
            InvoiceReferenceModel::query()->create(array_merge($reference, [
                'invoice_id' => (int) $invoice->getKey(),
                'tenant_id' => $payload['tenant_id'],
                'organization_unit_id' => $payload['organization_unit_id'] ?? null,
                'currency_id' => $payload['currency_id'] ?? null,
                'exchange_rate' => $payload['exchange_rate'] ?? 1,
                'row_version' => $reference['row_version'] ?? 1,
            ]));
        }

        foreach (is_array($lines) ? $lines : [] as $line) {
            InvoiceLineModel::query()->create(array_merge($line, [
                'invoice_id' => (int) $invoice->getKey(),
                'tenant_id' => $payload['tenant_id'],
                'organization_unit_id' => $payload['organization_unit_id'] ?? null,
                'row_version' => $line['row_version'] ?? 1,
            ]));
        }

        return $this->invoiceToArray((int) $invoice->getKey());
    }

    public function findInvoiceForUpdate(int $id): ?array
    {
        $invoice = InvoiceModel::query()->lockForUpdate()->find($id);
        if (! $invoice instanceof InvoiceModel) {
            return null;
        }

        return $this->invoiceToArray($id);
    }

    /** @param array<string, mixed> $attributes */
    public function updateInvoice(int $id, array $attributes): array
    {
        $invoice = InvoiceModel::query()->lockForUpdate()->findOrFail($id);
        $invoice->fill(array_merge($attributes, ['row_version' => (int) $invoice->row_version + 1]));
        $invoice->save();

        return $this->invoiceToArray($id);
    }

    /** @param array<string, mixed> $payload */
    public function createPayment(array $payload): array
    {
        $allocations = $payload['allocations'] ?? [];
        unset($payload['allocations']);

        $payment = PaymentModel::query()->create($payload);
        foreach (is_array($allocations) ? $allocations : [] as $allocation) {
            PaymentAllocationModel::query()->create(array_merge($allocation, [
                'payment_id' => (int) $payment->getKey(),
                'tenant_id' => $payload['tenant_id'],
                'organization_unit_id' => $payload['organization_unit_id'] ?? null,
                'row_version' => $allocation['row_version'] ?? 1,
            ]));
        }

        $payment->setRelation(
            'allocations',
            PaymentAllocationModel::query()->where('payment_id', (int) $payment->getKey())->get(),
        );

        return $this->modelToArray($payment);
    }

    private function assertVersion(Model $model, ?int $expectedVersion): void
    {
        if ($expectedVersion !== null && (int) $model->getAttribute('row_version') !== $expectedVersion) {
            throw new ConcurrencyException('The record was changed by another request.');
        }
    }

    /** @return array<string, mixed> */
    private function modelToArray(Model $model): array
    {
        return $model->toArray();
    }

    /** @return array<string, mixed> */
    private function invoiceToArray(int $id): array
    {
        $invoice = InvoiceModel::query()->findOrFail($id);
        $invoice->setRelation('lines', InvoiceLineModel::query()->where('invoice_id', $id)->get());
        $invoice->setRelation('references', InvoiceReferenceModel::query()->where('invoice_id', $id)->get());

        return $this->modelToArray($invoice);
    }
}
