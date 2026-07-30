import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { cwd } from 'node:process';
import { describe, expect, it } from 'vitest';

const lookupsSource = readFileSync(
    resolve(cwd(), 'resources/js/modules/vehicle-rental/components/VehicleRentalLookups.tsx'),
    'utf8',
);
const dialogsSource = readFileSync(
    resolve(cwd(), 'resources/js/modules/vehicle-rental/components/RentalAssignmentDialog.tsx'),
    'utf8',
);
const workspaceSource = readFileSync(
    resolve(cwd(), 'resources/js/modules/vehicle-rental/pages/VehicleRentalWorkspacePage.tsx'),
    'utf8',
);

describe('Vehicle Rental assignment source selection', () => {
    it('requests source assignments matching the selected vehicle and explicit planning period', () => {
        expect(lookupsSource).toContain('vehicle_id: isAssignmentSource ? vehicleId ?? undefined : undefined');
        expect(lookupsSource).toContain('date_from: isAssignmentSource && startsAt ? localDateTimeToOffsetIso(startsAt) : undefined');
        expect(lookupsSource).toContain('date_to: isAssignmentSource && endsAt ? localDateTimeToOffsetIso(endsAt) : undefined');
        expect(lookupsSource).not.toContain("assignment_status: 'active'");
    });

    it('invalidates loaded source options when vehicle or planning dates change', () => {
        expect(lookupsSource).toContain('const lookupContextKey = isAssignmentSource');
        expect(lookupsSource).toContain("[lookupPurpose, side ?? '', vehicleId ?? '', startsAt ?? '', endsAt ?? ''].join(':')");
        expect(lookupsSource).toContain('<ReferenceLookup key={lookupContextKey}');
    });

    it('resolves customer owner sources from request-keyed vehicle data without stale effects', () => {
        expect(dialogsSource).toContain("listRentalAssignmentLookup('assignment-source'");
        expect(dialogsSource).toContain('vehicle_id: requestedVehicleId');
        expect(dialogsSource).toContain('sourceLookup.vehicleId === selectedVehicleId');
        expect(dialogsSource).toContain('const resolvedSourceAssignment = selectedSourceCandidate');
        expect(dialogsSource).toContain('fitAssignmentDateTimes(state.startsAt, state.endsAt, bounds)');
        expect(dialogsSource).toContain('sourceAssignment: null');
        expect(dialogsSource).toContain('setSourceAssignment(null)');
        expect(dialogsSource).not.toContain('setSourceCandidates(null)');
    });

    it('starts vehicle selection from an active agreement without reselecting its side or identity', () => {
        expect(workspaceSource).toContain('vehicleRentalPermissions.assignmentsManage');
        expect(workspaceSource).toContain('onClick={() => setAssignmentAgreement(row)}>Select vehicle</Button>');
        expect(workspaceSource).toContain('agreement={assignmentAgreement ? agreementReference(assignmentAgreement) : null}');
        expect(workspaceSource).toContain("side={assignmentAgreement?.kind === 'owner' ? 'owner_supply' : 'customer_use'}");
        expect(workspaceSource).toContain('lockAgreement');
        expect(dialogsSource).toContain('initialAssignmentState(side, agreement, assignment)');
        expect(dialogsSource).toContain('disabled={lockAgreement}');
        expect(dialogsSource).toContain("? 'Select vehicle'");
        expect(dialogsSource).toContain("? 'Update assignment'");
    });
});
