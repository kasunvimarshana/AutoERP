import { describe, expect, it } from 'vitest';
import {
    agreementDateBoundary,
    clampLocalDateTime,
    earliestLocalDateTime,
    latestLocalDateTime,
    localDateTimeToOffsetIso,
} from './rentalDateTime';

describe('Vehicle Rental datetime contract', () => {
    it('adds the browser timezone offset to a datetime-local value', () => {
        const result = localDateTimeToOffsetIso('2026-07-21T10:55');

        expect(result).toMatch(/^2026-07-21T10:55:00(?:Z|[+-]\d{2}:\d{2})$/);
        expect(new Date(result).getTime()).toBe(new Date('2026-07-21T10:55').getTime());
    });

    it('keeps an already explicit instant unchanged', () => {
        expect(localDateTimeToOffsetIso('2026-07-21T10:55:00+05:30'))
            .toBe('2026-07-21T10:55:00+05:30');
    });

    it('intersects agreement and owner-source date boundaries without magic defaults', () => {
        const agreementStart = agreementDateBoundary('2026-07-20', 'start');
        const agreementEnd = agreementDateBoundary('2026-07-30', 'end');
        const sourceStart = '2026-07-21T10:55';
        const sourceEnd = '2026-07-30T18:00';

        expect(latestLocalDateTime(agreementStart, sourceStart)).toBe(sourceStart);
        expect(earliestLocalDateTime(agreementEnd, sourceEnd)).toBe(sourceEnd);
        expect(clampLocalDateTime('2026-07-21T00:01', sourceStart, sourceEnd)).toBe(sourceStart);
    });
});
