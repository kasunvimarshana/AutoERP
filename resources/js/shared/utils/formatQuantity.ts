export function formatQuantity(value?: string | number | null, precision = 3): string {
    const quantity = Number(value ?? 0);
    return new Intl.NumberFormat(undefined, {
        maximumFractionDigits: precision,
    }).format(Number.isFinite(quantity) ? quantity : 0);
}
