<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Lead;
use App\Models\Opportunity;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ContactService
{
    public function paginateContacts(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Contact::where('tenant_id', $tenantId);

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('first_name', 'like', '%'.$filters['search'].'%')
                    ->orWhere('last_name', 'like', '%'.$filters['search'].'%')
                    ->orWhere('email', 'like', '%'.$filters['search'].'%')
                    ->orWhere('company_name', 'like', '%'.$filters['search'].'%');
            });
        }

        return $query->orderBy('first_name')->paginate($perPage);
    }

    public function createContact(array $data): Contact
    {
        return Contact::create($data);
    }

    public function updateContact(string $id, array $data): Contact
    {
        $contact = Contact::findOrFail($id);
        $contact->update($data);

        return $contact->fresh();
    }

    public function deleteContact(string $id): void
    {
        Contact::findOrFail($id)->delete();
    }

    public function paginateLeads(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Lead::where('tenant_id', $tenantId)->with(['contact', 'assignedTo']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function createLead(array $data): Lead
    {
        return Lead::create($data);
    }

    public function convertLead(string $id): Lead
    {
        return DB::transaction(function () use ($id) {
            $lead = Lead::findOrFail($id);
            $lead->update([
                'status' => 'converted',
                'converted_at' => now(),
            ]);

            return $lead->fresh();
        });
    }

    public function paginateOpportunities(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Opportunity::where('tenant_id', $tenantId)->with(['contact', 'assignedTo']);

        if (isset($filters['stage'])) {
            $query->where('stage', $filters['stage']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function createOpportunity(array $data): Opportunity
    {
        return Opportunity::create($data);
    }
}
