<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Tenant\Http\Requests\ProvisionTenantRequest;
use Modules\Tenant\Http\Requests\ReplaceInitialAdministratorInvitationRequest;
use Modules\Tenant\Http\Requests\ResendInitialAdministratorInvitationRequest;
use Modules\Tenant\Http\Requests\RevokeInitialAdministratorInvitationRequest;
use Modules\Tenant\Http\Support\TenantApiResponder;
use Modules\Tenant\Services\Onboarding\TenantAdministratorInvitationService;
use Modules\Tenant\Services\Onboarding\TenantOnboardingService;
use Modules\Tenant\Services\Onboarding\TenantReadinessService;

final class TenantOnboardingController extends Controller
{
    public function __construct(
        private readonly TenantOnboardingService $onboarding,
        private readonly TenantReadinessService $readiness,
        private readonly TenantAdministratorInvitationService $administratorInvitations,
    ) {}

    public function provision(ProvisionTenantRequest $request, int|string $tenant): JsonResponse
    {
        $payload = $request->validated();
        $result = $this->onboarding->provision(
            $tenant,
            (int) $payload['expected_version'],
            (string) $payload['initial_admin_email'],
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
    public function invitation(int|string $tenant): JsonResponse
    {
        return response()->json([
            'data' => $this->administratorInvitations->inspect((int) $tenant),
        ]);
    }

    public function resendInvitation(
        ResendInitialAdministratorInvitationRequest $request,
        int|string $tenant,
        int $invitation,
    ): JsonResponse {
        return response()->json([
            'data' => $this->administratorInvitations->resend(
                (int) $tenant,
                $invitation,
                (int) $request->validated('expected_invitation_version'),
            ),
        ]);
    }

    public function revokeInvitation(
        RevokeInitialAdministratorInvitationRequest $request,
        int|string $tenant,
        int $invitation,
    ): JsonResponse {
        $validated = $request->validated();

        return response()->json([
            'data' => $this->administratorInvitations->revoke(
                (int) $tenant,
                $invitation,
                (int) $validated['expected_invitation_version'],
                (int) $validated['expected_onboarding_version'],
                (string) $validated['reason'],
            ),
        ]);
    }

    public function replaceInvitation(
        ReplaceInitialAdministratorInvitationRequest $request,
        int|string $tenant,
        int $invitation,
    ): JsonResponse {
        $validated = $request->validated();

        return response()->json([
            'data' => $this->administratorInvitations->replace(
                (int) $tenant,
                $invitation,
                (int) $validated['expected_invitation_version'],
                (int) $validated['expected_onboarding_version'],
                (string) $validated['email'],
                (string) $validated['reason'],
            ),
        ]);
    }

}
