<?php

declare(strict_types=1);

namespace Modules\Customer\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Modules\Core\Data\WhatsAppVerificationRecipient;
use Modules\Core\Enums\WhatsAppVerificationStatus;
use Modules\Core\Services\ManualWhatsAppVerificationService;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerWhatsAppVerification;

final class CustomerWhatsAppVerificationService extends ManualWhatsAppVerificationService
{
    /** @param Collection<int, Customer> $customers */
    public function attachListSummaries(Collection $customers): void
    {
        if ($customers->isEmpty()) {
            return;
        }

        $customers->load([
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

        $customers->each(function (Customer $customer): void {
            $state = $this->listState($customer, $customer->whatsappVerifications);
            $customer->setAttribute('whatsapp_contact', $state['recipient'] === null ? null : [...$state['recipient'], 'status' => $state['status']]);
        });
    }

    protected function verificationQuery(Model $party): Builder
    {
        $customer = $this->customer($party);

        return CustomerWhatsAppVerification::query()
            ->where('tenant_id', $customer->tenant_id)
            ->where('customer_id', $customer->getKey());
    }

    protected function lockParty(Model $party): Model
    {
        $customer = $this->customer($party);

        return Customer::query()
            ->where('tenant_id', $customer->tenant_id)
            ->whereKey($customer->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    protected function resolveRecipient(Model $party): WhatsAppVerificationRecipient
    {
        $customer = $this->customer($party);
        $contact = $customer->relationLoaded('contacts')
            ? $customer->contacts->first()
            : $customer->contacts()
                ->where('is_active', true)
                ->whereNotNull('mobile')
                ->where('mobile', '<>', '')
                ->orderByDesc('is_primary')
                ->orderBy('contact_name')
                ->first();
        $phone = trim((string) ($contact?->mobile ?? $customer->mobile));
        if ($phone === '') {
            throw new InvalidArgumentException('The customer does not have an active WhatsApp number.');
        }

        $name = trim((string) ($contact?->contact_name ?? $customer->display_name ?? $customer->name));

        return new WhatsAppVerificationRecipient(
            name: $name === '' ? 'Customer' : $name,
            phone: $phone,
            source: $contact === null ? 'customer_mobile' : 'customer_primary_contact_mobile',
            contactId: $contact === null ? null : (int) $contact->getKey(),
        );
    }

    protected function newVerification(
        Model $party,
        WhatsAppVerificationRecipient $recipient,
        array $attributes,
    ): Model {
        $customer = $this->customer($party);

        return new CustomerWhatsAppVerification([
            'tenant_id' => (int) $customer->tenant_id,
            'organization_unit_id' => $customer->organization_unit_id,
            'customer_id' => (int) $customer->getKey(),
            'customer_contact_id' => $recipient->contactId,
            ...$attributes,
        ]);
    }

    private function customer(Model $party): Customer
    {
        if (! $party instanceof Customer) {
            throw new InvalidArgumentException('Customer WhatsApp verification requires a customer.');
        }

        return $party;
    }
}
