<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Illuminate\Database\Eloquent\Builder;
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
    public function __construct(
        private readonly AccountRoleAssignmentService $assignments,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $rules
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
        $profile ??= new FinancePostingProfile();
        $profile->forceFill([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'code' => $code,
            'name' => $name,
            'description' => $description,
            'is_active' => $isActive,
        ])->save();

        $profile->rules()->delete();
        foreach ($rules as $rule) {
            $lineKey = trim((string) ($rule['line_key'] ?? ''));
            $accountRoleId = (int) ($rule['account_role_id'] ?? 0);
            if ($lineKey === '' || $accountRoleId < 1) {
                throw new InvalidArgumentException('Posting profile rules require a line key and account role.');
            }
            FinancePostingProfileRule::query()->create([
                'tenant_id' => $tenantId,
                'posting_profile_id' => $profile->getKey(),
                'line_key' => $lineKey,
                'account_role_id' => $accountRoleId,
                'description' => $rule['description'] ?? null,
            ]);
        }

        return $profile->refresh()->load('rules.role');
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
        $profileKey = trim((string) $line->profileKey);
        if (! $profile instanceof FinancePostingProfile || $profileKey === '') {
            throw new InvalidArgumentException('Posting line requires a semantic posting profile mapping key.');
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
        $query->where(function (Builder $scope) use ($organizationUnitId): void {
            $scope->where('organization_unit_id', $organizationUnitId);
            if ($organizationUnitId !== null) {
                $scope->orWhereNull('organization_unit_id');
            }
        })->orderByRaw('organization_unit_id IS NULL ASC');
    }
}
