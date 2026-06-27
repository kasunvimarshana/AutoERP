<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Finance\DTOs\PostingContext;
use Modules\Finance\DTOs\PostingLine;
use Modules\Finance\Models\FinanceAccountAssignment;
use Modules\Finance\Models\FinanceAccountRole;
use Modules\Finance\Models\FinanceDimension;
use Modules\Finance\Models\FinancePostingProfile;
use Modules\Finance\Models\FinancePostingProfileRule;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class PostingProfileService
{
    public function __construct(private readonly AccountAssignmentService $assignments) {}

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
        ?int $expectedVersion = null,
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
            $expectedVersion,
        ): FinancePostingProfile {
            if ($profile instanceof FinancePostingProfile) {
                $profile = FinancePostingProfile::query()->lockForUpdate()->findOrFail($profile->getKey());
                if ($expectedVersion === null || (int) $profile->row_version !== $expectedVersion) {
                    throw new ConflictHttpException('Finance posting profile was changed by another request.');
                }
                if ((int) $profile->tenant_id !== $tenantId
                    || $profile->organization_unit_id !== $organizationUnitId) {
                    throw new InvalidArgumentException('Posting profile scope cannot be changed.');
                }
            } else {
                $profile = new FinancePostingProfile;
            }

            $code = trim($code);
            $scopeKey = $this->scopeKey($organizationUnitId);
            $duplicate = FinancePostingProfile::query()
                ->where('tenant_id', $tenantId)
                ->where('scope_key', $scopeKey)
                ->where('code', $code)
                ->when($profile->exists, fn (Builder $query) => $query->whereKeyNot($profile->getKey()))
                ->exists();
            if ($duplicate) {
                throw new InvalidArgumentException('Posting profile code already exists for this scope.');
            }

            $values = [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'scope_key' => $scopeKey,
                'code' => $code,
                'name' => trim($name),
                'description' => $description,
                'is_active' => $isActive,
            ];
            if ($profile->exists) {
                $values['row_version'] = (int) $profile->row_version + 1;
            }
            $profile->forceFill($values)->save();

            $profile->rules()->delete();
            foreach ($rules as $rule) {
                $role = FinanceAccountRole::query()->findOrFail((int) $rule['account_role_id']);
                if ((int) $role->tenant_id !== $tenantId || ! $role->is_active) {
                    throw new InvalidArgumentException('Posting profile account role belongs to a different tenant or is inactive.');
                }

                FinancePostingProfileRule::query()->forceCreate([
                    'tenant_id' => $tenantId,
                    'posting_profile_id' => $profile->getKey(),
                    'line_key' => trim($rule['line_key']),
                    'account_role_id' => $role->getKey(),
                    'description' => $rule['description'] ?? null,
                ]);
            }

            return $profile->refresh()->load('rules.accountRole');
        }, 3);
    }

    public function resolveProfile(PostingContext $request): FinancePostingProfile
    {
        $code = trim((string) $request->postingProfileCode);
        if ($code === '') {
            throw new InvalidArgumentException('Posting profile code is required for operational Finance posting.');
        }

        $organizationUnitId = $request->source->organizationUnitId;
        $query = FinancePostingProfile::query()
            ->with('rules.accountRole')
            ->where('tenant_id', $request->source->tenantId)
            ->where('code', $code)
            ->where('is_active', true)
            ->where(function (Builder $scope) use ($organizationUnitId): void {
                $scope->whereNull('organization_unit_id');
                if ($organizationUnitId !== null) {
                    $scope->orWhere('organization_unit_id', $organizationUnitId);
                }
            });
        if ($organizationUnitId !== null) {
            $query->orderByRaw('organization_unit_id = ? desc', [$organizationUnitId]);
        }

        $profile = $query->first();
        if (! $profile instanceof FinancePostingProfile) {
            throw new InvalidArgumentException("Posting profile [{$code}] is missing or inactive for this scope.");
        }

        return $profile;
    }

    public function resolveAssignment(
        PostingContext $request,
        PostingLine $line,
        FinancePostingProfile $profile,
    ): FinanceAccountAssignment {
        $profileKey = trim($line->profileKey);
        if ($profileKey === '') {
            throw new InvalidArgumentException('Posting line requires a posting profile mapping key.');
        }

        $rule = $profile->rules->firstWhere('line_key', $profileKey);
        $role = $rule?->accountRole;
        if (! $role instanceof FinanceAccountRole) {
            throw new InvalidArgumentException(
                "Posting profile [{$profile->code}] is missing account-role mapping [{$profileKey}].",
            );
        }

        return $this->assignments->resolve(
            tenantId: (int) $request->source->tenantId,
            organizationUnitId: $request->source->organizationUnitId,
            role: $role,
            postingDate: $request->postingDate,
            contextType: $line->contextType,
            contextId: $line->contextId,
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

    private function scopeKey(?int $organizationUnitId): string
    {
        return $organizationUnitId === null ? 'global' : 'ou:'.$organizationUnitId;
    }

    private function scopeOrganization(Builder $query, ?int $organizationUnitId): void
    {
        $organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $organizationUnitId);
    }
}
