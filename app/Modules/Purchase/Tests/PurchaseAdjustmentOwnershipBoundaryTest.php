<?php

declare(strict_types=1);

namespace Modules\Purchase\Tests;

use PHPUnit\Framework\TestCase;

final class PurchaseAdjustmentOwnershipBoundaryTest extends TestCase
{
    public function test_purchase_adjustment_domain_does_not_resolve_finance_identities(): void
    {
        $dto = $this->source('../DTOs/PurchaseHeaderAdjustmentData.php');
        $model = $this->source('../Models/PurchaseHeaderAdjustment.php');
        $allocationModel = $this->source('../Models/PurchaseAdjustmentAllocation.php');
        $catalogue = $this->source('../Services/PurchaseAdjustmentCatalogueService.php');
        $policy = $this->source('../Services/PurchaseAdjustmentPolicyResolver.php');
        $persistence = $this->source('../Services/PurchaseHeaderAdjustmentService.php');
        $allocationLedger = $this->source('../Services/PurchaseAdjustmentAllocationLedger.php');
        $posting = $this->source('../Services/FastPurchasePostingCoordinator.php');

        $domain = $dto.$model.$allocationModel.$catalogue.$policy.$persistence.$allocationLedger;

        self::assertStringNotContainsString('Modules\\Finance', $domain);
        self::assertStringNotContainsString('FinanceAccount', $domain);
        self::assertStringNotContainsString('FinancePostingProfile', $domain);
        self::assertStringNotContainsString('finance_account_id', $domain);
        self::assertStringNotContainsString('finance_posting_profile_id', $domain);
        self::assertStringNotContainsString('override_reason', $domain);
        self::assertStringNotContainsString('accountCode:', $posting);
        self::assertStringContainsString('profileKey:', $posting);
        self::assertStringContainsString('invoiceProfileKeyFor', $posting);
        self::assertStringContainsString('recognition_source', $persistence);
    }

    public function test_purchase_requests_prohibit_accounting_authority(): void
    {
        $orderRequest = $this->source('../Http/Requests/StorePurchaseOrderRequest.php');
        $fastRequest = $this->source('../Http/Requests/FastPurchaseRequest.php');

        foreach ([
            'adjustments.*.finance_posting_profile_id',
            'adjustments.*.finance_account_id',
            'adjustments.*.cost_treatment',
            'adjustments.*.tax_treatment',
            'adjustments.*.mapping_source',
            'adjustments.*.override_reason',
        ] as $field) {
            self::assertStringContainsString($field, $orderRequest);
            self::assertStringContainsString($field, $fastRequest);
        }

        self::assertStringNotContainsString('financePostingProfileId:', $orderRequest);
        self::assertStringNotContainsString('financeAccountId:', $orderRequest);
        self::assertStringNotContainsString('costTreatment:', $orderRequest);
        self::assertStringNotContainsString('taxTreatment:', $orderRequest);
    }

    public function test_purchase_frontend_submits_business_adjustment_facts_only(): void
    {
        $root = dirname(__DIR__, 4);
        $model = $this->externalSource($root.'/resources/js/modules/purchase/components/purchaseHeaderAdjustmentModel.ts');
        $form = $this->externalSource($root.'/resources/js/modules/purchase/components/PurchaseHeaderAdjustmentForm.tsx');
        $editor = $this->externalSource($root.'/resources/js/modules/purchase/components/PurchaseHeaderAdjustmentEditor.tsx');
        $fastForm = $this->externalSource($root.'/resources/js/modules/purchase/hooks/useFastPurchaseForm.ts');
        $types = $this->externalSource($root.'/resources/js/modules/purchase/purchaseTypes.ts');
        $fastTypes = $this->externalSource($root.'/resources/js/modules/purchase/types/fastPurchaseTypes.ts');

        $frontend = $model.$form.$editor.$fastForm.$types.$fastTypes;
        self::assertStringNotContainsString('finance_posting_profile_id', $frontend);
        self::assertStringNotContainsString('finance_account_id', $frontend);
        self::assertStringNotContainsString('override_reason', $frontend);
        self::assertStringNotContainsString('mapping_source', $frontend);
        self::assertStringNotContainsString('Finance Mapping', $frontend);
        self::assertStringContainsString('recognition_label', $frontend);
    }

    public function test_schema_removes_obsolete_adjustment_finance_columns(): void
    {
        $headerMigration = $this->source('../Database/Migrations/2026_06_12_170003_create_purchase_header_adjustments_table.php');
        $allocationMigration = $this->source('../Database/Migrations/2026_06_12_170011_create_purchase_adjustment_allocations_table.php');
        $schema = $headerMigration.$allocationMigration;

        self::assertStringNotContainsString('finance_posting_profile_id', $schema);
        self::assertStringNotContainsString('finance_account_id', $schema);
        self::assertStringNotContainsString('override_reason', $schema);
        self::assertStringNotContainsString('mapping_source', $schema);
        self::assertStringContainsString('recognition_source', $headerMigration);
    }

    private function source(string $relativePath): string
    {
        return $this->externalSource(__DIR__.'/'.$relativePath);
    }

    private function externalSource(string $path): string
    {
        $source = file_get_contents($path);
        self::assertIsString($source);

        return $source;
    }
}
