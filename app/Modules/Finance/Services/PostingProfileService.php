<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use DateTimeImmutable;
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
    private const OPEN_ENDED_EFFECTIVE_TO = '9999-12-31';

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

        $seen = [];
        foreach ($rules as $rule) {
            $lineKey = trim((string) ($rule['line_key'] ?? ''));
            $accountRoleId = (int) ($rule['account_role_id'] ?? 0);
            $effectiveFrom = $this->normalizeDate(
                $rule['effective_from'] ?? FinancePostingProfileRule::OPENING_EFFECTIVE_DATE,
                'Posting profile rule effective-from date',
            );
            $effectiveTo = $this->normalizeNullableDate(
                $rule['effective_to'] ?? null,
                'Posting profile rule effective-to date',
            );
            $ruleIsActive = (bool) ($rule['is_active'] ?? true);

            if ($lineKey === '' || $accountRoleId < 1) {
                throw new InvalidArgumentException('Posting profile rules require a line key and account role.');
            }
            if ($effectiveTo !== null && strcmp($effectiveTo, $effectiveFrom) < 0) {
                throw new InvalidArgumentException('Posting profile rule effective-to date cannot be before effective-from date.');
            }

            $duplicateKey = $lineKey.'|'.$effectiveFrom;
            if (isset($seen[$duplicateKey])) {
                throw new InvalidArgumentException("Posting profile rule [{$lineKey}] has duplicate effective-from date [{$effectiveFrom}].");
            }
            $seen[$duplicateKey] = true;

            $existing = FinancePostingProfileRule::query()
                ->where('tenant_id', $tenantId)
                ->where('posting_profile_id', $profile->getKey())
                ->where('line_key', $lineKey)
                ->whereDate('effective_from', $effectiveFrom)
                ->first();

            if ($ruleIsActive) {
                $this->assertNoOverlappingRule(
                    $tenantId,
                    (int) $profile->getKey(),
                    $lineKey,
                    $effectiveFrom,
                    $effectiveTo,
                    $existing?->getKey(),
                );
            }

            ($existing ?? new FinancePostingProfileRule())->forceFill([
                'tenant_id' => $tenantId,
                'posting_profile_id' => $profile->getKey(),
                'line_key' => $lineKey,
                'account_role_id' => $accountRoleId,
                'effective_from' => $effectiveFrom,
                'effective_to' => $effectiveTo,
                'is_active' => $ruleIsActive,
                'description' => $rule['description'] ?? null,
            ])->save();
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

        $rule = FinancePostingProfileRule::query()
            ->with('role')
            ->where('tenant_id', (int) $request->source->tenantId)
            ->where('posting_profile_id', $profile->getKey())
            ->where('line_key', $profileKey)
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $request->postingDate)
            ->where(function (Builder $query) use ($request): void {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $request->postingDate);
            })
            ->orderByDesc('effective_from')
            ->first();
        $role = $rule?->role;
        if (! $role instanceof FinanceAccountRole) {
            throw new InvalidArgumentException(
                "Posting profile [{$profile->code}] is missing account role mapping [{$profileKey}] for posting date [{$request->postingDate}].",
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

    private function assertNoOverlappingRule(
        int $tenantId,
        int $postingProfileId,
        string $lineKey,
        string $effectiveFrom,
        ?string $effectiveTo,
        ?int $ignoreRuleId = null,
    ): void {
        $query = FinancePostingProfileRule::query()
            ->where('tenant_id', $tenantId)
            ->where('posting_profile_id', $postingProfileId)
            ->where('line_key', $lineKey)
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $effectiveTo ?? self::OPEN_ENDED_EFFECTIVE_TO)
            ->where(function (Builder $query) use ($effectiveFrom): void {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $effectiveFrom);
            });

        if ($ignoreRuleId !== null) {
            $query->where('id', '!=', $ignoreRuleId);
        }

        if ($query->exists()) {
            throw new InvalidArgumentException("Posting profile rule [{$lineKey}] has overlapping effective periods.");
        }
    }

    private function normalizeNullableDate(mixed $value, string $label): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return $this->normalizeDate($value, $label);
    }

    private function normalizeDate(mixed $value, string $label): string
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', trim((string) $value));
        $errors = DateTimeImmutable::getLastErrors();
        $hasErrors = is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);
        if (! $date instanceof DateTimeImmutable || $hasErrors) {
            throw new InvalidArgumentException("{$label} must use YYYY-MM-DD format.");
        }

        return $date->format('Y-m-d');
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
