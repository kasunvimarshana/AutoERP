<?php

namespace App\Presentation\Http\Resources;

use App\Domain\Order\Entities\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * OrderResource — transforms the Order domain entity into a JSON response.
 *
 * Never expose domain internals directly. All JSON shapes are defined here.
 *
 * @property Order $resource
 */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Order $order */
        $order = $this->resource;

        return [
            'id'          => $order->id()->value(),
            'customer_id' => $order->customerId(),
            'status'      => [
                'value' => $order->status()->value,
                'label' => $order->status()->label(),
            ],
            'total' => [
                'amount_cents' => $order->total()->amount(),
                'currency'     => $order->total()->currency(),
                'formatted'    => (string) $order->total(),
            ],
            'placed_at'  => $order->placedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
