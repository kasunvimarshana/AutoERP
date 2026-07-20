import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';

const lookupsSource = readFileSync(
    resolve(process.cwd(), 'resources/js/modules/vehicle-rental/components/VehicleRentalLookups.tsx'),
    'utf8',
);
const dialogsSource = readFileSync(
    resolve(process.cwd(), 'resources/js/modules/vehicle-rental/components/RentalAssignmentDialogs.tsx'),
    'utf8',
);

describe('Vehicle Rental assignment source selection', () => {
    it('requests only source assignments matching the selected vehicle and period', () => {
        expect(lookupsSource).toContain('vehicle_id: isAssignmentSource ? vehicleId ?? undefined : undefined');
        expect(lookupsSource).toContain('date_from: isAssignmentSource && startsAt ? startsAt : undefined');
        expect(lookupsSource).toContain('date_to: isAssignmentSource && endsAt ? endsAt : undefined');
    });

    it('clears stale source relationships when vehicle or period changes', () => {
        expect(dialogsSource).toContain('vehicleId={state.vehicle?.id ?? null}');
        expect(dialogsSource).toContain('startsAt={state.startsAt}');
        expect(dialogsSource).toContain('endsAt={state.endsAt}');
        expect(dialogsSource).not.toContain('useState<RentalReference | null>(() => assignment?.source_assignment ?? null)');
        expect(dialogsSource.match(/sourceAssignment: null/g)?.length ?? 0).toBeGreaterThanOrEqual(4);
        expect(dialogsSource).toContain('setSourceAssignment(null)');
    });
});
