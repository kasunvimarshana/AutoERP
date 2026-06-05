export function formatMoney(value: number, currency = 'LKR'): string {
    return new Intl.NumberFormat('en', {
        currency,
        maximumFractionDigits: 2,
        minimumFractionDigits: 2,
        style: 'currency',
    }).format(value);
}
