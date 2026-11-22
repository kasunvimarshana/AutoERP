/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

export interface CurrencyLike {
    id: number | string;
    code?: string | null;
    name?: string | null;
    symbol?: string | null;
    is_active?: boolean;
    is_default?: boolean;
}

const FALLBACK_DEFAULT_CURRENCY_CODES = ['LKR', 'USD'] as const;

/**
 * Resolves the default currency from a collection of currencies.
 *
 * Precedence:
 * 1. Active currency explicitly marked `is_default === true`
 * 2. Active currency matching configured bootstrap defaults ('LKR', 'USD')
 * 3. First active currency in the collection
 * 4. First available currency in the collection (if no active currencies exist)
 */
export function findDefaultCurrency<T extends CurrencyLike>(
    currencies: T[] | null | undefined,
): T | null {
    if (!currencies || currencies.length === 0) {
        return null;
    }

    const activeCurrencies = currencies.filter((currency) => currency.is_active !== false);
    const candidatePool = activeCurrencies.length > 0 ? activeCurrencies : currencies;

    // 1. Explicitly marked default
    const explicitDefault = candidatePool.find((currency) => currency.is_default === true);
    if (explicitDefault) {
        return explicitDefault;
    }

    // 2. Known configured default codes
    for (const code of FALLBACK_DEFAULT_CURRENCY_CODES) {
        const matchingCode = candidatePool.find(
            (currency) => currency.code?.toUpperCase() === code,
        );
        if (matchingCode) {
            return matchingCode;
        }
    }

    // 3. Fallback to first candidate
    return candidatePool[0] ?? null;
}

export function resolveDefaultCurrencyId(
    currencies: CurrencyLike[] | null | undefined,
): string {
    const defaultCurrency = findDefaultCurrency(currencies);
    return defaultCurrency ? String(defaultCurrency.id) : '';
}
