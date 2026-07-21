import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { cwd } from 'node:process';
import { describe, expect, it } from 'vitest';

const source = (path: string) => readFileSync(resolve(cwd(), path), 'utf8');

const reportPageSource = source('resources/js/modules/vehicle-rental/pages/VehicleRentalReportPage.tsx');
const rentalReportsSource = source('resources/js/modules/vehicle-rental/pages/RentalReportsPage.tsx');
const reportListSource = source('resources/js/modules/reporting/pages/ReportListPage.tsx');
const reportingRoutesSource = source('app/Modules/Reporting/Routes/api.php');
const reportServiceSource = source('app/Modules/Reporting/Services/VehicleRentalReportService.php');

describe('Vehicle Rental Phase 1 reporting', () => {
    it('exposes all five business reports from the Vehicle Rental workspace and global catalog', () => {
        for (const slug of [
            'running-chart',
            'chart-exceptions',
            'customer-invoices',
            'owner-vouchers',
            'rental-history',
        ]) {
            expect(rentalReportsSource).toContain(`'/vehicle-rental/reports/${slug}'`);
            expect(reportListSource).toContain(`'/vehicle-rental/reports/${slug}'`);
        }
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
        expect(reportServiceSource).toContain('private const MAX_EXCEPTION_REPORT_DAYS = 366;');
        expect(reportServiceSource).toContain('The missing and duplicate Running Chart report requires a start and end date.');
    });

    it('keeps customer invoices and owner vouchers on independent financial sides', () => {
        expect(reportServiceSource).toContain('RentalCalculationSide::Customer');
        expect(reportServiceSource).toContain('RentalCalculationSide::Owner');
        expect(reportServiceSource).toContain('InvoiceDirection::Outbound');
        expect(reportServiceSource).toContain('InvoiceDirection::Inbound');
        expect(reportServiceSource).toContain('Owner Payable Voucher Register');
    });

    it('registers explicit report and export routes before the generic catch-all', () => {
        const vehicleRentalRoute = reportingRoutesSource.indexOf("Route::get('vehicle-rental/running-chart'");
        const genericRoute = reportingRoutesSource.indexOf("Route::get('{report}'");
        const vehicleRentalExport = reportingRoutesSource.indexOf("Route::get('vehicle-rental/running-chart/export/{format}'");
        const genericExport = reportingRoutesSource.indexOf("Route::get('{report}/export/{format}'");

        expect(vehicleRentalRoute).toBeGreaterThan(-1);
        expect(vehicleRentalRoute).toBeLessThan(genericRoute);
        expect(vehicleRentalExport).toBeGreaterThan(-1);
        expect(vehicleRentalExport).toBeLessThan(genericExport);
    });
});
