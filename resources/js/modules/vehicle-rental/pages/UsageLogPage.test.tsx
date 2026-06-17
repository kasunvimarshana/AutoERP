import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ApiError } from '@/shared/api/apiError';
import UsageLogPage from './UsageLogPage';

const apiMocks = vi.hoisted(() => ({
    changeRunningChartTripStatus: vi.fn(),
    createRunningChartTrip: vi.fn(),
    deleteRunningChartTrip: vi.fn(),
    getRunningChartContext: vi.fn(),
    listRunningChartAgreements: vi.fn(),
    listRunningChartTrips: vi.fn(),
    previewRunningChart: vi.fn(),
    submitRunningChartDaily: vi.fn(),
    updateRunningChartTrip: vi.fn(),
}));

vi.mock('../vehicleRentalApi', () => apiMocks);

vi.mock('@/modules/auth/AuthProvider', () => ({
    useAuth: () => ({
        user: { id: 1, name: 'Recorder', email: 'recorder@example.test', roles: [], permissions: [
            'vehicle-rental.usage.record',
            'vehicle-rental.usage.approve',
            'vehicle-rental.usage.classify-holiday',
        ] },
        token: 'token',
        tenant: { id: 1, name: 'Tenant' },
        organizationUnit: { id: 1, name: 'Branch' },
        roles: [],
        permissions: [
            'vehicle-rental.usage.record',
            'vehicle-rental.usage.approve',
            'vehicle-rental.usage.classify-holiday',
        ],
        enabledModules: ['vehicle-rental'],
        isAuthenticated: true,
        isLoading: false,
        login: vi.fn(),
        logout: vi.fn(),
        loadCurrentUser: vi.fn(),
    }),
}));

const customerAgreement = {
    id: 101,
    name: 'AGR-CUSTOMER / Customer One / REG-1',
    agreement_id: 11,
    agreement_vehicle_id: 101,
    agreement_number: 'AGR-CUSTOMER',
    direction: 'outbound',
    party_type: 'customer',
    party_id: 201,
    party_name: 'Customer One',
    vehicle_id: 301,
    vehicle_registration: 'REG-1',
    rental_type: 'daily',
    billing_cycle: 'daily',
    start_at: '1970-01-01T00:00:00.000000Z',
    expected_end_at: '2099-12-31T00:00:00.000000Z',
    allocation_from: '1970-01-01T00:00:00.000000Z',
    allocation_to: '2099-12-31T00:00:00.000000Z',
    status: 'active',
};

const ownerAgreement = {
    ...customerAgreement,
    id: 102,
    name: 'AGR-OWNER / Supplier One / REG-1',
    agreement_id: 12,
    agreement_vehicle_id: 102,
    agreement_number: 'AGR-OWNER',
    direction: 'inbound',
    party_type: 'supplier',
    party_id: 202,
    party_name: 'Supplier One',
};

describe('UsageLogPage running chart state', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        ensureRandomUUID();
        apiMocks.listRunningChartAgreements.mockImplementation((params: { side?: string }) => Promise.resolve({
            data: params.side === 'lessor' ? [ownerAgreement] : [customerAgreement],
            meta: { current_page: 1, last_page: 1, per_page: 20, total: 1 },
        }));
        apiMocks.getRunningChartContext.mockResolvedValue(runningChartContext());
        apiMocks.listRunningChartTrips.mockResolvedValue([]);
        apiMocks.submitRunningChartDaily.mockResolvedValue([]);
        apiMocks.createRunningChartTrip.mockResolvedValue(savedTrip());
        apiMocks.updateRunningChartTrip.mockResolvedValue(savedTrip());
    });

    it('derives heading and selector mode directly from the route without loading context early', () => {
        renderPage('/vehicle-rental/running-chart?mode=linked');

        expect(screen.getByRole('heading', { name: 'Linked Running Charts — Customer + Owner' })).toBeInTheDocument();
        expect(screen.getByLabelText('Mode')).toHaveValue('linked');
        expect(apiMocks.getRunningChartContext).not.toHaveBeenCalled();
        expect(apiMocks.listRunningChartTrips).not.toHaveBeenCalled();
    });

    it('loads agreement lookup options without auto-selecting the first agreement', async () => {
        const user = userEvent.setup();
        renderPage('/vehicle-rental/running-chart?mode=lessee');

        await user.click(screen.getByLabelText('Customer agreement'));

        expect(await screen.findByRole('option', { name: /AGR-CUSTOMER/ })).toBeInTheDocument();
        expect(screen.getByLabelText('Customer agreement')).toHaveValue('');
        expect(apiMocks.getRunningChartContext).not.toHaveBeenCalled();
        expect(apiMocks.listRunningChartTrips).not.toHaveBeenCalled();
    });

    it('cancels internal mode navigation before discarding unsaved trips', async () => {
        const confirm = vi.spyOn(window, 'confirm').mockReturnValue(false);
        const user = userEvent.setup();
        renderPage('/vehicle-rental/running-chart?mode=lessee&agreement_id=11');

        await screen.findByText('Resolved context');
        await screen.findByText('No trips recorded for this working date.');
        await user.click(screen.getByRole('button', { name: 'Add Trip' }));
        await user.selectOptions(screen.getByLabelText('Mode'), 'lessor');

        expect(confirm).toHaveBeenCalledWith('You have unsaved Running Chart data.\nDiscard the changes and continue?');
        expect(screen.getByLabelText('Mode')).toHaveValue('lessee');
        expect(screen.getByRole('heading', { name: 'Customer Running Charts — Lessee' })).toBeInTheDocument();
        expect(screen.getAllByRole('button', { name: 'Edit' }).length).toBeGreaterThan(0);

        confirm.mockRestore();
    });

    it('keeps draft data visible and does not submit old ids when daily submit fails', async () => {
        apiMocks.submitRunningChartDaily.mockRejectedValue(new ApiError('Daily save failed.', 422));
        const user = userEvent.setup();
        renderPage('/vehicle-rental/running-chart?mode=lessee&agreement_id=11');

        await screen.findByText('Resolved context');
        await screen.findByText('No trips recorded for this working date.');
        await user.click(screen.getByRole('button', { name: 'Add Trip' }));
        await user.click(screen.getByRole('button', { name: 'Submit' }));

        expect(await screen.findByText('Daily save failed.')).toBeInTheDocument();
        expect(apiMocks.submitRunningChartDaily).toHaveBeenCalledOnce();
        expect(apiMocks.changeRunningChartTripStatus).not.toHaveBeenCalled();
        expect(screen.getAllByRole('button', { name: 'Edit' }).length).toBeGreaterThan(0);
    });
});

function renderPage(initialEntry: string) {
    return render(
        <MemoryRouter initialEntries={[initialEntry]}>
            <Routes>
                <Route path="/vehicle-rental/running-chart" element={<UsageLogPage />} />
            </Routes>
        </MemoryRouter>,
    );
}

function runningChartContext() {
    return {
        mode: 'lessee',
        vehicle_id: 301,
        vehicle: { id: 301, vehicle_number: 'VEH-1', registration_number: 'REG-1', odometer_reading: '1000.000000' },
        selected_agreement_id: 11,
        agreement_vehicle_link_id: null,
        last_valid_finish_odometer: '1000.000000',
        approved_cumulative_mileage: '0.000000',
        contexts: [{
            agreement_id: 11,
            agreement_vehicle_id: 101,
            agreement_number: 'AGR-CUSTOMER',
            direction: 'outbound',
            financial_side: 'revenue',
            party_type: 'customer',
            party_id: 201,
            party_name: 'Customer One',
            billing_cycle: 'daily',
            currency_id: null,
            rate_snapshot: {
                base_rate: '100.000000',
                rate_unit: 'day',
                allowed_hours: '0.000000',
                allowed_km: '100.000000',
                extra_hour_rate: '0.000000',
                extra_km_rate: '0.000000',
                overtime_rate: '0.000000',
                double_overtime_rate: '0.000000',
                night_shift_rate: '0.000000',
                weekend_rate: '0.000000',
                holiday_rate: '0.000000',
                driver_rate: '0.000000',
                outstation_rate: '0.000000',
                day_out_rate: '0.000000',
                night_out_rate: '0.000000',
                fuel_rate: '0.000000',
                waiting_hour_rate: '0.000000',
            },
        }],
    };
}

function savedTrip() {
    return {
        id: 501,
        agreement_vehicle_id: 101,
        vehicle_id: 301,
        usage_date: '2026-06-17',
        start_time: '08:00',
        end_time: '09:00',
        working_minutes: 60,
        start_odometer: '1000.000000',
        end_odometer: '1010.000000',
        distance_km: '10.000000',
        status: 'draft',
        events: [],
        contexts: [],
    };
}

function ensureRandomUUID() {
    if ('randomUUID' in crypto) return;

    Object.defineProperty(crypto, 'randomUUID', {
        value: () => `test-${Math.random().toString(16).slice(2)}`,
        configurable: true,
    });
}
