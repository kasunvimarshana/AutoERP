import { afterEach, describe, expect, it } from 'vitest';
import {
    businessDateInputValue,
    businessDateTimeInputValue,
    configureBusinessTimeZone,
} from './businessDate';

afterEach(() => configureBusinessTimeZone(null));

describe('business date utilities', () => {
    it('uses the configured business timezone instead of UTC', () => {
        configureBusinessTimeZone('Asia/Colombo');
        const instant = new Date('2026-06-15T20:00:00.000Z');

        expect(businessDateInputValue(instant)).toBe('2026-06-16');
        expect(businessDateTimeInputValue(instant)).toBe('2026-06-16T01:30');
    });

    it('applies calendar-day changes to business-local input values', () => {
        const instant = new Date('2026-12-31T20:00:00.000Z');

        expect(businessDateTimeInputValue(instant, 1, 'Asia/Colombo')).toBe('2027-01-02T01:30');
    });
});
