import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { cwd } from 'node:process';
import { describe, expect, it } from 'vitest';
import { emptyLineForm, lineFormToPayload } from './components/line-editor/lineForm';

const source = (path: string) => readFileSync(resolve(cwd(), path), 'utf8');
const detailPageSource = source('resources/js/modules/vehicle-service/pages/VehicleServiceJobDetailPage.tsx');
const lineEditorSource = source('resources/js/modules/vehicle-service/components/VehicleServiceLineEditor.tsx');
const lineFormSource = source('resources/js/modules/vehicle-service/components/line-editor/VehicleServiceLineForm.tsx');

describe('Vehicle Service job-line inventory flow', () => {
    it('keeps warehouse and location as UI orchestration context only', () => {
        const form = emptyLineForm();
        form.issueWarehouse = { id: 4, code: 'MAIN', name: 'Main warehouse' };
        form.issueLocation = { id: 9, code: 'RECEIVING', name: 'Receiving' };

        const payload = lineFormToPayload(form) as Record<string, unknown>;

        expect(payload).not.toHaveProperty('issueWarehouse');
        expect(payload).not.toHaveProperty('issueLocation');
        expect(payload).not.toHaveProperty('warehouse_id');
        expect(payload).not.toHaveProperty('warehouse_location_id');
    });

    it('offers add-and-issue only for permitted newly created inventory lines', () => {
        expect(lineFormSource).toContain("canIssueInventory && mode === 'create' && draft.source === 'inventory_item'");
        expect(lineFormSource).toContain('Add & issue stock');
        expect(lineFormSource).toContain('draft.issueWarehouse !== null');
        expect(lineFormSource).toContain('draft.issueLocation !== null');
        expect(lineEditorSource).toContain('vehicleServicePermissions.inventoryView');
        expect(lineEditorSource).toContain('vehicleServicePermissions.inventoryIssue');
    });

    it('uses the existing Inventory issue API after line creation and preserves pending recovery on failure', () => {
        expect(lineEditorSource).toContain('const lineVersion = expectedVersion + 1');
        expect(lineEditorSource).toContain('expected_version: lineVersion');
        expect(lineEditorSource).toContain('line_ids: [saved.id]');
        expect(lineEditorSource).toContain('Job line added. Stock issue is still pending.');
        expect(lineEditorSource).toContain('onChanged(nextLines, lineVersion)');
    });

    it('keeps stock issue recovery available for combo child inventory lines', () => {
        expect(lineEditorSource).toContain('onEdit={row.isComboChild ? undefined');
        expect(lineEditorSource).toContain('canIssueInventory && canIssueLine(row.line)');
        expect(lineEditorSource).toContain("line.inventory_movement_id == null ? 'Pending issue' : 'Issued'");
    });

    it('removes only the Inventory tab while keeping Inventory APIs owned by their existing module', () => {
        expect(detailPageSource).not.toContain('VehicleServiceInventoryIssueTab');
        expect(detailPageSource).not.toContain("{ id: 'inventory', label: 'Inventory' }");
        expect(detailPageSource).not.toContain("tabs.openedTabs.has('inventory')");
        expect(lineEditorSource).toContain('issueVehicleServiceInventory');
        expect(lineEditorSource).toContain('listVehicleServiceLines');
    });
});
