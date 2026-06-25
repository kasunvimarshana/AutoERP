<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Controllers\Platform;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Tenant\Http\Requests\Platform\ListPlatformTenantTargetsRequest;
use Modules\Tenant\Services\Platform\PlatformTenantTargetService;

final class PlatformTenantTargetController extends Controller
{
    public function __construct(private readonly PlatformTenantTargetService $targets) {}

    public function index(ListPlatformTenantTargetsRequest $request): JsonResponse
    {
        $page = $this->targets->page(
            $request->validated('search'),
            $request->page(),
            $request->perPage(),
        );

        return response()->json([
            'data' => array_map(static fn ($tenant): array => [
                'id' => (int) $tenant->getKey(),
                'name' => (string) $tenant->getAttribute('name'),
                'code' => (string) $tenant->getAttribute('code'),
                'status' => (string) $tenant->getAttribute('status'),
            ], $page->items()),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => max(1, $page->lastPage()),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function show(int $tenant): JsonResponse
    {
        $record = $this->targets->find($tenant);
        abort_if($record === null, 404, 'Tenant configuration target was not found.');

        return response()->json(['data' => [
            'id' => (int) $record->getKey(),
            'name' => (string) $record->getAttribute('name'),
            'code' => (string) $record->getAttribute('code'),
            'status' => (string) $record->getAttribute('status'),
        ]]);
    }
}
