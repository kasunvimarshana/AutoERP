import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';

const adjustmentSource = readFileSync(resolve(process.cwd(), 'resources/js/modules/inventory/components/workflows/AdjustmentsTab.tsx'), 'utf8');

describe('Inventory adjustment location selector', () => {
    it('shows a warehouse-filtered location selector in the primary adjustment form', () => {
        expect(adjustmentSource).toContain('label="Location"');
        expect(adjustmentSource).toContain('searchWarehouseLocations(params, warehouse?.id)');
        expect(adjustmentSource).toContain('disabled={!warehouse}');
    });

    it('clears dependent location and serial selections when the warehouse changes', () => {
        expect(adjustmentSource).toContain('warehouseLocation: null, serial: null');
    });

    it('uses the existing adjustment location payload and avoids a duplicate optional field', () => {
        expect(adjustmentSource).toContain('warehouse_location_id: dimensions.warehouseLocation?.id');
        expect(adjustmentSource).toContain('includeLocation={false}');
    });
});
