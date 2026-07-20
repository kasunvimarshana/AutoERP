import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { cwd } from 'node:process';
import { describe, expect, it } from 'vitest';

const source = (path: string) => readFileSync(resolve(cwd(), path), 'utf8');

const rateEditorSource = source('resources/js/modules/vehicle-rental/components/RentalRateEditor.tsx');
const agreementDialogSource = source('resources/js/modules/vehicle-rental/components/RentalAgreementDialogs.tsx');
const assignmentDialogSource = source('resources/js/modules/vehicle-rental/components/RentalAssignmentDialogs.tsx');
const lookupsSource = source('resources/js/modules/vehicle-rental/components/VehicleRentalLookups.tsx');
const calculationDialogSource = source('resources/js/modules/vehicle-rental/components/RentalCalculationDialog.tsx');
const runningChartDialogSource = source('resources/js/modules/vehicle-rental/components/RentalRunningChartDialog.tsx');
const vehicleRentalApiSource = source('resources/js/modules/vehicle-rental/vehicleRentalApi.ts');
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

    it('uses only complete eligible months for monthly calculations', () => {
        expect(calculationDialogSource).toContain('type="month"');
        expect(calculationDialogSource).toContain('completeMonth(periodMonth)');
        expect(calculationDialogSource).toContain('firstCompleteMonth(agreement?.startsOn)');
        expect(calculationDialogSource).toContain('lastCompleteMonth(agreement?.endsOn)');
        expect(calculationDialogSource).toContain('Partial-month proration is not configured');
    });

    it('autoloads the unique vehicle owner agreement and fits the customer period to it', () => {
        expect(assignmentDialogSource).toContain("listRentalAssignmentLookup('assignment-source'");
        expect(assignmentDialogSource).toContain('Vehicle owner agreement autoloaded');
        expect(assignmentDialogSource).toContain('const resolvedSourceAssignment = selectedSourceCandidate');
        expect(assignmentDialogSource).toContain('fitAssignmentDateTimes(state.startsAt, state.endsAt, bounds)');
        expect(assignmentDialogSource).toContain('assignmentBounds');
        expect(assignmentDialogSource).not.toContain('label="Owner-supply source assignment"');
    });

    it('uses explicit-offset timestamps for source lookup and rental mutations', () => {
        expect(lookupsSource).toContain('localDateTimeToOffsetIso(startsAt)');
        expect(lookupsSource).toContain('localDateTimeToOffsetIso(endsAt)');
        expect(assignmentDialogSource).toContain('starts_at: localDateTimeToOffsetIso(fittedDates.startsAt)');
        expect(assignmentDialogSource).toContain('ends_at: nullableLocalDateTimeToOffsetIso(fittedDates.endsAt)');
        expect(assignmentDialogSource).toContain('event_at: localDateTimeToOffsetIso(eventAt)');
        expect(assignmentDialogSource).toContain('effective_at: localDateTimeToOffsetIso(effectiveAt)');
        expect(vehicleRentalApiSource).toContain('runningChartApiPayload(payload)');
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
