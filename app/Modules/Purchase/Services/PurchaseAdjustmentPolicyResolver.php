<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Validation\ValidationException;
use Modules\Finance\Models\FinanceAccount;
use Modules\Finance\Models\FinancePostingProfile;
use Modules\Purchase\DTOs\PurchaseHeaderAdjustmentData;
use Modules\Purchase\Enums\PurchaseAdjustmentType;
use Modules\Purchase\Models\PurchaseHeaderAdjustment;

final class PurchaseAdjustmentPolicyResolver
{
    public function __construct(
        private readonly PurchaseAdjustmentCatalogueService $catalogue,
        private readonly PurchaseAuthorizationService $authorization,
    ) {}

    /**
     * @return array{cost_treatment: string, tax_treatment: string, mapping_source: string, finance_posting_profile_id: int|null, finance_account_id: int|null, final_treatment: string, profile_key: string|null}
     */
    public function resolveForData(
        PurchaseHeaderAdjustmentData $data,
        int $tenantId,
        ?int $organizationUnitId,
        string $fieldPrefix,
        ?int $userId = null,
        bool $lockReferences = false,
    ): array {
        $defaults = $this->catalogue->defaultsFor($data->adjustmentType);
        $overrideRequested = $this->overrideRequested($data, $defaults);

        if ($overrideRequested) {
            $this->assertOverrideAllowed($data->adjustmentType, $defaults, $tenantId, $userId, $fieldPrefix);
        }

        $costTreatment = $overrideRequested ? (string) ($data->costTreatment ?? $defaults['cost_treatment']) : (string) $defaults['cost_treatment'];
        $taxTreatment = $overrideRequested ? (string) ($data->taxTreatment ?? $defaults['tax_treatment']) : (string) $defaults['tax_treatment'];
        $mappingSource = $overrideRequested ? 'override' : 'catalogue';
        $finalTreatment = $this->finalTreatment($costTreatment, $taxTreatment, $data->adjustmentType, (bool) $data->isAllocatable);
        $profileKey = $this->profileKeyForTreatment($finalTreatment, $taxTreatment);

        $accountId = $data->financeAccountId;
        $profileId = $data->financePostingProfileId;
        if ($profileId !== null) {
            $profileAccount = $this->accountFromProfile($profileId, $profileKey, $tenantId, $organizationUnitId, $lockReferences, "{$fieldPrefix}.finance_posting_profile_id");
            if ($accountId !== null && (int) $profileAccount->getKey() !== $accountId) {
                throw ValidationException::withMessages([
                    "{$fieldPrefix}.finance_account_id" => ['Finance account conflicts with the selected posting profile mapping.'],
                ]);
            }
            $accountId = (int) $profileAccount->getKey();
        } elseif ($accountId !== null) {
            $this->account($accountId, $tenantId, $organizationUnitId, $lockReferences, "{$fieldPrefix}.finance_account_id");
        }

        return [
            'cost_treatment' => $costTreatment,
            'tax_treatment' => $taxTreatment,
            'mapping_source' => $mappingSource,
            'finance_posting_profile_id' => $profileId,
            'finance_account_id' => $accountId,
            'final_treatment' => $finalTreatment,
            'profile_key' => $profileKey,
        ];
    }

    /**
     * @return array{cost_treatment: string, tax_treatment: string, mapping_source: string, finance_posting_profile_id: int|null, finance_account_id: int|null, final_treatment: string, profile_key: string|null}
     */
    public function resolveForModel(PurchaseHeaderAdjustment $adjustment): array
    {
        $costTreatment = trim((string) $adjustment->cost_treatment);
        $taxTreatment = trim((string) $adjustment->tax_treatment);
        $finalTreatment = $this->finalTreatment(
            $costTreatment,
            $taxTreatment,
            $adjustment->adjustment_type instanceof PurchaseAdjustmentType
                ? $adjustment->adjustment_type
                : PurchaseAdjustmentType::from((string) $adjustment->adjustment_type),
            (bool) $adjustment->is_allocatable,
        );

        return [
            'cost_treatment' => $costTreatment,
            'tax_treatment' => $taxTreatment,
            'mapping_source' => (string) $adjustment->mapping_source,
            'finance_posting_profile_id' => $adjustment->finance_posting_profile_id === null ? null : (int) $adjustment->finance_posting_profile_id,
            'finance_account_id' => $adjustment->finance_account_id === null ? null : (int) $adjustment->finance_account_id,
            'final_treatment' => $finalTreatment,
            'profile_key' => $this->profileKeyForTreatment($finalTreatment, $taxTreatment),
        ];
    }

    public function recognizesAtGoodsReceipt(PurchaseHeaderAdjustment $adjustment): bool
    {
        if (! (bool) $adjustment->is_allocatable) {
            return false;
        }

        $type = $adjustment->adjustment_type instanceof PurchaseAdjustmentType
            ? $adjustment->adjustment_type
            : PurchaseAdjustmentType::from((string) $adjustment->adjustment_type);
        if (in_array($type, [PurchaseAdjustmentType::Tax, PurchaseAdjustmentType::Withholding], true)) {
            return false;
        }

        return in_array($this->resolveForModel($adjustment)['final_treatment'], ['capitalize', 'reduce_cost'], true);
    }

    public function receiveOnlySupported(PurchaseHeaderAdjustmentData $data): bool
    {
        $defaults = $this->catalogue->defaultsFor($data->adjustmentType);
        $costTreatment = (string) ($data->costTreatment ?? $defaults['cost_treatment']);
        $taxTreatment = (string) ($data->taxTreatment ?? $defaults['tax_treatment']);
        $treatment = $this->finalTreatment($costTreatment, $taxTreatment, $data->adjustmentType, $data->isAllocatable);

        if (! in_array($treatment, ['capitalize', 'reduce_cost'], true)) {
            return false;
        }

        return ! in_array($data->adjustmentType, [PurchaseAdjustmentType::Tax, PurchaseAdjustmentType::Withholding], true);
    }

    public function accountForAdjustment(PurchaseHeaderAdjustment $adjustment, bool $lock = false): ?FinanceAccount
    {
        $policy = $this->resolveForModel($adjustment);
        if ($policy['finance_account_id'] !== null) {
            return $this->account(
                $policy['finance_account_id'],
                (int) $adjustment->tenant_id,
                $adjustment->organization_unit_id,
                $lock,
                'finance_account_id',
            );
        }

        if ($policy['finance_posting_profile_id'] !== null) {
            return $this->accountFromProfile(
                $policy['finance_posting_profile_id'],
                $policy['profile_key'],
                (int) $adjustment->tenant_id,
                $adjustment->organization_unit_id,
                $lock,
                'finance_posting_profile_id',
            );
        }

        return null;
    }

    private function overrideRequested(PurchaseHeaderAdjustmentData $data, array $defaults): bool
    {
        if (($data->mappingSource ?? 'catalogue') === 'override') {
            return true;
        }
        if ($data->financePostingProfileId !== null || $data->financeAccountId !== null) {
            return true;
        }
        if ($data->costTreatment !== null && $data->costTreatment !== (string) $defaults['cost_treatment']) {
            return true;
        }

        return $data->taxTreatment !== null && $data->taxTreatment !== (string) $defaults['tax_treatment'];
    }

    private function assertOverrideAllowed(PurchaseAdjustmentType $type, array $defaults, int $tenantId, ?int $userId, string $fieldPrefix): void
    {
        if (! (bool) ($defaults['override_allowed'] ?? false)) {
            throw ValidationException::withMessages([
                "{$fieldPrefix}.mapping_source" => ['Accounting treatment for this adjustment type is controlled by the purchase catalogue.'],
            ]);
        }

        if ($userId === null || ! $this->authorization->can($userId, $tenantId, PurchaseAuthorizationService::ADJUSTMENT_ACCOUNTING_OVERRIDE)) {
            throw ValidationException::withMessages([
                "{$fieldPrefix}.mapping_source" => ['You are not allowed to override purchase adjustment accounting mappings.'],
            ]);
        }
    }

    private function finalTreatment(string $costTreatment, string $taxTreatment, PurchaseAdjustmentType $type, bool $isAllocatable): string
    {
        $cost = mb_strtolower(trim($costTreatment));
        $tax = mb_strtolower(trim($taxTreatment));

        if (in_array($type, [PurchaseAdjustmentType::Tax], true) || in_array($tax, ['input_tax', 'recoverable_tax', 'non_recoverable_tax'], true)) {
            return 'tax_only';
        }
        if ($type === PurchaseAdjustmentType::Withholding || $cost === 'withholding' || $tax === 'withholding') {
            return 'document_payable';
        }

        return match ($cost) {
            'landed_cost' => $isAllocatable ? 'capitalize' : 'unsupported',
            'landed_cost_or_expense' => $isAllocatable ? 'capitalize' : 'expense',
            'inventory_cost_reduction' => $isAllocatable ? 'reduce_cost' : 'unsupported',
            'expense', 'rounding' => 'expense',
            'supplier_credit', 'supplier_debit' => 'document_payable',
            'none' => $tax === 'none' ? 'document_payable' : 'tax_only',
            'custom', 'other' => 'expense',
            default => 'unsupported',
        };
    }

    private function profileKeyForTreatment(string $finalTreatment, string $taxTreatment): ?string
    {
        return match ($finalTreatment) {
            'capitalize', 'reduce_cost' => 'inventory',
            'tax_only' => in_array(mb_strtolower(trim($taxTreatment)), ['input_tax', 'recoverable_tax', 'non_recoverable_tax'], true) ? 'tax_receivable' : 'payable',
            'document_payable' => 'payable',
            'expense' => 'expense',
            default => null,
        };
    }

    private function accountFromProfile(int $profileId, ?string $profileKey, int $tenantId, ?int $organizationUnitId, bool $lock, string $field): FinanceAccount
    {
        if ($profileKey === null || $profileKey === '') {
            throw ValidationException::withMessages([$field => ['The selected posting profile cannot resolve this adjustment treatment.']]);
        }

        $query = FinancePostingProfile::query()
            ->with('rules.account')
            ->whereKey($profileId)
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where(fn ($scope) => $organizationUnitId === null
                ? $scope->whereNull('organization_unit_id')
                : $scope->whereNull('organization_unit_id')->orWhere('organization_unit_id', $organizationUnitId));
        if ($lock) {
            $query->lockForUpdate();
        }

        $profile = $query->first();
        if (! $profile instanceof FinancePostingProfile) {
            throw ValidationException::withMessages([$field => ['The selected finance posting profile is not available.']]);
        }

        $rule = $profile->rules->firstWhere('line_key', $profileKey);
        $account = $rule?->account;
        if (! $account instanceof FinanceAccount) {
            throw ValidationException::withMessages([$field => ["The selected finance posting profile is missing account mapping [{$profileKey}]."]]);
        }

        return $account;
    }

    private function account(int $accountId, int $tenantId, ?int $organizationUnitId, bool $lock, string $field): FinanceAccount
    {
        $query = FinanceAccount::query()
            ->whereKey($accountId)
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('is_posting_account', true)
            ->where(fn ($scope) => $organizationUnitId === null
                ? $scope->whereNull('organization_unit_id')
                : $scope->whereNull('organization_unit_id')->orWhere('organization_unit_id', $organizationUnitId));
        if ($lock) {
            $query->lockForUpdate();
        }

        $account = $query->first();
        if (! $account instanceof FinanceAccount) {
            throw ValidationException::withMessages([$field => ['The selected finance account is not available for posting.']]);
        }

        return $account;
    }
}
