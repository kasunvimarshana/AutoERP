<?php

namespace App\Modules\CRM\Repositories;

use App\Core\BaseRepository;
use App\Modules\CRM\Models\Contact;

class ContactRepository extends BaseRepository
{
    public function __construct(Contact $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?Contact
    {
        return $this->findBy('email', $email);
    }

    public function getByCustomer(int $customerId)
    {
        return $this->model->where('customer_id', $customerId)->get();
    }

    public function getPrimaryContact(int $customerId): ?Contact
    {
        return $this->model->where('customer_id', $customerId)
            ->where('is_primary', true)
            ->first();
    }

    public function getByTenant(int $tenantId)
    {
        return $this->model->where('tenant_id', $tenantId)->get();
    }
}
