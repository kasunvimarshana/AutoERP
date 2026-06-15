import { businessDateInputValue } from '@/shared/utils/businessDate';

export function todayDate(): string {
    return businessDateInputValue();
}

export function decimalOr(value: string | undefined, fallback = '0.000000'): string {
    return value && value.trim() !== '' ? value : fallback;
}
