<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Audit\Constants\AuditEventCategory;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Core\Services\WhatsAppShareLinkService;
use Modules\Customer\Models\Customer;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\Invoice;

final class InvoiceWhatsAppShareService
{
    /** @var list<InvoiceStatus> */
    private const SHAREABLE_STATUSES = [
        InvoiceStatus::Posted,
        InvoiceStatus::PartiallyPaid,
        InvoiceStatus::Paid,
    ];

    public function __construct(
        private readonly WhatsAppShareLinkService $links,
        private readonly AuditRecorderInterface $audit,
    ) {}

    /** @return array{recipient:array{name:string, phone:string}, document_url:string, whatsapp_url:string, expires_at:string} */
    public function create(Invoice $invoice): array
    {
        $this->assertShareable($invoice);
        [$recipientName, $recipientPhone] = $this->recipient($invoice);
        $expiresAt = now()->addMinutes($this->linkTtlMinutes());
        $documentUrl = URL::temporarySignedRoute(
            'invoices.public.shared-pdf',
            $expiresAt,
            $this->routeParameters($invoice),
        );
        $message = strtr((string) config('document-sharing.whatsapp.invoice_message'), [
            '{recipient_name}' => $recipientName,
            '{document_number}' => trim((string) $invoice->invoice_number) ?: 'Invoice #'.$invoice->getKey(),
            '{document_url}' => $documentUrl,
        ]);
        $share = $this->links->create($recipientPhone, $message);

        $this->audit->record(new AuditEventData(
            eventName: 'invoice.whatsapp_share.opened',
            eventCategory: AuditEventCategory::WORKFLOW,
            sourceModule: 'invoice',
            subjectType: 'invoice',
            subjectId: (string) $invoice->getKey(),
            subjectReference: $invoice->invoice_number,
            metadata: [
                'tenant_id' => (int) $invoice->tenant_id,
                'organization_unit_id' => $invoice->organization_unit_id,
                'recipient_source' => 'customer_mobile',
                'link_expires_at' => $expiresAt->toISOString(),
            ],
            tags: ['invoice', 'whatsapp_share'],
            producerKey: 'invoice.whatsapp_share.opened:'.Str::uuid(),
        ));

        return [
            'recipient' => ['name' => $recipientName, 'phone' => $share['phone']],
            'document_url' => $documentUrl,
            'whatsapp_url' => $share['whatsapp_url'],
            'expires_at' => $expiresAt->toISOString(),
        ];
    }

    public function isShareable(Invoice $invoice): bool
    {
        $direction = $invoice->direction instanceof InvoiceDirection
            ? $invoice->direction
            : InvoiceDirection::tryFrom((string) $invoice->direction);
        $status = $invoice->status instanceof InvoiceStatus
            ? $invoice->status
            : InvoiceStatus::tryFrom((string) $invoice->status);

        return $direction === InvoiceDirection::Outbound
            && $invoice->party_type === 'customer'
            && $invoice->party_id !== null
            && $status !== null
            && in_array($status, self::SHAREABLE_STATUSES, true);
    }

    private function assertShareable(Invoice $invoice): void
    {
        if (! $this->isShareable($invoice)) {
            throw new InvalidArgumentException('Only posted, active customer invoices can be shared through WhatsApp.');
        }
    }

    /** @return array{string, string} */
    private function recipient(Invoice $invoice): array
    {
        $customer = Customer::query()
            ->with(['contacts' => static fn ($query) => $query
                ->where('is_active', true)
                ->whereNotNull('mobile')
                ->where('mobile', '<>', '')
                ->orderByDesc('is_primary')
                ->orderBy('contact_name')])
            ->where('tenant_id', $invoice->tenant_id)
            ->whereKey($invoice->party_id)
            ->first();
        if ($customer === null) {
            throw new InvalidArgumentException('The customer for this invoice is no longer available.');
        }

        $contact = $customer->contacts->first();
        $phone = trim((string) ($contact?->mobile ?? $customer->mobile));
        if ($phone === '') {
            throw new InvalidArgumentException('The customer does not have an active WhatsApp number.');
        }

        $name = trim((string) ($contact?->contact_name ?? $customer->display_name ?? $customer->name ?? $invoice->party_name_snapshot));

        return [$name === '' ? 'Customer' : $name, $phone];
    }

    /** @return array<string, int> */
    private function routeParameters(Invoice $invoice): array
    {
        $parameters = [
            'invoice' => (int) $invoice->getKey(),
            'tenant' => (int) $invoice->tenant_id,
        ];
        if ($invoice->organization_unit_id !== null) {
            $parameters['organization_unit'] = (int) $invoice->organization_unit_id;
        }

        return $parameters;
    }

    private function linkTtlMinutes(): int
    {
        return max(1, (int) config('document-sharing.whatsapp.link_ttl_minutes', 43200));
    }
}
