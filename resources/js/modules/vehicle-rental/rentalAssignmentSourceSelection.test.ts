import { readFileSync } from 'node:fs';
import { describe, expect, it } from 'vitest';

const lookupsSource = readFileSync(
    new URL('./components/VehicleRentalLookups.tsx', import.meta.url),
    'utf8',
);
const dialogsSource = readFileSync(
    new URL('./components/RentalAssignmentDialogs.tsx', import.meta.url),
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
