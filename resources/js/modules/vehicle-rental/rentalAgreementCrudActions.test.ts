import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { cwd } from 'node:process';
import { describe, expect, it } from 'vitest';

function source(path: string): string {
    return readFileSync(resolve(cwd(), path), 'utf8');
}

const workspaceSource = source('resources/js/modules/vehicle-rental/pages/VehicleRentalWorkspacePage.tsx');
const detailSource = source('resources/js/modules/vehicle-rental/components/RentalAgreementDetailDialog.tsx');
const serviceSource = source('app/Modules/VehicleRental/Services/RentalAgreementService.php');

describe('Vehicle Rental agreement CRUD actions', () => {
    it('keeps agreement details available to every agreement reader', () => {
        expect(workspaceSource).toContain('>View</Button>');
        expect(workspaceSource).toContain('setViewingId(row.id)');
        expect(detailSource).toContain('getRentalAgreement(agreementId, signal)');
        expect(detailSource).toContain('Rate history');
    });

    it('keeps edit and delete limited to drafts', () => {
        expect(workspaceSource).toContain("canManage && row.status === 'draft'");
        expect(workspaceSource).toContain('>Edit</Button>');
        expect(workspaceSource).toContain('>Delete</Button>');
        expect(workspaceSource).toContain('deleteRentalAgreement(agreement.id, agreement.row_version)');
    });

    it('retains active and closed agreements as history', () => {
        expect(serviceSource).toContain('Only draft rental agreements can be deleted. Active and closed agreements must be retained as history.');
        expect(serviceSource).toContain('A rental agreement with operational or financial history cannot be deleted.');
        expect(serviceSource).toContain('$this->assertExpectedVersion($agreement, $expectedVersion)');
    });
});
