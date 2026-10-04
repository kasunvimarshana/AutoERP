import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import { configureBusinessTimeZone } from '@/shared/utils/businessDate';
import {
    formatOperationalDateTime,
    localTimestampValue,
    operationalTimeZone,
    timestampWithOffset,
} from './vehicleUse';

beforeEach(() => configureBusinessTimeZone('Asia/Colombo'));
afterEach(() => configureBusinessTimeZone(null));

describe('Vehicle Rental business time helpers', () => {
    it('uses the configured workspace timezone for labels and payload timestamps', () => {
        expect(operationalTimeZone()).toBe('Asia/Colombo');
        expect(timestampWithOffset('2026-09-07T09:00')).toBe('2026-09-07T09:00:00+05:30');
    });

    it('round-trips persisted instants through business-local editor values', () => {
        const original = '2026-09-07T03:30:15+00:00';
        const local = localTimestampValue(original);

        expect(local).toBe('2026-09-07T09:00:15');
        expect(new Date(timestampWithOffset(local)).getTime()).toBe(new Date(original).getTime());
    });

    it('formats equivalent instants consistently in business time', () => {
        expect(formatOperationalDateTime('2026-09-07T03:30:00+00:00'))
            .toBe(formatOperationalDateTime('2026-09-07T09:00:00+05:30'));
        expect(formatOperationalDateTime(null)).toBe('Not recorded');
    });
});
