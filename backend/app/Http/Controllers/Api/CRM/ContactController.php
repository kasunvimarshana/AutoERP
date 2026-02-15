<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\BaseController;
use App\Http\Resources\CRM\ContactResource;
use App\Modules\CRM\Repositories\ContactRepositoryInterface;
use App\Http\Requests\CRM\StoreContactRequest;
use App\Http\Requests\CRM\UpdateContactRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ContactController extends BaseController
{
    protected ContactRepositoryInterface $contactRepository;

    public function __construct(ContactRepositoryInterface $contactRepository)
    {
        $this->contactRepository = $contactRepository;
    }

    /**
     * Display a listing of contacts.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $customerId = $request->input('customer_id');
            
            if ($customerId) {
                $contacts = $this->contactRepository->getByCustomer($customerId);
            } else {
                $perPage = $request->input('per_page', 15);
                $contacts = $this->contactRepository->paginate($perPage);
            }

            return $this->successResponse(
                ContactResource::collection($contacts),
                'Contacts retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve contacts: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created contact.
     */
    public function store(StoreContactRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['tenant_id'] = auth()->user()->tenant_id;
            $validated['created_by'] = auth()->id();

            $contact = $this->contactRepository->create($validated);

            return $this->successResponse(
                new ContactResource($contact),
                'Contact created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create contact: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified contact.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $contact = $this->contactRepository->findById($id);

            if (!$contact) {
                return $this->errorResponse('Contact not found', [], 404);
            }

            $this->authorize('view', $contact);

            return $this->successResponse(
                new ContactResource($contact->load(['customer'])),
                'Contact retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve contact: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified contact.
     */
    public function update(UpdateContactRequest $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['updated_by'] = auth()->id();

            $this->contactRepository->update($id, $validated);
            $contact = $this->contactRepository->findById($id);

            return $this->successResponse(
                new ContactResource($contact),
                'Contact updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update contact: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified contact.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $contact = $this->contactRepository->findById($id);

            if (!$contact) {
                return $this->errorResponse('Contact not found', [], 404);
            }

            $this->authorize('delete', $contact);

            $this->contactRepository->delete($id);

            return $this->successResponse(null, 'Contact deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete contact: ' . $e->getMessage());
        }
    }

    /**
     * Search contacts.
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->input('q', '');
            $filters = $request->except(['q', 'page', 'per_page']);

            $contacts = $this->contactRepository->search($query, $filters);

            return $this->successResponse(
                ContactResource::collection($contacts),
                'Search results retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Search failed: ' . $e->getMessage());
        }
    }
}
