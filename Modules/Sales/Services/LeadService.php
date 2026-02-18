<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Services\BaseService;
use Modules\Sales\Entities\Customer;
use Modules\Sales\Entities\Lead;
use Modules\Sales\Repositories\Contracts\CustomerRepositoryInterface;
use Modules\Sales\Repositories\Contracts\LeadRepositoryInterface;

/**
 * Lead Service
 *
 * Handles business logic for lead management operations including conversion to customers.
 */
class LeadService extends BaseService
{
    /**
     * LeadService constructor.
     */
    public function __construct(
        protected LeadRepositoryInterface $leadRepository,
        protected CustomerRepositoryInterface $customerRepository
    ) {}

    /**
     * Get paginated leads.
     */
    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->leadRepository->paginate($perPage);
    }

    /**
     * Create a new lead.
     */
    public function create(array $data): Lead
    {
        return $this->executeInTransaction(function () use ($data) {
            // Generate lead number if not provided
            if (empty($data['lead_number'])) {
                $data['lead_number'] = $this->generateLeadNumber();
            }

            // Set default status if not provided
            if (empty($data['status'])) {
                $data['status'] = 'new';
            }

            // Set default stage if not provided
            if (empty($data['stage'])) {
                $data['stage'] = 'initial';
            }

            $lead = $this->leadRepository->create($data);

            $this->logInfo('Lead created', [
                'lead_id' => $lead->id,
                'lead_number' => $lead->lead_number,
            ]);

            return $lead;
        });
    }

    /**
     * Update an existing lead.
     */
    public function update(int $id, array $data): Lead
    {
        return $this->executeInTransaction(function () use ($id, $data) {
            $lead = $this->leadRepository->update($id, $data);

            $this->logInfo('Lead updated', [
                'lead_id' => $lead->id,
            ]);

            return $lead;
        });
    }

    /**
     * Delete a lead.
     */
    public function delete(int $id): bool
    {
        $result = $this->leadRepository->delete($id);

        if ($result) {
            $this->logInfo('Lead deleted', [
                'lead_id' => $id,
            ]);
        }

        return $result;
    }

    /**
     * Find lead by ID.
     */
    public function findById(int $id): ?Lead
    {
        return $this->leadRepository->findById($id);
    }

    /**
     * Convert a lead to a customer.
     */
    public function convertToCustomer(int $leadId, ?array $customerData = null): Customer
    {
        return $this->executeInTransaction(function () use ($leadId, $customerData) {
            $lead = $this->leadRepository->findById($leadId);

            if (! $lead) {
                throw new \Exception("Lead with ID {$leadId} not found.");
            }

            if ($lead->customer_id) {
                throw new \Exception('Lead has already been converted to a customer.');
            }

            // Prepare customer data from lead if not provided
            if (! $customerData) {
                $customerData = [
                    'name' => $lead->contact_name ?? $lead->company,
                    'legal_name' => $lead->company,
                    'type' => 'company',
                    'email' => $lead->contact_email,
                    'phone' => $lead->contact_phone,
                    'currency' => 'USD',
                    'status' => 'active',
                ];
            }

            // Generate customer number if not provided
            if (empty($customerData['customer_number'])) {
                $customerData['customer_number'] = $this->generateCustomerNumber();
            }

            $customer = $this->customerRepository->create($customerData);

            // Update lead with customer reference and status
            $this->leadRepository->update($leadId, [
                'customer_id' => $customer->id,
                'status' => 'converted',
            ]);

            $this->logInfo('Lead converted to customer', [
                'lead_id' => $leadId,
                'customer_id' => $customer->id,
            ]);

            return $customer;
        });
    }

    /**
     * Get leads by status.
     */
    public function getLeadsByStatus(string $status): Collection
    {
        return $this->leadRepository->getLeadsByStatus($status);
    }

    /**
     * Get leads by stage.
     */
    public function getLeadsByStage(string $stage): Collection
    {
        return $this->leadRepository->getLeadsByStage($stage);
    }

    /**
     * Get leads assigned to a user.
     */
    public function getLeadsAssignedTo(int $userId): Collection
    {
        return $this->leadRepository->getLeadsAssignedTo($userId);
    }

    /**
     * Search leads.
     */
    public function search(string $query): Collection
    {
        return $this->leadRepository->search($query);
    }

    /**
     * Generate a unique lead number.
     */
    protected function generateLeadNumber(): string
    {
        $prefix = 'LEAD';
        $year = date('Y');

        // Get the last lead number for this year
        $lastLead = $this->leadRepository
            ->getModel()
            ->where('lead_number', 'LIKE', "{$prefix}-{$year}-%")
            ->orderBy('lead_number', 'desc')
            ->first();

        if ($lastLead) {
            // Extract the sequence number and increment
            $parts = explode('-', $lastLead->lead_number);
            $sequence = (int) end($parts) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s-%s-%05d', $prefix, $year, $sequence);
    }

    /**
     * Generate a unique customer number.
     */
    protected function generateCustomerNumber(): string
    {
        $prefix = 'CUST';
        $year = date('Y');

        // Get the last customer number for this year
        $lastCustomer = $this->customerRepository
            ->getModel()
            ->where('customer_number', 'LIKE', "{$prefix}-{$year}-%")
            ->orderBy('customer_number', 'desc')
            ->first();

        if ($lastCustomer) {
            // Extract the sequence number and increment
            $parts = explode('-', $lastCustomer->customer_number);
            $sequence = (int) end($parts) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s-%s-%05d', $prefix, $year, $sequence);
    }
}
