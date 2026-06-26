<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\Exceptions\AuthFailure;
use Modules\Auth\Http\Requests\SwitchOrganizationUnitRequest;
use Modules\Auth\Services\OrganizationUnit\SwitchOrganizationUnitService;

final class OrganizationUnitContextController extends Controller
{
    public function __construct(private readonly SwitchOrganizationUnitService $switcher) {}

    public function switch(SwitchOrganizationUnitRequest $request): JsonResponse
    {
        try {
            $response = response()->json([
                'data' => $this->switcher->execute((int) $request->validated('target_organization_unit_id')),
                'message' => 'Organization unit switched successfully.',
            ]);
            $response->headers->set('Cache-Control', 'no-store, private');
            return $response;
        } catch (AuthFailure $exception) {
            $response = response()->json([
                'message' => $exception->getMessage(),
                'code' => $exception->errorCode,
                'details' => $exception->details,
            ], $exception->httpStatus);
            $response->headers->set('Cache-Control', 'no-store, private');
            return $response;
        }
    }
}
