<?php

declare(strict_types=1);

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Core\Http\Responses\ApiResponse;
use Modules\CRM\Http\Requests\StoreContactRequest;
use Modules\CRM\Http\Requests\UpdateContactRequest;
use Modules\CRM\Http\Resources\ContactResource;
use Modules\CRM\Models\Contact;
use Modules\CRM\Repositories\ContactRepository;

class ContactController extends Controller
{
    public function __construct(
        private ContactRepository $contactRepository
    ) {}

    /**
     * Display a listing of contacts.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Contact::class);

        $query = Contact::query()
            ->with(['customer']);

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('contact_type')) {
            $query->where('contact_type', $request->contact_type);
        }

        if ($request->has('is_primary')) {
            $query->where('is_primary', $request->boolean('is_primary'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $contacts = $query->paginate($perPage);

        return ApiResponse::paginated(
            $contacts->setCollection(
                $contacts->getCollection()->map(fn ($contact) => new ContactResource($contact))
            ),
            'Contacts retrieved successfully'
        );
    }

    /**
     * Store a newly created contact.
     */
    public function store(StoreContactRequest $request): JsonResponse
    {
        $this->authorize('create', Contact::class);

        $data = $request->validated();
        $data['tenant_id'] = $request->user()->currentTenant()->id;

        $contact = DB::transaction(function () use ($data) {
            // If this is marked as primary, unset other primary contacts for the customer
            if (! empty($data['is_primary'])) {
                Contact::where('customer_id', $data['customer_id'])
                    ->update(['is_primary' => false]);
            }

            return $this->contactRepository->create($data);
        });

        $contact->load(['customer']);

        return ApiResponse::created(
            new ContactResource($contact),
            'Contact created successfully'
        );
    }

    /**
     * Display the specified contact.
     */
    public function show(Contact $contact): JsonResponse
    {
        $this->authorize('view', $contact);

        $contact->load(['customer']);

        return ApiResponse::success(
            new ContactResource($contact),
            'Contact retrieved successfully'
        );
    }

    /**
     * Update the specified contact.
     */
    public function update(UpdateContactRequest $request, Contact $contact): JsonResponse
    {
        $this->authorize('update', $contact);

        $data = $request->validated();

        $contact = DB::transaction(function () use ($contact, $data) {
            // If this is marked as primary, unset other primary contacts for the customer
            if (! empty($data['is_primary'])) {
                Contact::where('customer_id', $contact->customer_id)
                    ->where('id', '!=', $contact->id)
                    ->update(['is_primary' => false]);
            }

            return $this->contactRepository->update($contact->id, $data);
        });

        $contact->load(['customer']);

        return ApiResponse::success(
            new ContactResource($contact),
            'Contact updated successfully'
        );
    }

    /**
     * Remove the specified contact.
     */
    public function destroy(Contact $contact): JsonResponse
    {
        $this->authorize('delete', $contact);

        DB::transaction(function () use ($contact) {
            $this->contactRepository->delete($contact->id);
        });

        return ApiResponse::success(
            null,
            'Contact deleted successfully'
        );
    }
}
