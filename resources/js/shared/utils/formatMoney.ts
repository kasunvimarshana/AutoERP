export function formatMoney(value?: string | number | null, currency = 'LKR'): string {
    const decimal = normalizeDecimal(value, 2, 6);
    const symbol = currencySymbol(currency);
    return `${symbol}${symbol.length > 1 ? ' ' : ''}${decimal.sign}${groupInteger(decimal.integer)}.${decimal.fraction}`;
}

function normalizeDecimal(value: string | number | null | undefined, minimumFractionDigits: number, maximumFractionDigits: number) {
    const raw = String(value ?? '0').trim();
    const match = raw.match(/^(-)?(\d+)(?:\.(\d+))?$/);
    if (!match) return { sign: '', integer: '0', fraction: '00' };

    const fraction = (match[3] ?? '').slice(0, maximumFractionDigits).padEnd(minimumFractionDigits, '0');
    const trimmed = fraction.length > minimumFractionDigits
        ? fraction.replace(/0+$/, '').padEnd(minimumFractionDigits, '0')
        : fraction;

    return {
        sign: match[1] ? '-' : '',
        integer: match[2].replace(/^0+(?=\d)/, '') || '0',
        fraction: trimmed,
    };
}

function groupInteger(value: string): string {
    return value.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function currencySymbol(currency: string): string {
    try {
        const parts = new Intl.NumberFormat(undefined, { style: 'currency', currency }).formatToParts(0);
        return parts.find((part) => part.type === 'currency')?.value ?? `${currency} `;
    } catch {
        return `${currency} `;
    }
}
