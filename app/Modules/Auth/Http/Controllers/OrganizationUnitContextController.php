<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Exceptions\DomainException;
use Modules\Auth\Http\Requests\SwitchOrganizationUnitRequest;
use Modules\Auth\Services\OrganizationUnit\SwitchOrganizationUnitService;
use Throwable;

final class OrganizationUnitContextController extends Controller
{
    public function __construct(private readonly SwitchOrganizationUnitService $switcher) {}

    public function switch(SwitchOrganizationUnitRequest $request): JsonResponse
    {
        try {
            $organizationUnit = $this->switcher->execute((int) $request->validated('target_organization_unit_id'));
            return response()->json([
                'data' => $organizationUnit->toArray(),
                'message' => 'Organization unit switched successfully.',
            ]);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json([
                'message' => $exception instanceof DomainException
                    ? $exception->getMessage()
                    : 'Organization unit could not be switched.',
                'code' => 'AUTH_ORGANIZATION_UNIT_SWITCH_FAILED',
            ], $exception instanceof DomainException ? 422 : 500);
        }
    }
}
