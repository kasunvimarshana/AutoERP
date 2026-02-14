<?php

namespace App\Modules\CRM\Services;

use App\Core\Services\BaseService;
use App\Modules\CRM\Repositories\LeadRepository;
use App\Modules\Customer\Repositories\CustomerRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeadService extends BaseService
{
    protected CustomerRepository $customerRepository;

    /**
     * LeadService constructor
     */
    public function __construct(
        LeadRepository $repository,
        CustomerRepository $customerRepository
    ) {
        $this->repository = $repository;
        $this->customerRepository = $customerRepository;
    }

    /**
     * Convert lead to customer
     */
    public function convertToCustomer(int $leadId, array $additionalData = []): mixed
    {
        DB::beginTransaction();

        try {
            $lead = $this->repository->findOrFail($leadId);

            $customerData = array_merge([
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'company' => $lead->company,
                'address' => $lead->address,
                'is_active' => true,
            ], $additionalData);

            $customer = $this->customerRepository->create($customerData);

            $this->repository->update($leadId, [
                'status' => 'converted',
                'is_converted' => true,
                'converted_at' => now(),
                'customer_id' => $customer->id,
            ]);

            DB::commit();

            Log::info("Lead {$leadId} converted to customer {$customer->id}");

            return $customer;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error converting lead {$leadId} to customer: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Assign lead to user
     */
    public function assignTo(int $leadId, int $userId): bool
    {
        DB::beginTransaction();

        try {
            $result = $this->repository->update($leadId, [
                'assigned_to' => $userId,
                'assigned_at' => now(),
            ]);

            DB::commit();

            Log::info("Lead {$leadId} assigned to user {$userId}");

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error assigning lead {$leadId}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get leads by status
     */
    public function getByStatus(string $status)
    {
        try {
            return $this->repository->getByStatus($status);
        } catch (\Exception $e) {
            Log::error("Error fetching leads by status {$status}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get qualified leads
     */
    public function getQualified()
    {
        try {
            return $this->repository->getQualified();
        } catch (\Exception $e) {
            Log::error('Error fetching qualified leads: '.$e->getMessage());
            throw $e;
        }
    }
}
