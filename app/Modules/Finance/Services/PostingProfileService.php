<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Finance\DTOs\PostingContext;
use Modules\Finance\DTOs\PostingLine;
use Modules\Finance\Models\FinanceAccount;
use Modules\Finance\Models\FinanceDimension;
use Modules\Finance\Models\FinancePostingProfile;
use Modules\Finance\Models\FinancePostingProfileRule;

final class PostingProfileService
{
    /**
     * @param  list<array{line_key: string, account_id: int, description?: string|null}>  $rules
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
                $account = FinanceAccount::query()->findOrFail((int) $rule['account_id']);
                if ((int) $account->tenant_id !== $tenantId
                    || $account->organization_unit_id !== $organizationUnitId) {
                    throw new InvalidArgumentException('Posting profile account mapping belongs to a different scope.');
                }

                FinancePostingProfileRule::query()->create([
                    'posting_profile_id' => $profile->getKey(),
                    'line_key' => trim($rule['line_key']),
                    'account_id' => $account->getKey(),
                    'description' => $rule['description'] ?? null,
                ]);
            }

            return $profile->refresh()->load('rules.account');
        });
    }

    public function resolveProfile(PostingContext $request): ?FinancePostingProfile
    {
        $code = trim((string) $request->postingProfileCode);
        if ($code === '') {
            return null;
        }

        $query = FinancePostingProfile::query()
            ->with('rules.account')
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
        $accountCode = trim((string) $line->accountCode);
        if ($accountCode !== '') {
            $query = FinanceAccount::query()
                ->where('tenant_id', $request->source->tenantId)
                ->where('code', $accountCode);

            $this->scopeOrganization($query, $request->source->organizationUnitId);
            $account = $query->first();

            if (! $account instanceof FinanceAccount) {
                throw new InvalidArgumentException("Finance account [{$accountCode}] is missing for this scope.");
            }

            return $account;
        }

        $profileKey = trim((string) $line->profileKey);
        if (! $profile instanceof FinancePostingProfile || $profileKey === '') {
            throw new InvalidArgumentException('Posting line requires an account code or posting profile mapping key.');
        }

        $rule = $profile->rules->firstWhere('line_key', $profileKey);
        $account = $rule?->account;
        if (! $account instanceof FinanceAccount) {
            throw new InvalidArgumentException(
                "Posting profile [{$profile->code}] is missing account mapping [{$profileKey}].",
            );
        }

        if ((int) $account->tenant_id !== (int) $request->source->tenantId
            || $account->organization_unit_id !== $request->source->organizationUnitId) {
            throw new InvalidArgumentException('Posting profile account mapping belongs to a different scope.');
        }

        return $account;
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
