<?php

declare(strict_types=1);

namespace Modules\Sales\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Application\Results\Result;
use Modules\Sales\Application\Contracts\Services\SalesLedgerNoteServiceInterface;

final class SalesLedgerNoteController extends Controller
{
    public function __construct(private readonly SalesLedgerNoteServiceInterface $notes) {}

    public function index(Request $request): JsonResponse
    {
        return $this->respond($this->notes->list($this->withContext($request)));
    }

    public function store(Request $request): JsonResponse
    {
        return $this->respond($this->notes->create($this->withContext($request)));
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        return $this->respond($this->notes->update($id, $this->withContext($request)));
    }

    public function destroy(Request $request, int|string $id): JsonResponse
    {
        return $this->respond($this->notes->delete($id, $this->withContext($request)));
    }

    /** @return array<string, mixed> */
    private function withContext(Request $request): array
    {
        $payload = $request->all();

        $tenantId = $request->attributes->get((string) config('core.current_tenant.id_attribute', 'current_tenant_id'));
        if (! isset($payload['tenant_id']) && is_int($tenantId)) {
            $payload['tenant_id'] = $tenantId;
        }

        $organizationUnitId = $request->attributes->get(
            (string) config('core.current_organization_unit.id_attribute', 'current_organization_unit_id')
        );
        if (! isset($payload['organization_unit_id']) && is_int($organizationUnitId)) {
            $payload['organization_unit_id'] = $organizationUnitId;
        }

        $currentUserId = $request->attributes->get(
            (string) config('core.current_user.id_attribute', 'current_user_id')
        );
        if (! isset($payload['created_by']) && is_int($currentUserId)) {
            $payload['created_by'] = $currentUserId;
        }
        if (! isset($payload['updated_by']) && is_int($currentUserId)) {
            $payload['updated_by'] = $currentUserId;
        }

        return $payload;
    }

    private function respond(Result $result): JsonResponse
    {
        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $statusCode = $error->code === 'SALES_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message, 'code' => $error->code], $statusCode);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }
}
