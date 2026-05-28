<?php

declare(strict_types=1);

namespace Modules\Voucher\Infrastructure\Persistence\Eloquent\Seeders\Support;

final class VoucherDocumentSeedCatalog
{
    public const DEFAULT_TENANT_CODE = 'default';

    /** @return list<array<string, mixed>> */
    public static function documentTypes(): array
    {
        return [
            ['code' => 'VOUCHER_PAYMENT', 'name' => 'Payment Voucher', 'default_status' => 'draft', 'is_active' => true, 'requires_source' => false],
            ['code' => 'VOUCHER_RECEIPT', 'name' => 'Receipt Voucher', 'default_status' => 'draft', 'is_active' => true, 'requires_source' => false],
            ['code' => 'VOUCHER_JOURNAL', 'name' => 'Journal Voucher', 'default_status' => 'draft', 'is_active' => true, 'requires_source' => false],
            ['code' => 'VOUCHER_CONTRA', 'name' => 'Contra Voucher', 'default_status' => 'draft', 'is_active' => true, 'requires_source' => false],
            ['code' => 'VOUCHER_EXPENSE', 'name' => 'Expense Voucher', 'default_status' => 'draft', 'is_active' => true, 'requires_source' => false],
        ];
    }

    /** @return array<string, string> */
    public static function documentDefinitionNames(): array
    {
        return [
            'VOUCHER_PAYMENT' => 'Payment Voucher Definition',
            'VOUCHER_RECEIPT' => 'Receipt Voucher Definition',
            'VOUCHER_JOURNAL' => 'Journal Voucher Definition',
            'VOUCHER_CONTRA' => 'Contra Voucher Definition',
            'VOUCHER_EXPENSE' => 'Expense Voucher Definition',
        ];
    }

    /** @return array<string, array<string, mixed>> */
    public static function workflowBlueprints(): array
    {
        $steps = [
            ['sequence' => 1, 'name' => 'draft', 'display_name' => 'Draft', 'is_initial' => true, 'is_terminal' => false],
            ['sequence' => 2, 'name' => 'submitted', 'display_name' => 'Submitted', 'is_initial' => false, 'is_terminal' => false],
            ['sequence' => 3, 'name' => 'approved', 'display_name' => 'Approved', 'is_initial' => false, 'is_terminal' => false],
            ['sequence' => 4, 'name' => 'posted', 'display_name' => 'Posted', 'is_initial' => false, 'is_terminal' => false],
            ['sequence' => 5, 'name' => 'cancelled', 'display_name' => 'Cancelled', 'is_initial' => false, 'is_terminal' => true],
            ['sequence' => 6, 'name' => 'reversed', 'display_name' => 'Reversed', 'is_initial' => false, 'is_terminal' => true],
        ];

        $transitions = [
            ['from' => 'draft', 'to' => 'submitted', 'action_name' => 'submit'],
            ['from' => 'draft', 'to' => 'cancelled', 'action_name' => 'cancel'],
            ['from' => 'submitted', 'to' => 'approved', 'action_name' => 'approve'],
            ['from' => 'submitted', 'to' => 'cancelled', 'action_name' => 'cancel'],
            ['from' => 'approved', 'to' => 'posted', 'action_name' => 'post'],
            ['from' => 'approved', 'to' => 'cancelled', 'action_name' => 'cancel'],
            ['from' => 'posted', 'to' => 'reversed', 'action_name' => 'reverse'],
        ];

        return [
            'VOUCHER_PAYMENT' => ['name' => 'Payment Voucher Workflow', 'steps' => $steps, 'transitions' => $transitions],
            'VOUCHER_RECEIPT' => ['name' => 'Receipt Voucher Workflow', 'steps' => $steps, 'transitions' => $transitions],
            'VOUCHER_JOURNAL' => ['name' => 'Journal Voucher Workflow', 'steps' => $steps, 'transitions' => $transitions],
            'VOUCHER_CONTRA' => ['name' => 'Contra Voucher Workflow', 'steps' => $steps, 'transitions' => $transitions],
            'VOUCHER_EXPENSE' => ['name' => 'Expense Voucher Workflow', 'steps' => $steps, 'transitions' => $transitions],
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function sequences(): array
    {
        return [
            ['document_type' => 'VOUCHER_PAYMENT', 'prefix' => 'PV-'],
            ['document_type' => 'VOUCHER_RECEIPT', 'prefix' => 'RV-'],
            ['document_type' => 'VOUCHER_JOURNAL', 'prefix' => 'JV-'],
            ['document_type' => 'VOUCHER_CONTRA', 'prefix' => 'CV-'],
            ['document_type' => 'VOUCHER_EXPENSE', 'prefix' => 'EV-'],
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function voucherTypes(): array
    {
        return [
            ['code' => 'PAYMENT', 'name' => 'Payment Voucher', 'direction' => 'payable', 'document_type_code' => 'VOUCHER_PAYMENT'],
            ['code' => 'RECEIPT', 'name' => 'Receipt Voucher', 'direction' => 'receivable', 'document_type_code' => 'VOUCHER_RECEIPT'],
            ['code' => 'JOURNAL', 'name' => 'Journal Voucher', 'direction' => 'journal', 'document_type_code' => 'VOUCHER_JOURNAL'],
            ['code' => 'CONTRA', 'name' => 'Contra Voucher', 'direction' => 'transfer', 'document_type_code' => 'VOUCHER_CONTRA'],
            ['code' => 'EXPENSE', 'name' => 'Expense Voucher', 'direction' => 'payable', 'document_type_code' => 'VOUCHER_EXPENSE'],
        ];
    }
}
