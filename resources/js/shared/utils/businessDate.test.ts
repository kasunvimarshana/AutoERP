import { afterEach, describe, expect, it } from 'vitest';
import {
    businessDateInputValue,
    businessDateTimeInputValue,
    businessDateTimeToOffsetTimestamp,
    businessTimeZone,
    configureBusinessTimeZone,
    formatBusinessDateTime,
} from './businessDate';

afterEach(() => configureBusinessTimeZone(null));

describe('business date utilities', () => {
    it('uses the configured business timezone instead of UTC', () => {
        configureBusinessTimeZone('Asia/Colombo');
        const instant = new Date('2026-06-15T20:00:00.000Z');

        expect(businessTimeZone()).toBe('Asia/Colombo');
        expect(businessDateInputValue(instant)).toBe('2026-06-16');
        expect(businessDateTimeInputValue(instant)).toBe('2026-06-16T01:30');
        expect(businessDateTimeInputValue(instant, 0, undefined, true)).toBe('2026-06-16T01:30:00');
    });

    it('formats timestamps in the configured business timezone and handles invalid input', () => {
        configureBusinessTimeZone('Asia/Colombo');

        expect(formatBusinessDateTime('2026-06-15T20:00:00.000Z')).toContain('Jun');
        expect(formatBusinessDateTime('not-a-date')).toBe('-');
        expect(formatBusinessDateTime(null, 'Not available')).toBe('Not available');
    });

    it('applies calendar-day changes to business-local input values', () => {
        const instant = new Date('2026-12-31T20:00:00.000Z');

        expect(businessDateTimeInputValue(instant, 1, 'Asia/Colombo')).toBe('2027-01-02T01:30');
    });
});


    it('serializes a business-local wall time with the configured timezone offset', () => {
        configureBusinessTimeZone('Asia/Colombo');

        expect(businessDateTimeToOffsetTimestamp('2026-09-07T09:00')).toBe('2026-09-07T09:00:00+05:30');
        expect(businessDateTimeToOffsetTimestamp('2026-09-07T09:00:15')).toBe('2026-09-07T09:00:15+05:30');
    });

    it('rejects nonexistent and ambiguous business-local times instead of guessing a DST offset', () => {
        expect(() => businessDateTimeToOffsetTimestamp('2026-03-08T02:30', 'America/New_York'))
            .toThrow('does not exist');
        expect(() => businessDateTimeToOffsetTimestamp('2026-11-01T01:30', 'America/New_York'))
            .toThrow('ambiguous');
    });

    it('rejects malformed civil date-time input', () => {
        expect(() => businessDateTimeToOffsetTimestamp('2026-02-30T09:00', 'Asia/Colombo'))
            .toThrow('Select a valid local date and time.');
    });
