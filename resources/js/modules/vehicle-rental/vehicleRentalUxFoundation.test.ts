import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { cwd } from 'node:process';
import { describe, expect, it } from 'vitest';

const source = (path: string) => readFileSync(resolve(cwd(), path), 'utf8');

const rateEditorSource = source('resources/js/modules/vehicle-rental/components/RentalRateEditor.tsx');
const agreementDialogSource = source('resources/js/modules/vehicle-rental/components/RentalAgreementDialogs.tsx');
const calculationDialogSource = source('resources/js/modules/vehicle-rental/components/RentalCalculationDialog.tsx');
const runningChartDialogSource = source('resources/js/modules/vehicle-rental/components/RentalRunningChartDialog.tsx');
const settlementPageSource = source('resources/js/modules/vehicle-rental/pages/RentalSettlementHandoffPage.tsx');
const calculationControllerSource = source('app/Modules/VehicleRental/Http/Controllers/RentalCalculationController.php');

describe('Vehicle Rental UX foundation', () => {
    it('does not offer unsupported fixed Other rates for new agreements', () => {
        expect(rateEditorSource).toContain('const EDITABLE_RATE_CODES');
        expect(rateEditorSource).not.toContain("{ value: 'other', label: 'Other fixed charge' }");
        expect(rateEditorSource).toContain('Unsupported fixed charge — remove this row');
    });

    it('autoloads business dates and party currency without overwriting an explicit currency', () => {
        expect(agreementDialogSource).toContain('const businessDate = businessDateInputValue()');
        expect(agreementDialogSource).toContain('executedAt: agreement?.executed_at ?? businessDate');
        expect(agreementDialogSource).toContain('startsOn: agreement?.starts_on ?? businessDate');
        expect(agreementDialogSource).toContain('currency: current.currency ?? value?.defaultCurrency ?? null');
    });

    it('uses a complete-month selector for monthly calculations', () => {
        expect(calculationDialogSource).toContain('type="month"');
        expect(calculationDialogSource).toContain('completeMonth(periodMonth)');
        expect(calculationDialogSource).toContain('Partial-month proration is not configured');
    });

    it('derives operational date and previews running-chart distance', () => {
        expect(runningChartDialogSource).toContain('operational_date: localDate(state.startsAt)');
        expect(runningChartDialogSource).not.toContain('label="Operational date"');
        expect(runningChartDialogSource).toContain('distancePreview(state.startOdometer, state.endOdometer, state.garageKm)');
        expect(runningChartDialogSource).toContain('This is a self-drive assignment');
    });

    it('filters outstanding financial documents before pagination', () => {
        expect(settlementPageSource).toContain('has_financial_document: true');
        expect(settlementPageSource).toContain('outstanding_only: true');
        expect(settlementPageSource).not.toContain('.filter((row) => row.financial_document');
        expect(calculationControllerSource).toContain('constrainToActiveFinancialDocuments');
    });
});
