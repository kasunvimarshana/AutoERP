const SCALE = 6;
const FACTOR = 10n ** BigInt(SCALE);
const decimalPattern = /^-?\d+(\.\d+)?$/;

function scaled(value: string | number | null | undefined): bigint {
    const input = String(value ?? '0').trim();
    if (!decimalPattern.test(input)) return 0n;

    const negative = input.startsWith('-');
    const [whole, fraction = ''] = (negative ? input.slice(1) : input).split('.');
    const amount = (BigInt(whole) * FACTOR) + BigInt(fraction.padEnd(SCALE, '0').slice(0, SCALE));
    return negative ? -amount : amount;
}

function decimal(value: bigint): string {
    const negative = value < 0n;
    const amount = negative ? -value : value;
    const whole = amount / FACTOR;
    const fraction = String(amount % FACTOR).padStart(SCALE, '0');
    return `${negative ? '-' : ''}${whole}.${fraction}`;
}

export function addDecimal(left: string, right: string): string {
    return decimal(scaled(left) + scaled(right));
}

export function subtractDecimal(left: string, right: string): string {
    return decimal(scaled(left) - scaled(right));
}

export function multiplyDecimal(left: string, right: string): string {
    return decimal((scaled(left) * scaled(right)) / FACTOR);
}

export function percentageOfDecimal(value: string, rate: string): string {
    return decimal((scaled(value) * scaled(rate)) / (FACTOR * 100n));
}

export function sumDecimals(values: string[]): string {
    return decimal(values.reduce((total, value) => total + scaled(value), 0n));
}

export function nonNegativeDecimal(value: string): string {
    return scaled(value) < 0n ? '0.000000' : decimal(scaled(value));
}

export function isDecimalString(value: string): boolean {
    const input = value.trim();
    return input === '' || decimalPattern.test(input);
}

export function normalizeDecimalInput(value: string): string {
    return value.replace(/[^\d.-]/g, '');
}

export function compareDecimalStrings(left: string, right: string): -1 | 0 | 1 {
    const difference = scaled(left) - scaled(right);
    if (difference < 0n) return -1;
    if (difference > 0n) return 1;
    return 0;
}

export function isPositiveDecimal(value: string): boolean {
    return isDecimalString(value) && value.trim() !== '' && compareDecimalStrings(value, '0') > 0;
}

export function isNonNegativeDecimal(value: string): boolean {
    return isDecimalString(value) && (value.trim() === '' || compareDecimalStrings(value, '0') >= 0);
}
