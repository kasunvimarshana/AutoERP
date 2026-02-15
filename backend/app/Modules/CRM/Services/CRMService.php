<?php

declare(strict_types=1);

namespace App\Modules\CRM\Services;

use App\Core\Services\BaseService;
use App\Modules\CRM\Repositories\CustomerRepositoryInterface;
use App\Modules\CRM\Repositories\ContactRepositoryInterface;
use App\Modules\CRM\Repositories\LeadRepositoryInterface;
use App\Core\Exceptions\ServiceException;
use Illuminate\Support\Facades\DB;

class CRMService extends BaseService
{
    /**
     * @var ContactRepositoryInterface
     */
    protected $contactRepository;

    /**
     * @var LeadRepositoryInterface
     */
    protected $leadRepository;

    /**
     * CRMService constructor.
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        ContactRepositoryInterface $contactRepository,
        LeadRepositoryInterface $leadRepository
    ) {
        parent::__construct($customerRepository);
        $this->contactRepository = $contactRepository;
        $this->leadRepository = $leadRepository;
    }

    /**
     * Create a new customer with addresses and contacts
     */
    public function createCustomer(array $data): mixed
    {
        DB::beginTransaction();

        try {
            // Generate customer code if not provided
            if (empty($data['code'])) {
                $data['code'] = $this->generateCustomerCode();
            }

            // Ensure code uniqueness
            if ($this->repository->findByCode($data['code'])) {
                throw new ServiceException('Customer code already exists');
            }

            // Extract addresses and contacts from data
            $addresses = $data['addresses'] ?? [];
            $contacts = $data['contacts'] ?? [];
            unset($data['addresses'], $data['contacts']);

            // Create customer
            $customer = $this->repository->create($data);

            // Create addresses if provided
            if (!empty($addresses)) {
                foreach ($addresses as $address) {
                    $address['customer_id'] = $customer->id;
                    $customer->addresses()->create($address);
                }
            }

            // Create contacts if provided
            if (!empty($contacts)) {
                foreach ($contacts as $contact) {
                    $contact['customer_id'] = $customer->id;
                    $contact['tenant_id'] = $customer->tenant_id;
                    $customer->contacts()->create($contact);
                }
            }

            DB::commit();

            return $customer->load(['addresses', 'contacts']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to create customer: ' . $e->getMessage());
        }
    }

    /**
     * Update customer with addresses and contacts
     */
    public function updateCustomer(int $id, array $data): mixed
    {
        DB::beginTransaction();

        try {
            $customer = $this->repository->findById($id);

            if (!$customer) {
                throw new ServiceException('Customer not found');
            }

            // Ensure code uniqueness if changed
            if (!empty($data['code']) && $data['code'] !== $customer->code) {
                $existing = $this->repository->findByCode($data['code']);
                if ($existing && $existing->id !== $id) {
                    throw new ServiceException('Customer code already exists');
                }
            }

            // Extract addresses and contacts from data
            unset($data['addresses'], $data['contacts']);

            // Update customer
            $this->repository->update($id, $data);

            DB::commit();

            return $this->repository->findById($id);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to update customer: ' . $e->getMessage());
        }
    }

    /**
     * Convert lead to customer
     */
    public function convertLead(int $leadId, array $customerData = []): mixed
    {
        DB::beginTransaction();

        try {
            $lead = $this->leadRepository->findById($leadId);

            if (!$lead) {
                throw new ServiceException('Lead not found');
            }

            if ($lead->is_converted) {
                throw new ServiceException('Lead is already converted');
            }

            // Prepare customer data from lead
            $data = array_merge([
                'tenant_id' => $lead->tenant_id,
                'organization_id' => $lead->organization_id,
                'branch_id' => $lead->branch_id,
                'type' => $lead->type,
                'code' => $this->generateCustomerCode(),
                'name' => $lead->full_name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'mobile' => $lead->mobile,
                'company_name' => $lead->company_name,
                'industry' => $lead->industry,
                'website' => $lead->website,
                'source' => $lead->source,
                'assigned_to' => $lead->assigned_to,
                'status' => 'active',
                'is_active' => true,
            ], $customerData);

            // Create customer
            $customer = $this->repository->create($data);

            // Create primary contact if lead has contact info
            if ($lead->first_name && $lead->last_name) {
                $this->contactRepository->create([
                    'tenant_id' => $lead->tenant_id,
                    'customer_id' => $customer->id,
                    'first_name' => $lead->first_name,
                    'last_name' => $lead->last_name,
                    'email' => $lead->email,
                    'phone' => $lead->phone,
                    'mobile' => $lead->mobile,
                    'designation' => $lead->designation,
                    'is_primary' => true,
                    'is_active' => true,
                ]);
            }

            // Update lead as converted
            $this->leadRepository->update($leadId, [
                'is_converted' => true,
                'converted_customer_id' => $customer->id,
                'converted_at' => now(),
                'converted_by' => auth()->id(),
                'status' => 'won',
            ]);

            DB::commit();

            return $customer->load(['contacts']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to convert lead: ' . $e->getMessage());
        }
    }

    /**
     * Create a new lead
     */
    public function createLead(array $data): mixed
    {
        DB::beginTransaction();

        try {
            // Generate lead code if not provided
            if (empty($data['code'])) {
                $data['code'] = $this->generateLeadCode();
            }

            // Ensure code uniqueness
            if ($this->leadRepository->findByCode($data['code'])) {
                throw new ServiceException('Lead code already exists');
            }

            // Create lead
            $lead = $this->leadRepository->create($data);

            DB::commit();

            return $lead;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to create lead: ' . $e->getMessage());
        }
    }

    /**
     * Update lead
     */
    public function updateLead(int $id, array $data): mixed
    {
        DB::beginTransaction();

        try {
            $lead = $this->leadRepository->findById($id);

            if (!$lead) {
                throw new ServiceException('Lead not found');
            }

            // Ensure code uniqueness if changed
            if (!empty($data['code']) && $data['code'] !== $lead->code) {
                $existing = $this->leadRepository->findByCode($data['code']);
                if ($existing && $existing->id !== $id) {
                    throw new ServiceException('Lead code already exists');
                }
            }

            // Update lead
            $this->leadRepository->update($id, $data);

            DB::commit();

            return $this->leadRepository->findById($id);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to update lead: ' . $e->getMessage());
        }
    }

    /**
     * Generate unique customer code
     */
    protected function generateCustomerCode(): string
    {
        $prefix = 'CUST';
        $timestamp = now()->format('Ymd');
        $random = str_pad((string) mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$timestamp}-{$random}";
    }

    /**
     * Generate unique lead code
     */
    protected function generateLeadCode(): string
    {
        $prefix = 'LEAD';
        $timestamp = now()->format('Ymd');
        $random = str_pad((string) mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$timestamp}-{$random}";
    }

    /**
     * Search customers
     */
    public function searchCustomers(string $query, array $filters = []): mixed
    {
        return $this->repository->search($query, $filters);
    }

    /**
     * Search leads
     */
    public function searchLeads(string $query, array $filters = []): mixed
    {
        return $this->leadRepository->search($query, $filters);
    }

    /**
     * Get customer statistics
     */
    public function getCustomerStatistics(): array
    {
        return [
            'total' => $this->repository->count(),
            'active' => $this->repository->count(['is_active' => true, 'status' => 'active']),
            'inactive' => $this->repository->count(['status' => 'inactive']),
            'vip' => $this->repository->count(['priority' => 'vip']),
            'individual' => $this->repository->count(['type' => 'individual']),
            'business' => $this->repository->count(['type' => 'business']),
        ];
    }

    /**
     * Get lead statistics
     */
    public function getLeadStatistics(): array
    {
        return [
            'total' => $this->leadRepository->count(),
            'new' => $this->leadRepository->count(['status' => 'new']),
            'qualified' => $this->leadRepository->count(['status' => 'qualified']),
            'won' => $this->leadRepository->count(['status' => 'won']),
            'lost' => $this->leadRepository->count(['status' => 'lost']),
            'converted' => $this->leadRepository->count(['is_converted' => true]),
        ];
    }
}
