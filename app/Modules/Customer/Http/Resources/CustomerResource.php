<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Customer\Enums\PreferredCommunicationChannel;

final class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            ...(new CustomerSummaryResource($this->resource))->resolve($request),
            'legal_name' => $this->legal_name,
            'website' => $this->website,
            'tax_registration_number' => $this->tax_registration_number,
            'vat_number' => $this->vat_number,
            'svat_number' => $this->svat_number,
            'business_registration_number' => $this->business_registration_number,
            'credit_limit' => (string) $this->credit_limit,
            'is_tax_exempt' => (bool) $this->is_tax_exempt,
            'marketing_consent' => (bool) $this->marketing_consent,
            'preferred_communication_channel' => $this->preferred_communication_channel instanceof PreferredCommunicationChannel
                ? $this->preferred_communication_channel->value
                : $this->preferred_communication_channel,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'approved_at' => $this->approved_at?->toISOString(),
            'contacts' => $this->whenLoaded('contacts', fn () => CustomerContactResource::collection($this->contacts)->resolve($request)),
            'addresses' => $this->whenLoaded('addresses', fn () => CustomerAddressResource::collection($this->addresses)->resolve($request)),
            'bank_accounts' => $this->whenLoaded('bankAccounts', fn () => CustomerBankAccountResource::collection($this->bankAccounts)->resolve($request)),
            'documents' => $this->whenLoaded('documents', fn () => CustomerDocumentResource::collection($this->documents)->resolve($request)),
            'credit_profile' => $this->whenLoaded('creditProfile', fn () => $this->creditProfile
                ? (new CustomerCreditProfileResource($this->creditProfile))->resolve($request)
                : null),
            'status_history' => $this->whenLoaded('statusHistories', fn () => CustomerStatusHistoryResource::collection($this->statusHistories)->resolve($request)),
        ];
    }
}
