import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { cwd } from 'node:process';
import { describe, expect, it } from 'vitest';
import { emptyLineForm, lineFormToPayload } from './components/line-editor/lineForm';

const source = (path: string) => readFileSync(resolve(cwd(), path), 'utf8');
const detailPageSource = source('resources/js/modules/vehicle-service/pages/VehicleServiceJobDetailPage.tsx');
const lineEditorSource = source('resources/js/modules/vehicle-service/components/VehicleServiceLineEditor.tsx');
const inventoryIssueDrawerSource = source('resources/js/modules/vehicle-service/components/VehicleServiceInventoryIssueDrawer.tsx');
const lineFormSource = source('resources/js/modules/vehicle-service/components/line-editor/VehicleServiceLineForm.tsx');

describe('Vehicle Service job-line inventory flow', () => {
    it('keeps warehouse and location as UI orchestration context only', () => {
        const form = emptyLineForm();
        form.issueWarehouse = { id: 4, code: 'MAIN', name: 'Main warehouse' };
        form.issueLocation = { id: 9, code: 'RECEIVING', name: 'Receiving' };

        const payload = lineFormToPayload(form);

        expect(payload).not.toHaveProperty('issueWarehouse');
        expect(payload).not.toHaveProperty('issueLocation');
        expect(payload).not.toHaveProperty('warehouse_id');
        expect(payload).not.toHaveProperty('warehouse_location_id');
    });

    it('uses direct item selection for creation and keeps stock issue as a permitted row action', () => {
        expect(lineEditorSource).toContain('<VehicleServiceLineItemLookup');
        expect(lineEditorSource).toContain('lineValueWithItem(emptyLineForm(), item)');
        expect(lineEditorSource).toContain('createVehicleServiceLine(jobId');
        expect(lineEditorSource).toContain('canIssueInventory && canIssueLine(row.line)');
        expect(lineEditorSource).toContain('<VehicleServiceInventoryIssueDrawer');
        expect(lineFormSource).not.toContain('Add & issue stock');
        expect(lineEditorSource).toContain('vehicleServicePermissions.inventoryView');
        expect(lineEditorSource).toContain('vehicleServicePermissions.inventoryIssue');
    });

    it('adds inventory items as pending lines before the explicit stock-issue workflow', () => {
        expect(lineEditorSource).toContain('onChanged(nextLines, mutation.rowVersion, mutation.jobTotals)');
        expect(lineEditorSource).toContain("setToast('Job line added.')");
        expect(lineEditorSource).toContain("line.inventory_movement_id == null ? 'Pending issue' : 'Issued'");
        expect(lineEditorSource).toContain('onIssued={(nextVersion) => void handleStockIssued(nextVersion)}');
    });

    it('keeps stock issue recovery available for combo child inventory lines', () => {
        expect(lineEditorSource).toContain('canManageLines && !row.isComboChild');
        expect(lineEditorSource).toContain('canIssueInventory && canIssueLine(row.line)');
        expect(lineEditorSource).toContain("line.inventory_movement_id == null ? 'Pending issue' : 'Issued'");
    });

    it('preserves inventory-only storekeeper access inside Job Lines', () => {
        expect(lineEditorSource).toContain('!canViewLines && canViewInventory');
        expect(lineEditorSource).toContain('listInventoryIssueLines(jobId, {}, signal)');
        expect(lineEditorSource).toContain('Showing inventory lines that are still pending stock issue.');
        expect(lineEditorSource).toContain('onVersionChanged(nextVersion)');
        expect(detailPageSource).toContain('onVersionChanged={updateJobVersion}');
    });

    it('removes only the Inventory tab while keeping Inventory APIs owned by their existing module', () => {
        expect(detailPageSource).not.toContain('VehicleServiceInventoryIssueTab');
        expect(detailPageSource).not.toContain("{ id: 'inventory', label: 'Inventory' }");
        expect(detailPageSource).not.toContain("tabs.openedTabs.has('inventory')");
        expect(inventoryIssueDrawerSource).toContain('issueVehicleServiceInventory');
        expect(lineEditorSource).toContain('listInventoryIssueLines');
        expect(lineEditorSource).toContain('listVehicleServiceLines');
    });
});
