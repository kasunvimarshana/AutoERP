import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';

function editorSource(): string {
    return readFileSync(
        resolve(
            process.cwd(),
            'resources/js/modules/vehicle-rental/components/RentalUsageFactEditor.tsx',
        ),
        'utf8',
    );
}

describe('Rental commercial usage fact workflow', () => {
    it('shows derived facts by default and edits only through an explicit variance action', () => {
        const source = editorSource();

        expect(source).toContain('Derived from the approved physical Running Chart');
        expect(source).toContain('Record commercial variance');
        expect(source).toContain('Save variance');
        expect(source).toContain('Cancel variance');
        expect(source).not.toContain('Save facts');
    });
});
