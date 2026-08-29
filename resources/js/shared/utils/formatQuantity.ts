export function formatQuantity(
    value?: string | number | null,
    precision = 3,
    minimumPrecision = 0,
): string {
    const raw = String(value ?? '0').trim();
    const match = raw.match(/^(-)?(\d+)(?:\.(\d+))?$/);
    if (!match) return '0';

    const integer = (match[2].replace(/^0+(?=\d)/, '') || '0').replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    const safePrecision = Math.max(0, precision);
    const safeMinimumPrecision = Math.min(Math.max(0, minimumPrecision), safePrecision);
    const significantFraction = (match[3] ?? '').slice(0, safePrecision).replace(/0+$/, '');
    const fraction = significantFraction.padEnd(safeMinimumPrecision, '0');

    return `${match[1] ?? ''}${integer}${fraction ? `.${fraction}` : ''}`;
}
