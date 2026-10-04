import { afterEach, describe, expect, it } from 'vitest';
import {
    businessDateInputValue,
    businessDateTimeInputValue,
    businessTimeZoneLabel,
    businessTimestampWithOffset,
    configureBusinessTimeZone,
    formatBusinessDateTime,
} from './businessDate';

afterEach(() => configureBusinessTimeZone(null));

describe('business date utilities', () => {
    it('uses the configured business timezone instead of UTC', () => {
        configureBusinessTimeZone('Asia/Colombo');
        const instant = new Date('2026-06-15T20:00:00.000Z');

        expect(businessDateInputValue(instant)).toBe('2026-06-16');
        expect(businessDateTimeInputValue(instant)).toBe('2026-06-16T01:30');
        expect(businessTimeZoneLabel()).toBe('Asia/Colombo');
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

    it('converts a business-local timestamp using the configured timezone offset', () => {
        configureBusinessTimeZone('Asia/Colombo');

        expect(businessTimestampWithOffset('2026-09-07T09:00')).toBe('2026-09-07T09:00:00+05:30');
        expect(new Date(businessTimestampWithOffset('2026-09-07T09:00')).toISOString()).toBe('2026-09-07T03:30:00.000Z');
    });

    it('uses the effective daylight-saving offset for configured zones', () => {
        expect(businessTimestampWithOffset('2026-01-15T09:00', 'America/New_York')).toBe('2026-01-15T09:00:00-05:00');
        expect(businessTimestampWithOffset('2026-07-15T09:00', 'America/New_York')).toBe('2026-07-15T09:00:00-04:00');
    });

    it('rejects nonexistent local times during a daylight-saving transition', () => {
        expect(() => businessTimestampWithOffset('2026-03-08T02:30', 'America/New_York')).toThrow('Select a valid business date and time.');
    });

    it('round trips configured-zone timestamps back to business-local inputs', () => {
        configureBusinessTimeZone('Asia/Colombo');
        const timestamp = businessTimestampWithOffset('2026-09-07T09:00:15');

        expect(businessDateTimeInputValue(new Date(timestamp))).toBe('2026-09-07T09:00');
    });
});
