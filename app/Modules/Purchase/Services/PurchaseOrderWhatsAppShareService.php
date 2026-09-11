<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Support\Facades\URL;
use InvalidArgumentException;
use Modules\Core\Services\WhatsAppShareLinkService;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Supplier\Models\Supplier;

final class PurchaseOrderWhatsAppShareService
{
    /** @var list<PurchaseOrderStatus> */
    private const SHAREABLE_STATUSES = [
        PurchaseOrderStatus::Approved,
        PurchaseOrderStatus::Closed,
    ];

    public function __construct(private readonly WhatsAppShareLinkService $links) {}

    /** @return array{recipient:array{name:string, phone:string}, document_url:string, whatsapp_url:string, expires_at:string} */
    public function create(PurchaseOrder $order): array
    {
        $this->assertShareable($order);
        [$recipientName, $recipientPhone] = $this->recipient($order);
        $expiresAt = now()->addMinutes($this->linkTtlMinutes());
        $documentUrl = URL::temporarySignedRoute(
            'purchase-orders.public.shared-pdf',
            $expiresAt,
            $this->routeParameters($order),
        );
        $message = strtr((string) config('document-sharing.whatsapp.purchase_order_message'), [
            '{recipient_name}' => $recipientName,
            '{document_number}' => trim((string) $order->purchase_order_number) ?: 'Purchase Order #'.$order->getKey(),
            '{document_url}' => $documentUrl,
        ]);
        $share = $this->links->create($recipientPhone, $message);

        return [
            'recipient' => ['name' => $recipientName, 'phone' => $share['phone']],
            'document_url' => $documentUrl,
            'whatsapp_url' => $share['whatsapp_url'],
            'expires_at' => $expiresAt->toISOString(),
        ];
    }

    public function isShareable(PurchaseOrder $order): bool
    {
        $status = $order->status instanceof PurchaseOrderStatus
            ? $order->status
            : PurchaseOrderStatus::tryFrom((string) $order->status);

        return $status !== null && in_array($status, self::SHAREABLE_STATUSES, true);
    }

    private function assertShareable(PurchaseOrder $order): void
    {
        if (! $this->isShareable($order)) {
            throw new InvalidArgumentException('Only approved or closed purchase orders can be shared through WhatsApp.');
        }
    }

    /** @return array{string, string} */
    private function recipient(PurchaseOrder $order): array
    {
        $supplier = Supplier::query()
            ->with(['contacts' => static fn ($query) => $query
                ->where('is_active', true)
                ->whereNotNull('mobile')
                ->where('mobile', '<>', '')
                ->orderByDesc('is_primary')
                ->orderBy('contact_name')])
            ->where('tenant_id', $order->tenant_id)
            ->whereKey($order->supplier_id)
            ->first();
        if ($supplier === null) {
            throw new InvalidArgumentException('The supplier for this purchase order is no longer available.');
        }

        $contact = $supplier->contacts->first();
        $phone = trim((string) ($contact?->mobile ?? $supplier->mobile));
        if ($phone === '') {
            throw new InvalidArgumentException('The supplier does not have an active WhatsApp number.');
        }

        $name = trim((string) ($contact?->contact_name ?? $supplier->display_name ?? $supplier->name));

        return [$name === '' ? 'Supplier' : $name, $phone];
    }

    /** @return array<string, int> */
    private function routeParameters(PurchaseOrder $order): array
    {
        $parameters = [
            'order' => (int) $order->getKey(),
            'tenant' => (int) $order->tenant_id,
        ];
        if ($order->organization_unit_id !== null) {
            $parameters['organization_unit'] = (int) $order->organization_unit_id;
        }

        return $parameters;
    }

    private function linkTtlMinutes(): int
    {
        return max(1, (int) config('document-sharing.whatsapp.link_ttl_minutes', 43200));
    }
}
