<?php

declare(strict_types=1);

namespace Modules\CRM\Services;

use Modules\Core\Helpers\TransactionHelper;
use Modules\Core\Services\CodeGeneratorService;
use Modules\CRM\Enums\LeadStatus;
use Modules\CRM\Exceptions\InvalidLeadConversionException;
use Modules\CRM\Models\Customer;
use Modules\CRM\Models\Lead;
use Modules\CRM\Repositories\CustomerRepository;
use Modules\CRM\Repositories\LeadRepository;

class LeadConversionService
{
    public function __construct(
        private LeadRepository $leadRepository,
        private CustomerRepository $customerRepository,
        private CodeGeneratorService $codeGenerator
    ) {}

    /**
     * Convert a lead to a customer
     */
    public function convertToCustomer(int $leadId, array $customerData = []): Customer
    {
        $lead = $this->leadRepository->findOrFail($leadId);

        // Validate lead can be converted
        if ($lead->isConverted()) {
            throw new InvalidLeadConversionException('Lead has already been converted');
        }

        if (! in_array($lead->status, [LeadStatus::QUALIFIED, LeadStatus::WON])) {
            throw new InvalidLeadConversionException(
                'Lead must be in QUALIFIED or WON status to convert'
            );
        }

        // Convert using transaction
        return TransactionHelper::execute(function () use ($lead, $customerData) {
            // Create customer from lead data
            $customer = $this->customerRepository->create(array_merge([
                'tenant_id' => $lead->tenant_id,
                'organization_id' => $lead->organization_id,
                'customer_code' => $this->generateCustomerCode(),
                'customer_type' => $customerData['customer_type'] ?? 'individual',
                'status' => 'active',
                'company_name' => $lead->company_name,
                'phone' => $lead->phone,
                'email' => $lead->email,
                'website' => $lead->website,
                'billing_address_line1' => $lead->address_line1,
                'billing_address_line2' => $lead->address_line2,
                'billing_city' => $lead->city,
                'billing_state' => $lead->state,
                'billing_postal_code' => $lead->postal_code,
                'billing_country' => $lead->country,
            ], $customerData));

            // Update lead with conversion info
            $this->leadRepository->update($lead->id, [
                'status' => LeadStatus::WON,
                'converted_at' => now(),
                'converted_to_customer_id' => $customer->id,
            ]);

            return $customer;
        });
    }

    /**
     * Generate unique customer code
     */
    private function generateCustomerCode(): string
    {
        $prefix = config('crm.customer.code_prefix', 'CUST-');

        return $this->codeGenerator->generate(
            $prefix,
            fn (string $code) => $this->customerRepository->findByCode($code) !== null
        );
    }
}
