<?php

namespace App\Modules\CRM\Services;

use App\Core\BaseService;
use App\Modules\CRM\Repositories\ContactRepository;
use App\Modules\CRM\Events\ContactCreated;
use App\Modules\CRM\Events\ContactUpdated;

class ContactService extends BaseService
{
    protected ContactRepository $repository;

    public function __construct(ContactRepository $repository)
    {
        $this->repository = $repository;
    }

    public function createContact(array $data)
    {
        return $this->transaction(function () use ($data) {
            $contact = $this->repository->create($data);
            
            $this->logActivity('Contact created', ['contact_id' => $contact->id]);
            
            event(new ContactCreated($contact));
            
            return $contact;
        });
    }

    public function updateContact(int $id, array $data): bool
    {
        return $this->transaction(function () use ($id, $data) {
            $result = $this->repository->update($id, $data);
            
            if ($result) {
                $contact = $this->repository->find($id);
                
                $this->logActivity('Contact updated', ['contact_id' => $id]);
                
                event(new ContactUpdated($contact));
            }
            
            return $result;
        });
    }

    public function getContact(int $id)
    {
        return $this->repository->with(['customer', 'tenant'])->find($id);
    }

    public function getAllContacts()
    {
        return $this->repository->with(['customer'])->all();
    }

    public function deleteContact(int $id): bool
    {
        return $this->transaction(function () use ($id) {
            $result = $this->repository->delete($id);
            
            if ($result) {
                $this->logActivity('Contact deleted', ['contact_id' => $id]);
            }
            
            return $result;
        });
    }
}
