/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import React from 'react';
import { MemoryRouter } from 'react-router-dom';
import FleetUtilizationReportPage from './FleetUtilizationReportPage';
import OwnerDisbursementReportPage from './OwnerDisbursementReportPage';
import LesseeOutstandingReportPage from './LesseeOutstandingReportPage';
import VehicleRevenueLicenseReportPage from './VehicleRevenueLicenseReportPage';
import * as rentalReportsApi from '../rentalReportsApi';

vi.mock('../rentalReportsApi', () => ({
    fetchFleetUtilizationReport: vi.fn(),
    fetchOwnerDisbursementReport: vi.fn(),
    fetchLesseeOutstandingReport: vi.fn(),
    fetchVehicleRevenueLicenseReport: vi.fn(),
    exportRentalReport: vi.fn(),
    getFleetUtilizationReportExportUrl: vi.fn(),
    getOwnerDisbursementReportExportUrl: vi.fn(),
    getLesseeOutstandingReportExportUrl: vi.fn(),
    getVehicleRevenueLicenseReportExportUrl: vi.fn(),
}));

const mockFleetData: rentalReportsApi.FleetUtilizationReportData = {
    header: {
        company_name: 'AutoERP Rentals',
        report_title: 'Fleet Utilization Report',
        duration: '2026-09-01 to 2026-09-30',
        start_date: '2026-09-01',
        end_date: '2026-09-30',
        report_date: '2026-09-30',
    },
    filters: { ownership_type: 'all', custody_status: 'all' },
    summary: {
        total_vehicles: 12,
        company_owned_count: 8,
        owner_leased_count: 4,
        in_custody_count: 9,
        available_count: 3,
        average_utilization_rate: 75.0,
        total_distance_km: 15420,
    },
    rows: [
        {
            vehicle_id: 1,
            registration_number: 'WP-CAS-1234',
            vehicle_make_model: 'Toyota Prius',
            ownership_type: 'Company Owned',
            owner_name: 'AutoERP Fleet',
            owner_agreement_number: 'N/A',
            custody_status: 'In Custody',
            status_key: 'in_custody',
            current_customer: 'Apex Logistics',
            current_agreement: 'AGR-CUST-001',
            current_driver: 'Kamal Perera',
            current_odometer: '45,200 km',
            period_distance_km: 1850,
            days_in_custody: 28,
            utilization_rate: 93.3,
        },
    ],
    footer: { software_provider: 'AutoERP SaaS' },
};

const mockDisbursementData: rentalReportsApi.OwnerDisbursementReportData = {
    header: {
        company_name: 'AutoERP Rentals',
        report_title: 'Owner Disbursement Ledger',
        duration: '2026-09-01 to 2026-09-30',
        start_date: '2026-09-01',
        end_date: '2026-09-30',
        report_date: '2026-09-30',
    },
    filters: { supplier_id: null, owner_agreement_id: null, wht_rate: 10 },
    summary: {
        total_owner_agreements: 5,
        total_base_payable: '250000.00',
        total_mileage_payable: '15000.00',
        total_overtime_payable: '0.00',
        total_gross_payable: '265000.00',
        total_wht_amount: '26000.00',
        total_deductions: '5000.00',
        total_net_payable: '234000.00',
    },
    rows: [
        {
            owner_agreement_id: 10,
            agreement_reference: 'AGR-OWN-010',
            supplier_id: 5,
            vehicle_number: 'WP-CAD-5678',
            owner_name: 'Sunil Silva',
            owner_code: 'OWN-005',
            rental_basis: 'monthly',
            status: 'active',
            period_start: '2026-09-01',
            period_end: '2026-09-30',
            base_payable: '50000.00',
            mileage_payable: '3000.00',
            overtime_payable: '0.00',
            gross_payable: '53000.00',
            wht_rate: 10,
            wht_amount: '5300.00',
            deductions: '1000.00',
            net_payable: '46700.00',
        },
    ],
    footer: { software_provider: 'AutoERP SaaS' },
};

const mockAgingData: rentalReportsApi.LesseeOutstandingReportData = {
    header: {
        company_name: 'AutoERP Rentals',
        system_title: 'AutoERP',
        report_title: "Lessee's Outstanding Aging",
        as_of_date: '2026-09-30',
        generated_at: '2026-09-30 10:00:00',
    },
    summary: {
        total_lessees_count: 3,
        total_invoices_count: 8,
        grand_total_original_amount: '125000.00',
        grand_total_balance_amount: '95000.00',
    },
    groups: [],
};

const mockLicenseData: rentalReportsApi.VehicleRevenueLicenseReportData = {
    header: {
        company_name: 'AutoERP Rentals',
        system_title: 'AutoERP',
        report_title: 'Vehicle Revenue Licence Report',
        period_description: 'All Records',
        start_date: null,
        end_date: null,
        generated_at: '2026-09-30 10:00:00',
    },
    summary: {
        total_vehicles: 10,
        expired_count: 1,
        expiring_soon_count: 2,
        active_count: 7,
    },
    data: [
        {
            vehicle_id: 1,
            vehicle_no: 'WP-CAS-1234',
            owner_name: 'AutoERP Fleet',
            ownership_type: 'Company Owned',
            expire_date: '2026-12-31',
            raw_expire_date: '2026-12-31',
            days_remaining: 92,
            document_number: 'LIC-2026-001',
            status: 'active',
            status_label: 'Valid',
        },
    ],
};

describe('Rental Reports Suite UI/UX Standards', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.mocked(rentalReportsApi.fetchFleetUtilizationReport).mockResolvedValue(mockFleetData);
        vi.mocked(rentalReportsApi.fetchOwnerDisbursementReport).mockResolvedValue(mockDisbursementData);
        vi.mocked(rentalReportsApi.fetchLesseeOutstandingReport).mockResolvedValue(mockAgingData);
        vi.mocked(rentalReportsApi.fetchVehicleRevenueLicenseReport).mockResolvedValue(mockLicenseData);
    });

    it('renders FleetUtilizationReportPage with standardized design system controls', async () => {
        render(
            <MemoryRouter>
                <FleetUtilizationReportPage />
            </MemoryRouter>
        );

        expect(screen.getByText('Fleet Utilization & Vehicle Status Report')).toBeInTheDocument();
        expect(screen.getByLabelText('Start Date')).toBeInTheDocument();
        expect(screen.getByLabelText('End Date')).toBeInTheDocument();
        expect(screen.getByLabelText('Ownership Type')).toBeInTheDocument();
        expect(screen.getByLabelText('Custody Status')).toBeInTheDocument();

        await waitFor(() => {
            expect(screen.getByText('Total Fleet')).toBeInTheDocument();
            expect(screen.getByText('WP-CAS-1234')).toBeInTheDocument();
            expect(screen.getByText('Apex Logistics')).toBeInTheDocument();
        });
    });

    it('renders OwnerDisbursementReportPage and navigates accessible Tabs', async () => {
        render(
            <MemoryRouter>
                <OwnerDisbursementReportPage />
            </MemoryRouter>
        );

        expect(screen.getByText('Vehicle Owner Disbursement & Payables Ledger')).toBeInTheDocument();
        expect(screen.getByLabelText('Settlement Period From')).toBeInTheDocument();
        expect(screen.getByLabelText('Settlement Period Until')).toBeInTheDocument();

        // Verify accessible tabs are rendered
        const summaryTab = screen.getByRole('tab', { name: 'Owner Settlements Summary' });
        const detail1Tab = screen.getByRole('tab', { name: 'Statement of Accounts (Detail I)' });
        const whtTab = screen.getByRole('tab', { name: 'Withholding Tax (WHT) Ledger (:3305-000 WHT)' });

        expect(summaryTab).toHaveAttribute('aria-selected', 'true');
        expect(detail1Tab).toHaveAttribute('aria-selected', 'false');

        // Switch tab
        fireEvent.click(detail1Tab);
        await waitFor(() => {
            expect(rentalReportsApi.fetchOwnerDisbursementReport).toHaveBeenCalledWith(
                expect.objectContaining({ report_type: 'statement_detail_1' })
            );
        });

        fireEvent.click(whtTab);
        await waitFor(() => {
            expect(rentalReportsApi.fetchOwnerDisbursementReport).toHaveBeenCalledWith(
                expect.objectContaining({ report_type: 'wht_ledger' })
            );
        });
    });

    it('renders LesseeOutstandingReportPage with date picker and bucket filter', async () => {
        render(
            <MemoryRouter>
                <LesseeOutstandingReportPage />
            </MemoryRouter>
        );

        expect(screen.getByText("Lessee's Outstanding Age Analysis")).toBeInTheDocument();
        expect(screen.getByLabelText('As-Of Date')).toBeInTheDocument();
        expect(screen.getByLabelText('Aging Category')).toBeInTheDocument();

        await waitFor(() => {
            expect(screen.getByText('Active Lessees')).toBeInTheDocument();
            expect(screen.getByText('Total Outstanding')).toBeInTheDocument();
            expect(screen.getByText('No outstanding lessee balances found for the selected as-of date.')).toBeInTheDocument();
        });
    });

    it('renders VehicleRevenueLicenseReportPage with license status and expiration count', async () => {
        render(
            <MemoryRouter>
                <VehicleRevenueLicenseReportPage />
            </MemoryRouter>
        );

        expect(screen.getByText('Vehicle Revenue Licence Expire Report')).toBeInTheDocument();
        expect(screen.getByLabelText('Status Filter')).toBeInTheDocument();

        await waitFor(() => {
            expect(screen.getByText('Expired Licences')).toBeInTheDocument();
            expect(screen.getByText('WP-CAS-1234')).toBeInTheDocument();
            expect(screen.getByText('AutoERP Fleet')).toBeInTheDocument();
        });
    });
});
