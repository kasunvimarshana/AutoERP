import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { MemoryRouter } from 'react-router-dom';
import VehicleAvailabilityPage from './VehicleAvailabilityPage';
import * as vehicleUseApi from './vehicleUseApi';

vi.mock('./vehicleUseApi', async (importOriginal) => {
    const actual = await importOriginal<typeof vehicleUseApi>();
    return {
        ...actual,
        fetchAvailableVehicles: vi.fn(),
    };
});

const mockAvailableVehicles: vehicleUseApi.AvailableVehicleOption[] = [
    {
        id: 101,
        vehicle_number: 'CAB-9988',
        registration_number: 'WP-CAB-9988',
        display_label: 'WP-CAB-9988 (Toyota Axio)',
        make: 'Toyota',
        model: 'Axio',
        category: 'Sedan',
        category_id: 1,
        odometer_reading: '45200.000000',
    },
];

describe('VehicleAvailabilityPage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.mocked(vehicleUseApi.fetchAvailableVehicles).mockResolvedValue(mockAvailableVehicles);
    });

    it('renders the query form with header and triggers availability search', async () => {
        render(
            <MemoryRouter>
                <VehicleAvailabilityPage />
            </MemoryRouter>
        );

        expect(screen.getByText('Vehicle availability')).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Check Availability' })).toBeInTheDocument();

        fireEvent.click(screen.getByRole('button', { name: 'Check Availability' }));

        await waitFor(() => {
            expect(vehicleUseApi.fetchAvailableVehicles).toHaveBeenCalled();
            expect(screen.getAllByText('WP-CAB-9988 (Toyota Axio)').length).toBeGreaterThan(0);
            expect(screen.getAllByText('Sedan').length).toBeGreaterThan(0);
            expect(screen.getAllByText('Toyota Axio').length).toBeGreaterThan(0);
            expect(screen.getAllByRole('button', { name: 'Reserve' }).length).toBeGreaterThan(0);
            expect(screen.getAllByRole('button', { name: 'Dispatch' }).length).toBeGreaterThan(0);
        });
    });

    it('filters results by search input', async () => {
        render(
            <MemoryRouter>
                <VehicleAvailabilityPage />
            </MemoryRouter>
        );

        fireEvent.click(screen.getByRole('button', { name: 'Check Availability' }));

        await waitFor(() => {
            expect(screen.getAllByText('WP-CAB-9988 (Toyota Axio)').length).toBeGreaterThan(0);
        });

        const searchInput = screen.getByPlaceholderText('Registration, make, model or category');
        fireEvent.change(searchInput, { target: { value: 'NonexistentVehicle' } });

        await waitFor(() => {
            expect(screen.getByText('No vehicles are available for the selected time window.')).toBeInTheDocument();
        });
    });
});
