import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { MemoryRouter } from 'react-router-dom';
import RentalRunningChartReportPage from './RentalRunningChartReportPage';
import * as runningChartApi from './runningChartApi';
import * as agreementApi from './agreementApi';
import type { Agreement } from './agreements';

vi.mock('./runningChartApi', async (importOriginal) => {
    const actual = await importOriginal<typeof runningChartApi>();
    return {
        ...actual,
        fetchRunningChartReport: vi.fn(),
    };
});

vi.mock('./agreementApi', async (importOriginal) => {
    const actual = await importOriginal<typeof agreementApi>();
    return {
        ...actual,
        getAgreement: vi.fn(),
        searchCustomerAgreements: vi.fn(),
    };
});

const mockReportData: runningChartApi.RunningChartReportData = {
    header: {
        company_name: 'AT Tours & Transport (Pvt) Ltd',
        report_title: 'RUNNING CHART FOR LESSEE - Monthly Basis Vehicles',
        rental_mode: 'WITH DRIVER MONTHLY BASIS',
        client_name: 'Lanka Industrial Corp',
        duration: '01/11/2026 To 30/11/2026',
        start_date: '2026-11-01',
        end_date: '2026-11-30',
        page: 1,
        report_date: '03/11/2026',
        agreement_number: 'CA-TEST-001',
        agreement_id: 1,
        agreement_vehicle_number: 'WP-CAB-1234',
    },
    rows: [
        {
            date: '01/11/2026',
            raw_date: '2026-11-01',
            start_odometer: '1000',
            finish_odometer: '1120',
            comparative_mileage: 120,
            cumulative_mileage: 120,
            on_time: '08:00 AM',
            off_time: '06:00 PM',
            ot_hours: '2.00',
            ot_type: 'N',
            night_out: '',
            pass: '',
            garage_km: '10.0',
            remarks: 'Site inspection trip',
            driver_name: 'K. Perera',
            is_active_day: true,
        },
    ],
    summary: {
        total_comparative_mileage: 120,
        final_cumulative_mileage: 120,
        total_normal_ot_hours: '2.00',
        total_double_ot_hours: '0.00',
        total_triple_ot_hours: '0.00',
        total_ot_hours: '2.00',
        total_night_out: 0,
        total_garage_km: 10,
        total_pass: '0.00',
        included_km: 100,
        excess_km: 20,
    },
    footer: {
        software_provider: 'Software Provided by : AutoERP Computer Systems',
    },
};

const mockCustomerAgreement = {
    id: 1,
    reference: 'CA-TEST-001',
    party: { id: 10, name: 'Lanka Industrial Corp' },
} as unknown as Agreement;

describe('RentalRunningChartReportPage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.mocked(runningChartApi.fetchRunningChartReport).mockResolvedValue(mockReportData);
        vi.mocked(agreementApi.getAgreement).mockResolvedValue(mockCustomerAgreement);
        vi.mocked(agreementApi.searchCustomerAgreements).mockResolvedValue({
            data: [{ id: 1, name: 'CA-TEST-001 — Lanka Industrial Corp' }],
            links: { first: '', last: '', prev: null, next: null },
            meta: { current_page: 1, from: 1, to: 1, last_page: 1, per_page: 15, total: 1, path: '' },
        });
    });

    it('renders report parameters form and loads matrix sheet when agreement_id is provided', async () => {
        render(
            <MemoryRouter initialEntries={['/vehicle-rental/running-chart/report?agreement_id=1']}>
                <RentalRunningChartReportPage />
            </MemoryRouter>
        );

        expect(screen.getByText('Running Chart report (31-day matrix sheet)')).toBeInTheDocument();

        await waitFor(() => {
            expect(agreementApi.getAgreement).toHaveBeenCalledWith('customer', 1);
            expect(runningChartApi.fetchRunningChartReport).toHaveBeenCalledWith({
                agreement_id: 1,
                start_date: expect.any(String),
                end_date: expect.any(String),
            });
            expect(screen.getByText('RUNNING CHART FOR LESSEE - Monthly Basis Vehicles')).toBeInTheDocument();
            expect(screen.getByText('Lanka Industrial Corp')).toBeInTheDocument();
            expect(screen.getByText('WP-CAB-1234')).toBeInTheDocument();
            expect(screen.getByText('Print Sheet')).toBeInTheDocument();
            expect(screen.getByText('Download PDF')).toBeInTheDocument();
            expect(screen.getByText('Excess Distance')).toBeInTheDocument();
            expect(screen.getByText((content, element) => {
                return element?.textContent === '20 km';
            })).toBeInTheDocument();
            expect(screen.getByRole('button', { name: 'Generate Sheet' })).toBeInTheDocument();
        });
    });

    it('generates report on manual form submission with LookupSelect', async () => {
        render(
            <MemoryRouter initialEntries={['/vehicle-rental/running-chart/report']}>
                <RentalRunningChartReportPage />
            </MemoryRouter>
        );

        const agreementCombobox = screen.getByRole('combobox', { name: 'Customer Agreement' });
        fireEvent.change(agreementCombobox, { target: { value: 'CA-TEST' } });
        fireEvent.click(await screen.findByRole('option', { name: 'CA-TEST-001 — Lanka Industrial Corp' }));

        fireEvent.click(screen.getByRole('button', { name: 'Generate Sheet' }));

        await waitFor(() => {
            expect(runningChartApi.fetchRunningChartReport).toHaveBeenCalledWith({
                agreement_id: 1,
                start_date: expect.any(String),
                end_date: expect.any(String),
            });
            expect(screen.getByText('Lanka Industrial Corp')).toBeInTheDocument();
        });
    });
});
