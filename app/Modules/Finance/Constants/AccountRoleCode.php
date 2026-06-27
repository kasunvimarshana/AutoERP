<?php

declare(strict_types=1);

namespace Modules\Finance\Constants;

final class AccountRoleCode
{
    public const CASH = 'CASH';
    public const BANK = 'BANK';
    public const ACCOUNTS_RECEIVABLE = 'ACCOUNTS_RECEIVABLE';
    public const ACCOUNTS_PAYABLE = 'ACCOUNTS_PAYABLE';
    public const INVENTORY_ASSET = 'INVENTORY_ASSET';
    public const SALES_REVENUE = 'SALES_REVENUE';
    public const SERVICE_REVENUE = 'SERVICE_REVENUE';
    public const PURCHASE_EXPENSE = 'PURCHASE_EXPENSE';
    public const COST_OF_GOODS_SOLD = 'COST_OF_GOODS_SOLD';
    public const INPUT_TAX = 'INPUT_TAX';
    public const OUTPUT_TAX = 'OUTPUT_TAX';
    public const WITHHOLDING_RECEIVABLE = 'WITHHOLDING_RECEIVABLE';
    public const WITHHOLDING_PAYABLE = 'WITHHOLDING_PAYABLE';
    public const PAYMENT_RECEIPT_ACCOUNT = 'PAYMENT_RECEIPT_ACCOUNT';
    public const PAYMENT_DISBURSEMENT_ACCOUNT = 'PAYMENT_DISBURSEMENT_ACCOUNT';
    public const PURCHASE_ADJUSTMENT = 'PURCHASE_ADJUSTMENT';
    public const CURRENCY_EXPOSURE = 'CURRENCY_EXPOSURE';
    public const UNREALIZED_GAIN = 'UNREALIZED_GAIN';
    public const UNREALIZED_LOSS = 'UNREALIZED_LOSS';
    public const VEHICLE_SERVICE_WIP = 'VEHICLE_SERVICE_WIP';
    public const MATERIAL_REVENUE = 'MATERIAL_REVENUE';
    public const MATERIAL_COST_OF_SALES = 'MATERIAL_COST_OF_SALES';
    public const LABOUR_REVENUE = 'LABOUR_REVENUE';
    public const EXTERNAL_WORK_REVENUE = 'EXTERNAL_WORK_REVENUE';
    public const RENTAL_INCOME = 'RENTAL_INCOME';
    public const EXCESS_KM_INCOME = 'EXCESS_KM_INCOME';
    public const DRIVER_REIMBURSEMENT_INCOME = 'DRIVER_REIMBURSEMENT_INCOME';
    public const RENTAL_DIRECT_COST = 'RENTAL_DIRECT_COST';
    public const EXCESS_KM_EXPENSE = 'EXCESS_KM_EXPENSE';
    public const VEHICLE_OWNER_PAYABLE = 'VEHICLE_OWNER_PAYABLE';
    public const FUEL_REPAIR_RECOVERY = 'FUEL_REPAIR_RECOVERY';

    /**
     * @return array<string, array{module: string, name: string, description: string}>
     */
    public static function definitions(): array
    {
        return [
            self::CASH => self::definition('finance', 'Cash', 'Default cash account role.'),
            self::BANK => self::definition('finance', 'Bank', 'Default bank account role.'),
            self::ACCOUNTS_RECEIVABLE => self::definition('finance', 'Accounts receivable', 'Customer receivable control account.'),
            self::ACCOUNTS_PAYABLE => self::definition('finance', 'Accounts payable', 'Supplier and owner payable control account.'),
            self::INVENTORY_ASSET => self::definition('inventory', 'Inventory asset', 'Inventory asset account.'),
            self::SALES_REVENUE => self::definition('sales', 'Sales revenue', 'Sales revenue account.'),
            self::SERVICE_REVENUE => self::definition('vehicle-service', 'Service revenue', 'General vehicle-service revenue account.'),
            self::PURCHASE_EXPENSE => self::definition('purchase', 'Purchase expense', 'Direct purchase expense account.'),
            self::COST_OF_GOODS_SOLD => self::definition('sales', 'Cost of goods sold', 'Inventory cost recognized on sale.'),
            self::INPUT_TAX => self::definition('tax', 'Input tax', 'Recoverable input tax account.'),
            self::OUTPUT_TAX => self::definition('tax', 'Output tax', 'Output tax liability account.'),
            self::WITHHOLDING_RECEIVABLE => self::definition('tax', 'Withholding receivable', 'Withholding tax asset deducted by customers.'),
            self::WITHHOLDING_PAYABLE => self::definition('tax', 'Withholding payable', 'Withholding tax liability deducted from suppliers.'),
            self::PAYMENT_RECEIPT_ACCOUNT => self::definition('payment', 'Receipt settlement account', 'Account receiving customer or other incoming payments.'),
            self::PAYMENT_DISBURSEMENT_ACCOUNT => self::definition('payment', 'Disbursement settlement account', 'Account funding supplier or other outgoing payments.'),
            self::PURCHASE_ADJUSTMENT => self::definition('purchase', 'Purchase adjustment', 'Context-specific purchase adjustment account.'),
            self::CURRENCY_EXPOSURE => self::definition('finance', 'Currency exposure', 'Context-specific monetary exposure account.'),
            self::UNREALIZED_GAIN => self::definition('finance', 'Unrealized currency gain', 'Unrealized foreign-exchange gain account.'),
            self::UNREALIZED_LOSS => self::definition('finance', 'Unrealized currency loss', 'Unrealized foreign-exchange loss account.'),
            self::VEHICLE_SERVICE_WIP => self::definition('vehicle-service', 'Vehicle service work in progress', 'Work-in-progress account for open vehicle-service jobs.'),
            self::MATERIAL_REVENUE => self::definition('vehicle-service', 'Vehicle service material revenue', 'Revenue from materials billed on service jobs.'),
            self::MATERIAL_COST_OF_SALES => self::definition('vehicle-service', 'Vehicle service material cost', 'Cost of materials consumed by service jobs.'),
            self::LABOUR_REVENUE => self::definition('vehicle-service', 'Vehicle service labour revenue', 'Revenue from labour billed on service jobs.'),
            self::EXTERNAL_WORK_REVENUE => self::definition('vehicle-service', 'Vehicle service external-work revenue', 'Revenue from outsourced work billed to customers.'),
            self::RENTAL_INCOME => self::definition('vehicle-rental', 'Vehicle rental income', 'Base vehicle-rental income.'),
            self::EXCESS_KM_INCOME => self::definition('vehicle-rental', 'Excess kilometre income', 'Rental income from excess kilometres.'),
            self::DRIVER_REIMBURSEMENT_INCOME => self::definition('vehicle-rental', 'Driver reimbursement income', 'Customer reimbursement for driver costs.'),
            self::RENTAL_DIRECT_COST => self::definition('vehicle-rental', 'Vehicle rental direct cost', 'Vehicle-owner rental cost.'),
            self::EXCESS_KM_EXPENSE => self::definition('vehicle-rental', 'Excess kilometre expense', 'Vehicle-owner excess kilometre expense.'),
            self::VEHICLE_OWNER_PAYABLE => self::definition('vehicle-rental', 'Vehicle owner payable', 'Payable due to the vehicle owner.'),
            self::FUEL_REPAIR_RECOVERY => self::definition('vehicle-rental', 'Fuel and repair recovery', 'Recovery income charged against vehicle-owner settlements.'),
        ];
    }

    /** @return array{module: string, name: string, description: string} */
    private static function definition(string $module, string $name, string $description): array
    {
        return compact('module', 'name', 'description');
    }
}
