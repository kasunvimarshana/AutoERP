export function formatNumber(value: number, maximumFractionDigits = 2): string {
    return new Intl.NumberFormat('en', { maximumFractionDigits }).format(value);
}
