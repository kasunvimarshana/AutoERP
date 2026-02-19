<?php

declare(strict_types=1);

namespace Modules\CRM\Repositories;

use Modules\Core\Repositories\BaseRepository;
use Modules\CRM\Exceptions\ContactNotFoundException;
use Modules\CRM\Models\Contact;

class ContactRepository extends BaseRepository
{
    public function __construct(Contact $model)
    {
        parent::__construct($model);
    }

    protected function getModelClass(): string
    {
        return Contact::class;
    }

    protected function getNotFoundExceptionClass(): string
    {
        return ContactNotFoundException::class;
    }

    public function findByCustomerId(int $customerId): array
    {
        return $this->model->where('customer_id', $customerId)->get()->all();
    }

    public function findPrimaryContact(int $customerId): ?Contact
    {
        return $this->model
            ->where('customer_id', $customerId)
            ->where('is_primary', true)
            ->first();
    }

    public function findByEmail(string $email): ?Contact
    {
        return $this->model->where('email', $email)->first();
    }
}
