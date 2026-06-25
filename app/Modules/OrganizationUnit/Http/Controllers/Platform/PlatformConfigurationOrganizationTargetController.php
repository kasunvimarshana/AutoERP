<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Http\Controllers\Platform;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\OrganizationUnit\Http\Requests\Platform\ListPlatformConfigurationOrganizationTargetsRequest;
use Modules\OrganizationUnit\Services\Platform\PlatformConfigurationOrganizationTargetService;

final class PlatformConfigurationOrganizationTargetController extends Controller
{
    public function __construct(private readonly PlatformConfigurationOrganizationTargetService $targets) {}

    public function index(
        ListPlatformConfigurationOrganizationTargetsRequest $request,
        int $tenant,
    ): JsonResponse {
        $page = $this->targets->page(
            $tenant,
            $request->validated('search'),
            $request->page(),
            $request->perPage(),
        );

        return response()->json([
            'data' => array_map(static fn ($organization): array => [
                'id' => (int) $organization->getKey(),
                'name' => (string) $organization->getAttribute('name'),
                'code' => (string) $organization->getAttribute('code'),
                'path' => (string) $organization->getAttribute('path'),
                'depth' => (int) $organization->getAttribute('depth'),
                'is_active' => (bool) $organization->getAttribute('is_active'),
            ], $page->items()),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => max(1, $page->lastPage()),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function show(int $tenant, int $organizationUnit): JsonResponse
    {
        $record = $this->targets->find($tenant, $organizationUnit);
        abort_if($record === null, 404, 'Organization-unit configuration target was not found.');

        return response()->json(['data' => [
            'id' => (int) $record->getKey(),
            'name' => (string) $record->getAttribute('name'),
            'code' => (string) $record->getAttribute('code'),
            'path' => (string) $record->getAttribute('path'),
            'depth' => (int) $record->getAttribute('depth'),
            'is_active' => (bool) $record->getAttribute('is_active'),
        ]]);
    }
}
