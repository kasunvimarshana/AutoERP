import { afterEach, describe, expect, it } from 'vitest';
import { configureBusinessTimeZone } from '@/shared/utils/businessDate';
import { localTimestampValue, operationalTimeZoneLabel, timestampWithOffset } from './vehicleUse';

afterEach(() => configureBusinessTimeZone(null));

describe('Vehicle Rental business-time helpers', () => {
    it('uses the configured business timezone for labels and outbound timestamps', () => {
        configureBusinessTimeZone('Asia/Colombo');

        expect(operationalTimeZoneLabel()).toBe('Asia/Colombo');
        expect(timestampWithOffset('2026-09-07T09:00')).toBe('2026-09-07T09:00:00+05:30');
    });

    it('converts stored instants back to the configured business-local value with seconds', () => {
        configureBusinessTimeZone('Asia/Colombo');

        expect(localTimestampValue('2026-09-07T03:30:15Z')).toBe('2026-09-07T09:00:15');
    });

    it('refuses ambiguous configured business times instead of choosing one DST occurrence', () => {
        configureBusinessTimeZone('America/New_York');

        expect(() => timestampWithOffset('2026-11-01T01:30')).toThrow('Select a valid business date and time.');
    });
});
