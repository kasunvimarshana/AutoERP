import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { cwd } from 'node:process';
import { describe, expect, it } from 'vitest';

function source(path: string): string {
    return readFileSync(resolve(cwd(), path), 'utf8');
}

const workspaceSource = source('resources/js/modules/vehicle-rental/pages/VehicleRentalWorkspacePage.tsx');
const dialogSource = source('resources/js/modules/vehicle-rental/components/RentalAssignmentDialog.tsx');
const detailSource = source('resources/js/modules/vehicle-rental/components/RentalAssignmentDetailDialog.tsx');
const serviceSource = source('app/Modules/VehicleRental/Services/RentalAssignmentService.php');

describe('Vehicle Operations CRUD actions', () => {
    it('keeps operation details available to every assignment reader', () => {
        expect(workspaceSource).toContain('setViewingId(row.id)}>View</Button>');
        expect(workspaceSource).toContain('<RentalAssignmentDetailDialog');
        expect(detailSource).toContain('getRentalAssignment(assignmentId, signal)');
        expect(detailSource).toContain('Custody history');
    });

    it('keeps edit and delete limited to planned operations', () => {
        expect(workspaceSource).toContain("canManage && row.status === 'planned'");
        expect(workspaceSource).toContain('setEditing(row)}>Edit</Button>');
        expect(workspaceSource).toContain('deleteRentalAssignment(assignment.id, assignment.row_version)');
        expect(dialogSource).toContain('updateRentalAssignment(assignment.id, payload, assignment.row_version)');
    });

    it('preloads the complete planned operation into the edit form', () => {
        expect(dialogSource).toContain('agreement: assignment.agreement ?? null');
        expect(dialogSource).toContain('vehicle: assignment.vehicle ? {');
        expect(dialogSource).toContain('driver: assignment.driver ?? null');
        expect(dialogSource).toContain('startsAt: utcDateTimeToLocalInput(assignment.starts_at)');
        expect(dialogSource).toContain('endsAt: utcDateTimeToLocalInput(assignment.ends_at)');
        expect(dialogSource).toContain("handoverOdometer: assignment.handover_odometer ?? ''");
    });

    it('retains operational and dependent history', () => {
        expect(serviceSource).toContain('Only planned rental assignments can be {$action}.');
        expect(serviceSource).toContain('custodyEvents()->lockForUpdate()');
        expect(serviceSource).toContain('runningCharts()->lockForUpdate()');
        expect(serviceSource).toContain("where('source_assignment_id', $assignment->getKey())");
        expect(serviceSource).toContain("orWhere('replaces_assignment_id', $assignment->getKey())");
        expect(serviceSource).toContain('assertExpectedVersion($assignment, $expectedVersion)');
    });
});
