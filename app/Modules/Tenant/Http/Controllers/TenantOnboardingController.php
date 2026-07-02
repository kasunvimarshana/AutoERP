<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Tenant\Http\Requests\ProvisionTenantRequest;
use Modules\Tenant\Http\Support\TenantApiResponder;
use Modules\Tenant\Services\Onboarding\TenantOnboardingService;
use Modules\Tenant\Services\Onboarding\TenantReadinessService;

final class TenantOnboardingController extends Controller
{
    public function __construct(
        private readonly TenantOnboardingService $onboarding,
        private readonly TenantReadinessService $readiness,
    ) {}

    public function provision(ProvisionTenantRequest $request, int|string $tenant): JsonResponse
    {
        $payload = $request->validated();
        $result = $this->onboarding->provision(
            $tenant,
            (int) $payload['expected_version'],
            (string) $payload['initial_admin_first_name'],
            isset($payload['initial_admin_last_name']) ? (string) $payload['initial_admin_last_name'] : null,
            (string) $payload['initial_admin_email'],
            (string) $payload['initial_admin_password'],
        );

        return $result->isFailure()
            ? TenantApiResponder::error($result->errorOrFail())
            : response()->json(['data' => $result->valueOrFail()]);
    }

    public function readiness(int|string $tenant): JsonResponse
    {
        return response()->json([
            'data' => $this->readiness->inspect((int) $tenant),
        ]);
    }

}
