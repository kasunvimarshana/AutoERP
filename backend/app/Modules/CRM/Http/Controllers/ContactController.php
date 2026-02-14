<?php

namespace App\Modules\CRM\Http\Controllers;

use App\Core\BaseController;
use App\Modules\CRM\Services\ContactService;
use App\Modules\CRM\Requests\CreateContactRequest;
use App\Modules\CRM\Requests\UpdateContactRequest;
use App\Modules\CRM\Resources\ContactResource;
use Illuminate\Http\JsonResponse;

/**
 * Contact Controller
 * 
 * Handles CRUD operations for contact management
 * 
 * @package App\Modules\CRM\Http\Controllers
 */
class ContactController extends BaseController
{
    protected ContactService $service;

    public function __construct(ContactService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of contacts
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $contacts = $this->service->getAllContacts();
        return $this->collection(ContactResource::collection($contacts));
    }

    /**
     * Store a newly created contact
     *
     * @param CreateContactRequest $request
     * @return JsonResponse
     */
    public function store(CreateContactRequest $request): JsonResponse
    {
        $contact = $this->service->createContact($request->validated());
        return $this->created(new ContactResource($contact));
    }

    /**
     * Display the specified contact
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $contact = $this->service->getContact($id);
        
        if (!$contact) {
            return $this->notFound();
        }
        
        return $this->resource(new ContactResource($contact));
    }

    /**
     * Update the specified contact
     *
     * @param UpdateContactRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateContactRequest $request, int $id): JsonResponse
    {
        $result = $this->service->updateContact($id, $request->validated());
        
        if (!$result) {
            return $this->notFound();
        }
        
        return $this->success(null, 'Contact updated successfully');
    }

    /**
     * Remove the specified contact
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->deleteContact($id);
        
        if (!$result) {
            return $this->notFound();
        }
        
        return $this->noContent();
    }
}
