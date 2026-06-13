export function todayDate(): string {
    return new Date().toISOString().slice(0, 10);
}

export function decimalOr(value: string | undefined, fallback = '0.000000'): string {
    return value && value.trim() !== '' ? value : fallback;
}
