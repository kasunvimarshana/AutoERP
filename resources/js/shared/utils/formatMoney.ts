export function formatMoney(value?: string | number | null, currency = 'LKR'): string {
    const amount = Number(value ?? 0);
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency,
        maximumFractionDigits: 2,
    }).format(Number.isFinite(amount) ? amount : 0);
}
