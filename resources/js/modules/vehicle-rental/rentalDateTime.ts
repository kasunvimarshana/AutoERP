const LOCAL_DATE_TIME_PATTERN = /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(?::\d{2})?$/;
const EXPLICIT_TIMEZONE_PATTERN = /(?:Z|[+-]\d{2}:\d{2})$/;

export function localDateTimeToOffsetIso(value: string): string {
    const input = value.trim();
    if (EXPLICIT_TIMEZONE_PATTERN.test(input)) {
        const explicitDate = new Date(input);
        if (Number.isNaN(explicitDate.getTime())) {
            throw new Error('A valid date and time is required.');
        }

        return input;
    }
    if (!LOCAL_DATE_TIME_PATTERN.test(input)) {
        throw new Error('A valid local date and time is required.');
    }

    const date = new Date(input);
    if (Number.isNaN(date.getTime())) {
        throw new Error('A valid local date and time is required.');
    }

    const normalized = input.length === 16 ? `${input}:00` : input;
    const offsetMinutes = -date.getTimezoneOffset();
    const sign = offsetMinutes >= 0 ? '+' : '-';
    const absoluteOffset = Math.abs(offsetMinutes);
    const hours = String(Math.floor(absoluteOffset / 60)).padStart(2, '0');
    const minutes = String(absoluteOffset % 60).padStart(2, '0');

    return `${normalized}${sign}${hours}:${minutes}`;
}

export function nullableLocalDateTimeToOffsetIso(value?: string | null): string | null {
    const input = value?.trim() ?? '';
    return input === '' ? null : localDateTimeToOffsetIso(input);
}

export function utcDateTimeToLocalInput(value?: string | null): string {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value.slice(0, 16);
    const local = new Date(date.getTime() - date.getTimezoneOffset() * 60_000);
    return local.toISOString().slice(0, 16);
}

export function agreementDateBoundary(value: string | null | undefined, boundary: 'start' | 'end'): string {
    if (!value) return '';
    return `${value.slice(0, 10)}T${boundary === 'start' ? '00:00' : '23:59'}`;
}

export function latestLocalDateTime(...values: Array<string | null | undefined>): string {
    return values.filter((value): value is string => Boolean(value)).sort().at(-1) ?? '';
}

export function earliestLocalDateTime(...values: Array<string | null | undefined>): string {
    return values.filter((value): value is string => Boolean(value)).sort().at(0) ?? '';
}

export function clampLocalDateTime(value: string, minimum: string, maximum: string): string {
    if (!value) return minimum;
    if (minimum && value < minimum) return minimum;
    if (maximum && value > maximum) return maximum;
    return value;
}

export function formatLocalDateTime(value?: string | null): string {
    if (!value) return 'Open-ended';
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? value : date.toLocaleString();
}
