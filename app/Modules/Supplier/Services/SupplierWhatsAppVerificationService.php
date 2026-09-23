<?php

declare(strict_types=1);

namespace Modules\Supplier\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Modules\Core\Data\WhatsAppVerificationRecipient;
use Modules\Core\Enums\WhatsAppVerificationStatus;
use Modules\Core\Services\ManualWhatsAppVerificationService;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Models\SupplierWhatsAppVerification;

final class SupplierWhatsAppVerificationService extends ManualWhatsAppVerificationService
{
    /** @param Collection<int, Supplier> $suppliers */
    public function attachListSummaries(Collection $suppliers): void
    {
        if ($suppliers->isEmpty()) {
            return;
        }

        $suppliers->load([
            'contacts' => static fn ($query) => $query
                ->where('is_active', true)
                ->whereNotNull('mobile')
                ->where('mobile', '<>', '')
                ->orderByDesc('is_primary')
                ->orderBy('contact_name'),
            'whatsappVerifications' => static fn ($query) => $query
                ->whereIn('status', [
                    WhatsAppVerificationStatus::Pending->value,
                    WhatsAppVerificationStatus::Verified->value,
                ]),
        ]);

        $suppliers->each(function (Supplier $supplier): void {
            $state = $this->listState($supplier, $supplier->whatsappVerifications);
            $supplier->setAttribute('whatsapp_contact', $state['recipient'] === null ? null : [...$state['recipient'], 'status' => $state['status']]);
        });
    }

    protected function verificationQuery(Model $party): Builder
    {
        $supplier = $this->supplier($party);

        return SupplierWhatsAppVerification::query()
            ->where('tenant_id', $supplier->tenant_id)
            ->where('supplier_id', $supplier->getKey());
    }

    protected function lockParty(Model $party): Model
    {
        $supplier = $this->supplier($party);

        return Supplier::query()
            ->where('tenant_id', $supplier->tenant_id)
            ->whereKey($supplier->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    protected function resolveRecipient(Model $party): WhatsAppVerificationRecipient
    {
        $supplier = $this->supplier($party);
        $contact = $supplier->relationLoaded('contacts')
            ? $supplier->contacts->first()
            : $supplier->contacts()
                ->where('is_active', true)
                ->whereNotNull('mobile')
                ->where('mobile', '<>', '')
                ->orderByDesc('is_primary')
                ->orderBy('contact_name')
                ->first();
        $phone = trim((string) ($contact?->mobile ?? $supplier->mobile));
        if ($phone === '') {
            throw new InvalidArgumentException('The supplier does not have an active WhatsApp number.');
        }

        $name = trim((string) ($contact?->contact_name ?? $supplier->display_name ?? $supplier->name));

        return new WhatsAppVerificationRecipient(
            name: $name === '' ? 'Supplier' : $name,
            phone: $phone,
            source: $contact === null ? 'supplier_mobile' : 'supplier_primary_contact_mobile',
            contactId: $contact === null ? null : (int) $contact->getKey(),
        );
    }

    protected function newVerification(
        Model $party,
        WhatsAppVerificationRecipient $recipient,
        array $attributes,
    ): Model {
        $supplier = $this->supplier($party);

        return new SupplierWhatsAppVerification([
            'tenant_id' => (int) $supplier->tenant_id,
            'organization_unit_id' => $supplier->organization_unit_id,
            'supplier_id' => (int) $supplier->getKey(),
            'supplier_contact_id' => $recipient->contactId,
            ...$attributes,
        ]);
    }

    private function supplier(Model $party): Supplier
    {
        if (! $party instanceof Supplier) {
            throw new InvalidArgumentException('Supplier WhatsApp verification requires a supplier.');
        }

        return $party;
    }
}
