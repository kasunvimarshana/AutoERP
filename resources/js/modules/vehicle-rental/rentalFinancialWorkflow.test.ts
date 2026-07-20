import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { cwd } from 'node:process';
import { describe, expect, it } from 'vitest';

function source(path: string): string {
    return readFileSync(resolve(cwd(), path), 'utf8');
}

const navigationSource = source('resources/js/app/navigation/vehicleRentalNavigation.ts');
const workspaceSource = source('resources/js/modules/vehicle-rental/pages/VehicleRentalWorkspacePage.tsx');
const financialSource = source('resources/js/modules/vehicle-rental/pages/RentalFinancialDocumentsPage.tsx');
const settlementSource = source('resources/js/modules/vehicle-rental/pages/RentalSettlementHandoffPage.tsx');
const reportsSource = source('resources/js/modules/vehicle-rental/pages/RentalReportsPage.tsx');
const apiSource = source('resources/js/modules/vehicle-rental/vehicleRentalApi.ts');

const primaryWorkflowLabels = [
    'Owner / Supplier Agreements',
    'Customer Agreements',
    'Daily Running Charts',
    'Customer Invoices',
    'Owner Settlements',
    'Customer Receipts',
    'Owner Payments',
    'Reports',
];

describe('Vehicle Rental end-to-end financial workflow', () => {
    it('keeps the primary navigation aligned to the simple business workflow', () => {
        for (const label of primaryWorkflowLabels) {
            expect(navigationSource).toContain(label);
        }
        expect(navigationSource).not.toContain("label: 'Vehicle Assignments'");
        expect(navigationSource).not.toContain("label: 'Calculations'");
    });

    it('keeps vehicle selection contextual to the active agreement', () => {
        expect(workspaceSource).toContain('>Select vehicle</Button>');
        expect(workspaceSource).toContain('lockAgreement');
        expect(workspaceSource).toContain('<AgreementsPage fixedKind="owner" />');
        expect(workspaceSource).toContain('<AgreementsPage fixedKind="customer" />');
    });

    it('creates Rental financial documents and delegates settlement to Payment', () => {
        expect(apiSource).toContain('createRentalFinancialDocument');
        expect(apiSource).toContain('/financial-document`');
        expect(financialSource).toContain('Customer invoice');
        expect(financialSource).toContain('Owner Payable Voucher');
        expect(financialSource).toContain('/payments/create?invoice_id=');
        expect(settlementSource).toContain('Payment module');
        expect(settlementSource).toContain('/payments/create?invoice_id=');
    });

    it('derives reports from operational and financial owner modules', () => {
        expect(apiSource).toContain('getRentalReportSummary');
        expect(reportsSource).toContain('Gross margin before tax');
        expect(reportsSource).toContain('/finance/ledger');
        expect(reportsSource).toContain('/finance/bank-reconciliations');
    });
});
