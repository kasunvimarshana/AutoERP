import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';

const source = (path: string) => readFileSync(resolve(process.cwd(), path), 'utf8');
const panelSource = source('resources/js/modules/inventory/components/workflows/OpeningStockImportPanel.tsx');
const adjustmentSource = source('resources/js/modules/inventory/components/workflows/AdjustmentsTab.tsx');
const apiSource = source('resources/js/modules/inventory/inventoryApi.ts');

describe('Opening stock CSV import', () => {
    it('is owned by Inventory Adjustments and keeps the manual workflow available', () => {
        expect(adjustmentSource).toContain('<OpeningStockImportPanel reload={reload} />');
        expect(adjustmentSource).toContain('onSubmit={submit}');
    });

    it('requires warehouse context and previews before draft creation', () => {
        expect(panelSource).toContain('disabled={!warehouse}');
        expect(panelSource).toContain('Preview CSV');
        expect(panelSource).toContain("!preview?.can_create");
        expect(panelSource).toContain('Create draft adjustment');
    });

    it('uses dedicated template, preview, and import endpoints', () => {
        expect(apiSource).toContain('/opening-stock-import/template');
        expect(apiSource).toContain('/opening-stock-import/preview');
        expect(apiSource).toContain('`${endpoints.inventory}/opening-stock-import`');
    });
});
