import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';

function pageSource(): string {
    return readFileSync(
        resolve(
            process.cwd(),
            'resources/js/modules/vehicle-rental/pages/RentalRunningChartPage.tsx',
        ),
        'utf8',
    );
}

describe('Rental running chart physical approval review contract', () => {
    it('does not expose physical approval or rejection from the summary row', () => {
        const source = pageSource();

        expect(source).toContain('Review usage and facts');
        expect(source).not.toContain('transition(row, "approved")');
        expect(source).not.toContain('transition(row, "rejected")');
    });

    it('offers submitted physical transitions only from the selected usage review panel', () => {
        const source = pageSource();

        expect(source).toContain('selectedUsage.status === "submitted"');
        expect(source).toContain('transition(selectedUsage, "approved")');
        expect(source).toContain('transition(selectedUsage, "rejected")');
        expect(source).toContain('Physical approval or rejection note');
    });
});
