<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Finance\DTOs\PostingContext;
use Modules\Finance\DTOs\PostingLine;
use Modules\Finance\Models\FinanceAccount;
use Modules\Finance\Models\FinanceAccountRole;
use Modules\Finance\Models\FinanceDimension;
use Modules\Finance\Models\FinancePostingProfile;
use Modules\Finance\Models\FinancePostingProfileRule;

final class PostingProfileService
{
    public function __construct(private readonly AccountRoleAssignmentService $assignments) {}

    /**
     * @param  list<array{line_key: string, account_role_id: int, description?: string|null}>  $rules
     */
    public function save(
        int $tenantId,
        ?int $organizationUnitId,
        string $code,
        string $name,
        ?string $description,
        bool $isActive,
        array $rules,
        ?FinancePostingProfile $profile = null,
    ): FinancePostingProfile {
        return DB::transaction(function () use (
            $tenantId,
            $organizationUnitId,
            $code,
            $name,
            $description,
            $isActive,
            $rules,
            $profile,
        ): FinancePostingProfile {
            $profile ??= new FinancePostingProfile;
            if ($profile->exists
                && ((int) $profile->tenant_id !== $tenantId
                    || $profile->organization_unit_id !== $organizationUnitId)) {
                throw new InvalidArgumentException('Posting profile scope cannot be changed.');
            }

            $duplicate = FinancePostingProfile::query()
                ->where('tenant_id', $tenantId)
                ->where('code', trim($code))
                ->when($profile->exists, fn ($query) => $query->whereKeyNot($profile->getKey()))
                ->when(
                    $organizationUnitId === null,
                    fn ($query) => $query->whereNull('organization_unit_id'),
                    fn ($query) => $query->where('organization_unit_id', $organizationUnitId),
                )
                ->exists();
            if ($duplicate) {
                throw new InvalidArgumentException('Posting profile code already exists for this scope.');
            }

            $profile->forceFill([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'code' => trim($code),
                'name' => trim($name),
                'description' => $description,
                'is_active' => $isActive,
            ])->save();

            $profile->rules()->delete();
            foreach ($rules as $rule) {
                $role = FinanceAccountRole::query()->findOrFail((int) $rule['account_role_id']);
                if ((int) $role->tenant_id !== $tenantId || ! (bool) $role->is_active) {
                    throw new InvalidArgumentException('Posting profile account role belongs to a different tenant or is inactive.');
                }

                FinancePostingProfileRule::query()->create([
                    'tenant_id' => $tenantId,
                    'posting_profile_id' => $profile->getKey(),
                    'line_key' => trim($rule['line_key']),
                    'account_role_id' => $role->getKey(),
                    'description' => $rule['description'] ?? null,
                ]);
            }

            return $profile->refresh()->load('rules.role');
        });
    }

    public function resolveProfile(PostingContext $request): ?FinancePostingProfile
    {
        $code = trim((string) $request->postingProfileCode);
        if ($code === '') {
            return null;
        }

        $query = FinancePostingProfile::query()
            ->with('rules.role')
            ->where('tenant_id', $request->source->tenantId)
            ->where('code', $code)
            ->where('is_active', true);

        $this->scopeOrganization($query, $request->source->organizationUnitId);

        $profile = $query->first();
        if (! $profile instanceof FinancePostingProfile) {
            throw new InvalidArgumentException("Posting profile [{$code}] is missing or inactive for this scope.");
        }

        return $profile;
    }

    public function resolveAccount(
        PostingContext $request,
        PostingLine $line,
        ?FinancePostingProfile $profile = null,
    ): FinanceAccount {
        if (trim((string) $line->accountCode) !== '') {
            throw new InvalidArgumentException('Source postings cannot select Finance accounts by code; use a semantic posting profile key.');
        }

        $profileKey = trim((string) $line->profileKey);
        if (! $profile instanceof FinancePostingProfile || $profileKey === '') {
            throw new InvalidArgumentException('Posting line requires a posting profile mapping key.');
        }

        $rule = $profile->rules->firstWhere('line_key', $profileKey);
        $role = $rule?->role;
        if (! $role instanceof FinanceAccountRole) {
            throw new InvalidArgumentException(
                "Posting profile [{$profile->code}] is missing account role mapping [{$profileKey}].",
            );
        }

        return $this->assignments->resolve(
            (int) $request->source->tenantId,
            $request->source->organizationUnitId,
            $role,
            $request->postingDate,
        );
    }

    public function resolveDimension(PostingContext $request, ?string $dimensionCode): ?FinanceDimension
    {
        $code = trim((string) $dimensionCode);
        if ($code === '') {
            return null;
        }

        $query = FinanceDimension::query()
            ->where('tenant_id', $request->source->tenantId)
            ->where('code', $code)
            ->where('is_active', true);

        $this->scopeOrganization($query, $request->source->organizationUnitId);
        $dimension = $query->first();

        if (! $dimension instanceof FinanceDimension) {
            throw new InvalidArgumentException("Finance dimension [{$code}] is missing or inactive for this scope.");
        }

        return $dimension;
    }

    private function scopeOrganization($query, ?int $organizationUnitId): void
    {
        $organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $organizationUnitId);
    }
}
