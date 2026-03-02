<?php

declare(strict_types=1);

namespace Modules\Sales\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sales\Domain\Contracts\ContactRepositoryInterface;
use Modules\Sales\Domain\Entities\Contact;
use Modules\Sales\Domain\Enums\ContactType;

/**
 * Customer/Supplier contact management controller.
 * Derived from PHP_POS ContactController reference.
 */
class ContactController extends Controller
{
    public function __construct(
        private readonly ContactRepositoryInterface $contacts,
    ) {}

    /**
     * GET /api/v1/contacts
     */
    public function index(Request $request): JsonResponse
    {
        $type    = $request->query('type') ?: null;
        $page    = (int) ($request->query('page', 1));
        $perPage = min((int) ($request->query('per_page', 25)), 100);

        $items = $this->contacts->findAll($type, $page, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Contacts retrieved successfully.',
            'data'    => array_map(fn (Contact $c) => $this->format($c), $items),
            'errors'  => null,
        ]);
    }

    /**
     * POST /api/v1/contacts
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type'            => 'required|string|in:customer,supplier,both',
            'name'            => 'required|string|max:255',
            'email'           => 'nullable|email|max:191',
            'phone'           => 'nullable|string|max:50',
            'tax_number'      => 'nullable|string|max:100',
            'opening_balance' => 'nullable|numeric',
        ]);

        $contact = new Contact(
            id: 0,
            tenantId: (int) $request->attributes->get('tenant_id'),
            type: ContactType::from($validated['type']),
            name: $validated['name'],
            email: $validated['email'] ?? null,
            phone: $validated['phone'] ?? null,
            taxNumber: $validated['tax_number'] ?? null,
            openingBalance: isset($validated['opening_balance']) ? bcadd((string) $validated['opening_balance'], '0', 4) : null,
            isActive: true,
        );

        $saved = $this->contacts->save($contact);

        return response()->json([
            'success' => true,
            'message' => 'Contact created successfully.',
            'data'    => $this->format($saved),
            'errors'  => null,
        ], 201);
    }

    /**
     * GET /api/v1/contacts/{id}
     */
    public function show(int $id): JsonResponse
    {
        $contact = $this->contacts->findById($id);

        if ($contact === null) {
            return response()->json(['success' => false, 'message' => 'Contact not found.', 'data' => null, 'errors' => null], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Contact retrieved successfully.',
            'data'    => $this->format($contact),
            'errors'  => null,
        ]);
    }

    /**
     * PUT /api/v1/contacts/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $contact = $this->contacts->findById($id);

        if ($contact === null) {
            return response()->json(['success' => false, 'message' => 'Contact not found.', 'data' => null, 'errors' => null], 404);
        }

        $validated = $request->validate([
            'type'            => 'sometimes|string|in:customer,supplier,both',
            'name'            => 'sometimes|string|max:255',
            'email'           => 'nullable|email|max:191',
            'phone'           => 'nullable|string|max:50',
            'tax_number'      => 'nullable|string|max:100',
            'opening_balance' => 'nullable|numeric',
            'is_active'       => 'sometimes|boolean',
        ]);

        $updated = new Contact(
            id: $contact->getId(),
            tenantId: $contact->getTenantId(),
            type: isset($validated['type']) ? ContactType::from($validated['type']) : $contact->getType(),
            name: $validated['name'] ?? $contact->getName(),
            email: array_key_exists('email', $validated) ? $validated['email'] : $contact->getEmail(),
            phone: array_key_exists('phone', $validated) ? $validated['phone'] : $contact->getPhone(),
            taxNumber: array_key_exists('tax_number', $validated) ? $validated['tax_number'] : $contact->getTaxNumber(),
            openingBalance: array_key_exists('opening_balance', $validated)
                ? ($validated['opening_balance'] !== null ? bcadd((string) $validated['opening_balance'], '0', 4) : null)
                : $contact->getOpeningBalance(),
            isActive: $validated['is_active'] ?? $contact->isActive(),
        );

        $saved = $this->contacts->save($updated);

        return response()->json([
            'success' => true,
            'message' => 'Contact updated successfully.',
            'data'    => $this->format($saved),
            'errors'  => null,
        ]);
    }

    /**
     * DELETE /api/v1/contacts/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        if ($this->contacts->findById($id) === null) {
            return response()->json(['success' => false, 'message' => 'Contact not found.', 'data' => null, 'errors' => null], 404);
        }

        $this->contacts->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Contact deleted successfully.',
            'data'    => null,
            'errors'  => null,
        ]);
    }

    private function format(Contact $c): array
    {
        return [
            'id'              => $c->getId(),
            'tenant_id'       => $c->getTenantId(),
            'type'            => $c->getType()->value,
            'name'            => $c->getName(),
            'email'           => $c->getEmail(),
            'phone'           => $c->getPhone(),
            'tax_number'      => $c->getTaxNumber(),
            'opening_balance' => $c->getOpeningBalance(),
            'is_active'       => $c->isActive(),
        ];
    }
}

