import { describe, expect, it } from 'vitest';
import { formatDate } from './formatDate';

describe('formatDate', () => {
    it('formats civil dates without UTC-to-local timezone shifting', () => {
        expect(formatDate('2026-05-15')).toContain('May');
        expect(formatDate('2026-05-15')).toContain('15');
        expect(formatDate('2026-05-15')).toContain('2026');
    });

    it('returns hyphen for empty or null inputs', () => {
        expect(formatDate(null)).toBe('-');
        expect(formatDate(undefined)).toBe('-');
        expect(formatDate('')).toBe('-');
    });

    it('formats ISO timestamps correctly', () => {
        const formatted = formatDate('2026-06-15T20:00:00.000Z');
        expect(formatted).not.toBe('-');
        expect(formatted).toContain('2026');
    });

    it('returns raw string for unrecognized format', () => {
        expect(formatDate('not-a-valid-date')).toBe('not-a-valid-date');
    });
});
