<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Modules\Sales\Models\SalesCreditNote;
use Modules\Sales\Models\SalesDelivery;
use Modules\Sales\Models\SalesInvoiceLink;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesReturn;

final class SalesRelatedDocumentService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function forSalesOrder(SalesOrder $order): array
    {
        $related = [];

        foreach (SalesDelivery::query()->where('sales_order_id', $order->getKey())->orderBy('id')->get() as $delivery) {
            $related[] = [
                'type' => 'sales_delivery',
                'id' => (int) $delivery->getKey(),
                'number' => $delivery->delivery_number,
                'status' => $this->statusValue($delivery->status),
            ];
        }

        foreach (SalesInvoiceLink::query()->where('source_type', 'sales_order')->where('source_id', $order->getKey())->orderBy('id')->get() as $link) {
            $related[] = [
                'type' => 'customer_invoice',
                'id' => (int) $link->invoice_id,
                'number' => null,
                'status' => $link->status,
            ];
        }

        foreach (SalesReturn::query()->where('source_type', 'sales_order')->where('source_id', $order->getKey())->orderBy('id')->get() as $return) {
            $related[] = [
                'type' => 'sales_return',
                'id' => (int) $return->getKey(),
                'number' => $return->return_number,
                'status' => $this->statusValue($return->status),
            ];
        }

        return $related;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forSalesDelivery(SalesDelivery $delivery): array
    {
        $related = [];

        foreach (SalesInvoiceLink::query()->where('source_type', 'sales_delivery')->where('source_id', $delivery->getKey())->orderBy('id')->get() as $link) {
            $related[] = [
                'type' => 'customer_invoice',
                'id' => (int) $link->invoice_id,
                'number' => null,
                'status' => $link->status,
            ];
        }

        foreach (SalesReturn::query()->where('source_type', 'sales_delivery')->where('source_id', $delivery->getKey())->orderBy('id')->get() as $return) {
            $related[] = [
                'type' => 'sales_return',
                'id' => (int) $return->getKey(),
                'number' => $return->return_number,
                'status' => $this->statusValue($return->status),
            ];
        }

        return $related;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forSalesReturn(SalesReturn $return): array
    {
        $related = [];
        if ($return->credit_note_id !== null) {
            $related[] = [
                'type' => 'sales_credit_note',
                'id' => (int) $return->credit_note_id,
                'number' => $return->creditNote?->credit_note_number,
                'status' => $return->creditNote === null ? null : $this->statusValue($return->creditNote->status),
            ];
        }

        return $related;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forCreditNote(SalesCreditNote $note): array
    {
        return $note->sales_return_id === null ? [] : [[
            'type' => 'sales_return',
            'id' => (int) $note->sales_return_id,
            'number' => $note->salesReturn?->return_number,
            'status' => $note->salesReturn === null ? null : $this->statusValue($note->salesReturn->status),
        ]];
    }

    private function statusValue(mixed $status): ?string
    {
        return $status instanceof \BackedEnum ? (string) $status->value : ($status === null ? null : (string) $status);
    }
}
