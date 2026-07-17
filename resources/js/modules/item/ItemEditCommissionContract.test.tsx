import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';

function source(path: string): string {
    return readFileSync(resolve(process.cwd(), path), 'utf8');
}

describe('Item edit labor commission integration', () => {
    it('shows the commission editor only for visible Vehicle Service labor commissions', () => {
        const page = source('resources/js/modules/item/ItemEditPage.tsx');

        expect(page).toContain("['commission', 'Commission']");
        expect(page).toContain("form?.item_type === 'labour' && canViewLaborCommission");
        expect(page).toContain('<LaborItemCommissionEditor');
        expect(page).toContain('canManage={canManageLaborCommission}');
    });

    it('loads and version-checks the Vehicle Service-owned commission rule', () => {
        const editor = source(
            'resources/js/modules/vehicle-service/components/LaborItemCommissionEditor.tsx',
        );

        expect(editor).toContain('getLaborItemCommissionRule(itemId, signal)');
        expect(editor).toContain('saveLaborItemCommissionRule(itemId, {');
        expect(editor).toContain('expected_version: rule?.row_version');
        expect(editor).toContain('You can review this labor commission');
    });
});
