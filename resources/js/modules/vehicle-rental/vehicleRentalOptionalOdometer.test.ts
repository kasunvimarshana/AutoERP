import { describe, expect, it } from 'vitest';
import { rentalAssignmentOption } from './components/VehicleRentalLookups';
import type { RentalAssignment } from './vehicleRentalTypes';

describe('Vehicle Rental optional odometer workflow', () => {
    it('treats a missing or unavailable vehicle reading as unavailable', () => {
        expect(rentalAssignmentOption(assignment()).odometerAvailable).toBe(false);
        expect(rentalAssignmentOption(assignment(null)).odometerAvailable).toBe(false);
    });

    it('preserves zero as a real available odometer reading', () => {
        const option = rentalAssignmentOption(assignment('0.000000'));

        expect(option.odometerAvailable).toBe(true);
        expect(option.vehicleOdometerReading).toBe('0.000000');
    });
});

function assignment(odometerReading?: string | null): RentalAssignment {
    return {
        id: 1,
        row_version: 1,
        side: 'customer_use',
        status: 'active',
        agreement: { id: 2, code: 'CRA-001', name: 'CRA-001' },
        vehicle: odometerReading === undefined ? null : {
            id: 3,
            code: 'VEH-001',
            name: 'ABC-1234',
            odometer_reading: odometerReading,
        },
        driver: null,
        source_assignment: null,
        replaces_assignment: null,
        starts_at: '2026-07-01T08:00:00Z',
        ends_at: null,
        handover_odometer: null,
        return_odometer: null,
        self_drive: true,
        replacement_reason: null,
        custody_events: [],
    };
}
