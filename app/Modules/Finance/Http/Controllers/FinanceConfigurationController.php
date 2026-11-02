<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Finance\Http\Requests\CreateAccountAssignmentRequest;
use Modules\Finance\Http\Requests\EndAccountAssignmentRequest;
use Modules\Finance\Http\Requests\ListFinanceRequest;
use Modules\Finance\Http\Requests\UpsertAccountRoleRequest;
use Modules\Finance\Http\Requests\UpsertPostingProfileRequest;
use Modules\Finance\Http\Resources\FinanceAccountAssignmentResource;
use Modules\Finance\Http\Resources\FinanceAccountRoleResource;
use Modules\Finance\Http\Resources\PostingProfileResource;
use Modules\Finance\Models\FinanceAccount;
use Modules\Finance\Models\FinanceAccountAssignment;
use Modules\Finance\Models\FinanceAccountCategory;
use Modules\Finance\Models\FinanceAccountRole;
use Modules\Finance\Models\FinanceAccountType;
use Modules\Finance\Models\FinanceDimension;
use Modules\Finance\Models\FinancePostingProfile;
use Modules\Finance\Services\AccountRoleAssignmentService;
use Modules\Finance\Services\PostingProfileService;

final class FinanceConfigurationController
{
    public function lookups(ListFinanceRequest $request): JsonResponse
    {
        $tenantId = $request->tenantId();
        $organizationUnitId = $request->organizationUnitId();

        $types = FinanceAccountType::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name', 'normal_balance', 'statement_type']);
        $categories = FinanceAccountCategory::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'account_type_id', 'code', 'name']);
        $accounts = $this->scopeOrganization(FinanceAccount::query()->where('tenant_id', $tenantId), $organizationUnitId)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'is_posting_account', 'is_active']);
        $profiles = $this->scopeOrganizationWithTenantFallback(
            FinancePostingProfile::query()->where('tenant_id', $tenantId),
            $organizationUnitId,
        )
            ->where('is_active', true)
            ->with('rules.role:id,code,name,is_active')
            ->with('organizationUnit:id,name,code')
            ->orderByRaw('organization_unit_id IS NULL ASC')
            ->orderBy('code')
            ->get();
        $accountRoles = FinanceAccountRole::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'is_active']);
        $assignments = FinanceAccountAssignment::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where(function (Builder $query) use ($organizationUnitId): void {
                $query->whereNull('organization_unit_id');
                if ($organizationUnitId !== null) {
                    $query->orWhere('organization_unit_id', $organizationUnitId);
                }
            })
            ->with(['role:id,code,name', 'account:id,code,name', 'organizationUnit:id,name,code'])
            ->orderByRaw('organization_unit_id IS NULL ASC')
            ->orderBy('account_role_id')
            ->orderByDesc('effective_from')
            ->get();
        $dimensions = $this->scopeOrganization(FinanceDimension::query()->where('tenant_id', $tenantId), $organizationUnitId)
            ->where('is_active', true)
            ->orderBy('dimension_type')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'dimension_type']);
        $bankAccounts = $this->scopeOrganization(FinanceAccount::query()->where('tenant_id', $tenantId), $organizationUnitId)
            ->where('is_bank_account', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'is_posting_account', 'is_active']);

        return response()->json(['data' => [
            'types' => $types,
            'categories' => $categories,
            'accounts' => $accounts,
            'profiles' => PostingProfileResource::collection($profiles)->resolve($request),
            'account_roles' => FinanceAccountRoleResource::collection($accountRoles)->resolve($request),
            'account_assignments' => FinanceAccountAssignmentResource::collection($assignments)->resolve($request),
            'dimensions' => $dimensions,
            'bank_accounts' => $bankAccounts,
        ]]);
    }

    public function postingProfiles(ListFinanceRequest $request): AnonymousResourceCollection
    {
        return PostingProfileResource::collection(
            $this->scopeOrganizationWithTenantFallback(
                FinancePostingProfile::query()->where('tenant_id', $request->tenantId()),
                $request->organizationUnitId(),
            )
                ->with('rules.role')
                ->with('organizationUnit')
                ->orderByRaw('organization_unit_id IS NULL ASC')
                ->orderBy('code')
                ->paginate($request->perPage()),
        );
    }

    public function createPostingProfile(
        UpsertPostingProfileRequest $request,
        PostingProfileService $service,
    ): PostingProfileResource {
        return new PostingProfileResource($service->save(
            $request->tenantId(),
            $request->organizationUnitId(),
            (string) $request->input('code'),
            (string) $request->input('name'),
            $request->filled('description') ? (string) $request->input('description') : null,
            $request->boolean('is_active', true),
            $request->input('rules'),
        ));
    }

    public function updatePostingProfile(
        UpsertPostingProfileRequest $request,
        int $profile,
        PostingProfileService $service,
    ): PostingProfileResource {
        $model = $this->scopeOrganization(
            FinancePostingProfile::query()->where('tenant_id', $request->tenantId()),
            $request->organizationUnitId(),
        )->findOrFail($profile);

        return new PostingProfileResource($service->save(
            $request->tenantId(),
            $request->organizationUnitId(),
            (string) $request->input('code'),
            (string) $request->input('name'),
            $request->filled('description') ? (string) $request->input('description') : null,
            $request->boolean('is_active', true),
            $request->input('rules'),
            $model,
            (int) $request->input('expected_version'),
        ));
    }

    public function accountRoles(ListFinanceRequest $request): AnonymousResourceCollection
    {
        return FinanceAccountRoleResource::collection(
            FinanceAccountRole::query()
                ->where('tenant_id', $request->tenantId())
                ->orderBy('code')
                ->paginate($request->perPage()),
        );
    }

    public function createAccountRole(
        UpsertAccountRoleRequest $request,
        AccountRoleAssignmentService $service,
    ): FinanceAccountRoleResource {
        return new FinanceAccountRoleResource($service->saveRole(
            $request->tenantId(),
            (string) $request->input('code'),
            (string) $request->input('name'),
            $request->filled('description') ? (string) $request->input('description') : null,
            $request->boolean('is_active', true),
        ));
    }

    public function updateAccountRole(
        UpsertAccountRoleRequest $request,
        int $role,
        AccountRoleAssignmentService $service,
    ): FinanceAccountRoleResource {
        $model = FinanceAccountRole::query()
            ->where('tenant_id', $request->tenantId())
            ->findOrFail($role);

        return new FinanceAccountRoleResource($service->saveRole(
            $request->tenantId(),
            (string) $request->input('code'),
            (string) $request->input('name'),
            $request->filled('description') ? (string) $request->input('description') : null,
            $request->boolean('is_active', true),
            $model,
        ));
    }

    public function accountAssignments(ListFinanceRequest $request): AnonymousResourceCollection
    {
        return FinanceAccountAssignmentResource::collection(
            FinanceAccountAssignment::query()
                ->where('tenant_id', $request->tenantId())
                ->where(function (Builder $query) use ($request): void {
                    $query->whereNull('organization_unit_id');
                    if ($request->organizationUnitId() !== null) {
                        $query->orWhere('organization_unit_id', $request->organizationUnitId());
                    }
                })
                ->with(['role', 'account', 'organizationUnit'])
                ->orderByRaw('organization_unit_id IS NULL ASC')
                ->orderBy('account_role_id')
                ->orderByDesc('effective_from')
                ->paginate($request->perPage()),
        );
    }

    public function createAccountAssignment(
        CreateAccountAssignmentRequest $request,
        AccountRoleAssignmentService $service,
    ): FinanceAccountAssignmentResource {
        return new FinanceAccountAssignmentResource($service->assign(
            $request->tenantId(),
            $request->organizationUnitId(),
            (int) $request->input('account_role_id'),
            (int) $request->input('account_id'),
            (string) $request->input('effective_from'),
            $request->filled('effective_to') ? (string) $request->input('effective_to') : null,
            $request->currentUserId(),
        ));
    }

    public function endAccountAssignment(
        EndAccountAssignmentRequest $request,
        int $assignment,
        AccountRoleAssignmentService $service,
    ): FinanceAccountAssignmentResource {
        $model = FinanceAccountAssignment::query()
            ->where('tenant_id', $request->tenantId())
            ->where('organization_unit_id', $request->organizationUnitId())
            ->findOrFail($assignment);

        return new FinanceAccountAssignmentResource($service->endAssignment(
            $model,
            (string) $request->input('effective_to'),
            $request->currentUserId(),
        ));
    }

    public function simulatePostingProfile(
        ListFinanceRequest $request,
        int $profile,
        AccountRoleAssignmentService $assignmentService,
    ): JsonResponse {
        $model = $this->scopeOrganizationWithTenantFallback(
            FinancePostingProfile::query()->where('tenant_id', $request->tenantId()),
            $request->organizationUnitId(),
        )->with('rules.role')->findOrFail($profile);

        $postingDate = trim((string) $request->input('posting_date', now()->toDateString()));
        if ($postingDate === '') {
            $postingDate = now()->toDateString();
        }
        $organizationUnitId = $request->organizationUnitId();

        $activeRules = $model->rules->filter(function ($rule) use ($postingDate): bool {
            if (! (bool) $rule->is_active) {
                return false;
            }
            $from = $rule->effective_from?->format('Y-m-d') ?? (string) $rule->getRawOriginal('effective_from');
            if ($from && strcmp($from, $postingDate) > 0) {
                return false;
            }
            $to = $rule->effective_to?->format('Y-m-d') ?? (string) $rule->getRawOriginal('effective_to');
            if ($to && strcmp($to, $postingDate) < 0) {
                return false;
            }

            return true;
        });

        $results = [];
        $hasErrors = false;

        foreach ($activeRules as $rule) {
            $role = $rule->role;
            if (! $role instanceof FinanceAccountRole || ! (bool) $role->is_active) {
                $hasErrors = true;
                $results[] = [
                    'line_key' => (string) $rule->line_key,
                    'account_role_id' => (int) $rule->account_role_id,
                    'role' => $role ? [
                        'id' => (int) $role->getKey(),
                        'code' => (string) $role->code,
                        'name' => (string) $role->name,
                        'is_active' => (bool) $role->is_active,
                    ] : null,
                    'status' => 'missing_role',
                    'resolved_account' => null,
                    'message' => 'Role is missing or inactive.',
                ];
                continue;
            }

            try {
                $account = $assignmentService->resolve(
                    $request->tenantId(),
                    $organizationUnitId,
                    $role,
                    $postingDate,
                );

                $results[] = [
                    'line_key' => (string) $rule->line_key,
                    'account_role_id' => (int) $role->getKey(),
                    'role' => [
                        'id' => (int) $role->getKey(),
                        'code' => (string) $role->code,
                        'name' => (string) $role->name,
                        'is_active' => (bool) $role->is_active,
                    ],
                    'status' => 'resolved',
                    'resolved_account' => [
                        'id' => (int) $account->getKey(),
                        'code' => (string) $account->code,
                        'name' => (string) $account->name,
                        'normal_balance' => (string) $account->normal_balance->value,
                        'scope' => $account->organization_unit_id === null ? 'tenant_default' : 'organization',
                        'organization_unit_id' => $account->organization_unit_id,
                    ],
                    'message' => null,
                ];
            } catch (\Throwable $e) {
                $hasErrors = true;
                $results[] = [
                    'line_key' => (string) $rule->line_key,
                    'account_role_id' => (int) $role->getKey(),
                    'role' => [
                        'id' => (int) $role->getKey(),
                        'code' => (string) $role->code,
                        'name' => (string) $role->name,
                        'is_active' => (bool) $role->is_active,
                    ],
                    'status' => 'error',
                    'resolved_account' => null,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'data' => [
                'profile_id' => (int) $model->getKey(),
                'profile_code' => (string) $model->code,
                'profile_name' => (string) $model->name,
                'posting_date' => $postingDate,
                'organization_unit_id' => $organizationUnitId,
                'is_ready' => ! $hasErrors && count($results) > 0,
                'lines' => $results,
            ],
        ]);
    }

    private function scopeOrganization(Builder $query, ?int $organizationUnitId): Builder
    {
        return $organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $organizationUnitId);
    }

    private function scopeOrganizationWithTenantFallback(Builder $query, ?int $organizationUnitId): Builder
    {
        return $query->where(function (Builder $scope) use ($organizationUnitId): void {
            $scope->whereNull('organization_unit_id');
            if ($organizationUnitId !== null) {
                $scope->orWhere('organization_unit_id', $organizationUnitId);
            }
        });
    }
}
