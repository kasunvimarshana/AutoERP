import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { cwd } from 'node:process';
import { describe, expect, it } from 'vitest';
import { emptyLineForm, lineFormToPayload } from './components/line-editor/lineForm';

const source = (path: string) => readFileSync(resolve(cwd(), path), 'utf8');
const detailPageSource = source('resources/js/modules/vehicle-service/pages/VehicleServiceJobDetailPage.tsx');
const lineEditorSource = source('resources/js/modules/vehicle-service/components/VehicleServiceLineEditor.tsx');
const lineFormSource = source('resources/js/modules/vehicle-service/components/line-editor/VehicleServiceLineForm.tsx');
const lineItemFieldsSource = source('resources/js/modules/vehicle-service/components/line-editor/LineItemFields.tsx');
const lineWriteServiceSource = source('app/Modules/VehicleService/Services/VehicleServiceLineWriteService.php');
const statusServiceSource = source('app/Modules/VehicleService/Services/VehicleServiceStatusService.php');

describe('Vehicle Service job-line inventory flow', () => {
    it('keeps warehouse and location out of the line payload', () => {
        const form = emptyLineForm();
        form.issueWarehouse = { id: 4, code: 'MAIN', name: 'Main warehouse' };
        form.issueLocation = { id: 9, code: 'RECEIVING', name: 'Receiving' };

        const payload = lineFormToPayload(form);

        expect(payload).not.toHaveProperty('issueWarehouse');
        expect(payload).not.toHaveProperty('issueLocation');
        expect(payload).not.toHaveProperty('warehouse_id');
        expect(payload).not.toHaveProperty('warehouse_location_id');
    });

    it('reserves stock when a line is created and removes manual issue actions', () => {
        expect(lineEditorSource).toContain('<VehicleServiceLineItemLookup');
        expect(lineEditorSource).toContain('createVehicleServiceLine(jobId');
        expect(lineWriteServiceSource).toContain('reserveLineTree($job, $line, $actorId)');
        expect(lineEditorSource).not.toContain('<VehicleServiceInventoryIssueDrawer');
        expect(lineEditorSource).not.toContain('Issue all ready items');
        expect(lineFormSource).not.toContain('Add & issue stock');
    });

    it('issues every reservation inside the job-start transition', () => {
        expect(statusServiceSource).toContain('issueReservedOnStart($job, $changedBy)');
        expect(lineEditorSource).toContain("line.inventory_movement_id == null ? 'Reserved' : 'Issued'");
        expect(lineEditorSource).toContain('It will be issued automatically when the job starts.');
    });

    it('releases and recreates reservations on edits and releases them before deletion', () => {
        expect(lineWriteServiceSource).toContain('releaseLineTree($job, $line, $actorId)');
        expect(lineWriteServiceSource).toContain('reserveLineTree($job, $line->refresh(), $actorId)');
        expect(lineWriteServiceSource.indexOf('releaseLineTree($job, $line, $actorId)'))
            .toBeLessThan(lineWriteServiceSource.lastIndexOf('$line->delete()'));
    });

    it('shows available and reserved quantities with reorder-level color states', () => {
        expect(lineItemFieldsSource).toContain('reserved_stock_quantity');
        expect(lineItemFieldsSource).toContain('reorder_level');
        expect(lineItemFieldsSource).toContain("return 'font-semibold text-amber-700'");
        expect(lineItemFieldsSource).toContain("return 'font-semibold text-rose-700'");
    });

    it('preserves inventory-only visibility without a separate Inventory tab', () => {
        expect(lineEditorSource).toContain('!canViewLines && canViewInventory');
        expect(lineEditorSource).toContain('Showing stock reserved for this job.');
        expect(lineEditorSource).toContain('listInventoryIssueLines(jobId, {}, signal)');
        expect(detailPageSource).not.toContain('VehicleServiceInventoryIssueTab');
        expect(detailPageSource).not.toContain("{ id: 'inventory', label: 'Inventory' }");
        expect(detailPageSource).toContain('onVersionChanged={updateJobVersion}');
    });
});
