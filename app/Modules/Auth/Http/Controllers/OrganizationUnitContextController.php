<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\Exceptions\AuthFailure;
use Modules\Auth\Http\Requests\SwitchOrganizationUnitRequest;
use Modules\Auth\Http\Responses\AuthResponseFactory;
use Modules\Auth\Services\OrganizationUnit\SwitchOrganizationUnitService;

final class OrganizationUnitContextController extends Controller
{
    public function __construct(
        private readonly SwitchOrganizationUnitService $switcher,
        private readonly AuthResponseFactory $responses,
    ) {}

    public function switch(SwitchOrganizationUnitRequest $request): JsonResponse
    {
        try {
            return $this->responses->success([
                'data' => $this->switcher->execute((int) $request->validated('target_organization_unit_id')),
                'message' => 'Organization unit switched successfully.',
            ]);
        } catch (AuthFailure $exception) {
            return $this->responses->failure($exception);
        }
    }
}
