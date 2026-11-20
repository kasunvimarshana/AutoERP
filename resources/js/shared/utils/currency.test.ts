import { describe, expect, it } from 'vitest';
import { findDefaultCurrency, resolveDefaultCurrencyId, type CurrencyLike } from './currency';

describe('currency utilities', () => {
    describe('findDefaultCurrency', () => {
        it('returns null for empty or undefined input', () => {
            expect(findDefaultCurrency(null)).toBeNull();
            expect(findDefaultCurrency(undefined)).toBeNull();
            expect(findDefaultCurrency([])).toBeNull();
        });

        it('prioritizes currency with explicit is_default: true', () => {
            const currencies: CurrencyLike[] = [
                { id: 1, code: 'LKR', name: 'Sri Lankan Rupee', is_active: true, is_default: false },
                { id: 2, code: 'EUR', name: 'Euro', is_active: true, is_default: true },
                { id: 3, code: 'USD', name: 'US Dollar', is_active: true, is_default: false },
            ];
            expect(findDefaultCurrency(currencies)?.id).toBe(2);
        });

        it('matches configured bootstrap codes like LKR when is_default is not set', () => {
            const currencies: CurrencyLike[] = [
                { id: 10, code: 'GBP', name: 'British Pound', is_active: true },
                { id: 11, code: 'LKR', name: 'Sri Lankan Rupee', is_active: true },
                { id: 12, code: 'USD', name: 'US Dollar', is_active: true },
            ];
            expect(findDefaultCurrency(currencies)?.id).toBe(11);
        });

        it('matches USD when LKR is not available', () => {
            const currencies: CurrencyLike[] = [
                { id: 20, code: 'GBP', name: 'British Pound', is_active: true },
                { id: 21, code: 'USD', name: 'US Dollar', is_active: true },
            ];
            expect(findDefaultCurrency(currencies)?.id).toBe(21);
        });

        it('falls back to the first active currency when no standard default matches', () => {
            const currencies: CurrencyLike[] = [
                { id: 30, code: 'AED', name: 'Emirati Dirham', is_active: true },
                { id: 31, code: 'JPY', name: 'Japanese Yen', is_active: true },
            ];
            expect(findDefaultCurrency(currencies)?.id).toBe(30);
        });

        it('ignores inactive currencies when active ones exist', () => {
            const currencies: CurrencyLike[] = [
                { id: 40, code: 'LKR', name: 'Old Rupee', is_active: false, is_default: true },
                { id: 41, code: 'USD', name: 'US Dollar', is_active: true, is_default: false },
            ];
            expect(findDefaultCurrency(currencies)?.id).toBe(41);
        });

        it('falls back to inactive candidate if all currencies are inactive', () => {
            const currencies: CurrencyLike[] = [
                { id: 50, code: 'OLD', name: 'Retired', is_active: false },
            ];
            expect(findDefaultCurrency(currencies)?.id).toBe(50);
        });
    });

    describe('resolveDefaultCurrencyId', () => {
        it('returns string ID or empty string', () => {
            expect(resolveDefaultCurrencyId([])).toBe('');
            expect(resolveDefaultCurrencyId([{ id: 42, code: 'USD', is_active: true }])).toBe('42');
        });
    });
});
