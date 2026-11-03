import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { MemoryRouter } from 'react-router-dom';
import RentalRunningChartReportPage from './RentalRunningChartReportPage';
import * as runningChartApi from './runningChartApi';

vi.mock('./runningChartApi', async (importOriginal) => {
    const actual = await importOriginal<typeof runningChartApi>();
    return {
        ...actual,
        fetchRunningChartReport: vi.fn(),
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

describe('RentalRunningChartReportPage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.mocked(runningChartApi.fetchRunningChartReport).mockResolvedValue(mockReportData);
    });

    it('renders report parameters form and loads matrix sheet', async () => {
        render(
            <MemoryRouter initialEntries={['/vehicle-rental/running-chart/report?agreement_id=1']}>
                <RentalRunningChartReportPage />
            </MemoryRouter>
        );

        expect(screen.getByText('Running Chart report (31-day matrix sheet)')).toBeInTheDocument();

        await waitFor(() => {
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

    it('generates report on manual form submission', async () => {
        render(
            <MemoryRouter initialEntries={['/vehicle-rental/running-chart/report']}>
                <RentalRunningChartReportPage />
            </MemoryRouter>
        );

        const agreementInput = screen.getByLabelText('Customer Agreement ID');
        fireEvent.change(agreementInput, { target: { value: '1' } });
        fireEvent.click(screen.getByRole('button', { name: 'Generate Sheet' }));

        await waitFor(() => {
            expect(runningChartApi.fetchRunningChartReport).toHaveBeenCalled();
            expect(screen.getByText('Lanka Industrial Corp')).toBeInTheDocument();
        });
    });
});
