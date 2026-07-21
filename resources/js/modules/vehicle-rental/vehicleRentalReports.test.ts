import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { cwd } from 'node:process';
import { describe, expect, it } from 'vitest';

const source = (path: string) => readFileSync(resolve(cwd(), path), 'utf8');

const reportPageSource = source('resources/js/modules/vehicle-rental/pages/VehicleRentalReportPage.tsx');
const rentalReportsSource = source('resources/js/modules/vehicle-rental/pages/RentalReportsPage.tsx');
const reportingRoutesSource = source('app/Modules/Reporting/Routes/api.php');
const exceptionServiceSource = source('app/Modules/Reporting/Services/VehicleRentalChartExceptionReportService.php');
const financialServiceSource = source('app/Modules/Reporting/Services/VehicleRentalFinancialReportService.php');
const definitionSource = source('app/Modules/Reporting/Services/VehicleRentalReportDefinitions.php');

describe('Vehicle Rental Phase 1 reporting', () => {
    it('exposes all five business reports from the Vehicle Rental workspace', () => {
        for (const slug of [
            'running-chart',
            'chart-exceptions',
            'customer-invoices',
            'owner-vouchers',
            'rental-history',
        ]) {
            expect(rentalReportsSource).toContain(`'/vehicle-rental/reports/${slug}'`);
        }
        expect(rentalReportsSource).toContain('reportingPermissions.view');
    });

    it('uses the existing report filter, summary, pagination and export infrastructure', () => {
        expect(reportPageSource).toContain('runOperationalReport(reportKey, params');
        expect(reportPageSource).toContain('<SummaryCards summary={result.summary} />');
        expect(reportPageSource).toContain('<ExportActions reportKey={reportKey} params={params} />');
        expect(reportPageSource).toContain('<ReportDataGrid');
        expect(reportPageSource).toContain('<Pagination');
    });

    it('requires a bounded date range for missing and duplicate chart detection', () => {
        expect(reportPageSource).toContain("kind === 'chart-exceptions'");
        expect(reportPageSource).toContain('maximum period of 366 calendar days');
        expect(exceptionServiceSource).toContain('private const MAX_REPORT_DAYS = 366;');
        expect(exceptionServiceSource).toContain('The missing and duplicate Running Chart report requires a start and end date.');
    });

    it('keeps customer invoices and owner vouchers on independent financial sides', () => {
        expect(financialServiceSource).toContain('RentalCalculationSide::Customer');
        expect(financialServiceSource).toContain('RentalCalculationSide::Owner');
        expect(financialServiceSource).toContain('InvoiceDirection::Outbound');
        expect(financialServiceSource).toContain('InvoiceDirection::Inbound');
        expect(definitionSource).toContain('Owner Payable Voucher Register');
    });

    it('registers feature-gated report and export routes before the generic catch-all', () => {
        const vehicleRentalRoute = reportingRoutesSource.indexOf("Route::get('vehicle-rental/running-chart'");
        const genericRoute = reportingRoutesSource.indexOf("Route::get('{report}'");
        const vehicleRentalExport = reportingRoutesSource.indexOf("Route::get('vehicle-rental/running-chart/export/{format}'");
        const genericExport = reportingRoutesSource.indexOf("Route::get('{report}/export/{format}'");

        expect(reportingRoutesSource).toContain("Route::middleware('tenant.feature:vehicle-rental')");
        expect(vehicleRentalRoute).toBeGreaterThan(-1);
        expect(vehicleRentalRoute).toBeLessThan(genericRoute);
        expect(vehicleRentalExport).toBeGreaterThan(-1);
        expect(vehicleRentalExport).toBeLessThan(genericExport);
    });
});
