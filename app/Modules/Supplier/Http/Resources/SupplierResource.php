<?php

declare(strict_types=1);

namespace Modules\Supplier\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            ...(new SupplierSummaryResource($this->resource))->resolve($request),
            'legal_name' => $this->legal_name,
            'website' => $this->website,
            'tax_registration_number' => $this->tax_registration_number,
            'vat_number' => $this->vat_number,
            'svat_number' => $this->svat_number,
            'business_registration_number' => $this->business_registration_number,
            'credit_limit' => (string) $this->credit_limit,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'approved_at' => $this->approved_at?->toISOString(),
            'contacts' => $this->whenLoaded('contacts', fn () => SupplierContactResource::collection($this->contacts)->resolve($request)),
            'addresses' => $this->whenLoaded('addresses', fn () => SupplierAddressResource::collection($this->addresses)->resolve($request)),
            'bank_accounts' => $this->whenLoaded('bankAccounts', fn () => SupplierBankAccountResource::collection($this->bankAccounts)->resolve($request)),
            'documents' => $this->whenLoaded('documents', fn () => SupplierDocumentResource::collection($this->documents)->resolve($request)),
            'item_mappings' => $this->whenLoaded('itemMappings', fn () => SupplierItemMappingResource::collection($this->itemMappings)->resolve($request)),
            'credit_profile' => $this->whenLoaded('creditProfile', fn () => $this->creditProfile
                ? (new SupplierCreditProfileResource($this->creditProfile))->resolve($request)
                : null),
            'status_history' => $this->whenLoaded('statusHistories', fn () => SupplierStatusHistoryResource::collection($this->statusHistories)->resolve($request)),
        ];
    }
}
