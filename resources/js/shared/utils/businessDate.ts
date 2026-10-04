let configuredTimeZone: string | null = null;

interface DateParts {
    year: number;
    month: number;
    day: number;
    hour: number;
    minute: number;
    second: number;
}

interface LocalDateTimeParts extends DateParts {}

const MILLISECONDS_PER_MINUTE = 60_000;
const MILLISECONDS_PER_DAY = 86_400_000;
const OFFSET_SAMPLE_DAY_SHIFTS = [-2, -1, 0, 1, 2] as const;
const LOCAL_DATE_TIME_PATTERN = /^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})(?::(\d{2}))?$/;

export function configureBusinessTimeZone(timeZone?: string | null): void {
    configuredTimeZone = isValidTimeZone(timeZone) ? timeZone : null;
}

export function businessTimeZone(timeZone = configuredTimeZone): string {
    if (timeZone !== null) {
        if (!isValidTimeZone(timeZone)) throw new Error('Select a valid business time zone.');
        return timeZone;
    }

    return Intl.DateTimeFormat().resolvedOptions().timeZone;
}

export function businessDateInputValue(date = new Date(), timeZone = configuredTimeZone): string {
    const parts = dateParts(date, timeZone);
    return `${parts.year}-${pad(parts.month)}-${pad(parts.day)}`;
}

export function formatBusinessDateTime(
    value: string | Date | null | undefined,
    fallback = '-',
    timeZone = configuredTimeZone,
): string {
    if (value === null || value === undefined || value === '') return fallback;
    const date = value instanceof Date ? value : new Date(value);
    if (Number.isNaN(date.getTime())) return fallback;

    return new Intl.DateTimeFormat(undefined, {
        timeZone: timeZone ?? undefined,
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(date);
}

export function businessDateTimeInputValue(
    date = new Date(),
    calendarDays = 0,
    timeZone = configuredTimeZone,
    includeSeconds = false,
): string {
    const parts = dateParts(date, timeZone);
    const adjusted = new Date(Date.UTC(
        parts.year,
        parts.month - 1,
        parts.day + calendarDays,
        parts.hour,
        parts.minute,
        parts.second,
    ));

    const datePart = [
        adjusted.getUTCFullYear(),
        pad(adjusted.getUTCMonth() + 1),
        pad(adjusted.getUTCDate()),
    ].join('-');
    const timePart = `${pad(adjusted.getUTCHours())}:${pad(adjusted.getUTCMinutes())}`;

    return `${datePart}T${timePart}${includeSeconds ? `:${pad(adjusted.getUTCSeconds())}` : ''}`;
}

export function businessDateTimeToOffsetTimestamp(
    value: string,
    timeZone = configuredTimeZone,
): string {
    const parts = parseLocalDateTime(value);
    const zone = businessTimeZone(timeZone);
    const localEpoch = Date.UTC(parts.year, parts.month - 1, parts.day, parts.hour, parts.minute, parts.second);
    const offsets = new Set<number>();

    for (const dayShift of OFFSET_SAMPLE_DAY_SHIFTS) {
        offsets.add(timeZoneOffsetMinutes(new Date(localEpoch + dayShift * MILLISECONDS_PER_DAY), zone));
    }

    const candidates = [...offsets]
        .map((offset) => ({ offset, instant: localEpoch - offset * MILLISECONDS_PER_MINUTE }))
        .filter(({ instant }) => sameDateParts(dateParts(new Date(instant), zone), parts));

    if (candidates.length === 0) {
        throw new Error(`Selected local date and time does not exist in ${zone}.`);
    }
    if (candidates.length > 1) {
        throw new Error(`Selected local date and time is ambiguous in ${zone}. Choose a different time.`);
    }

    const [{ offset }] = candidates;
    const normalized = `${parts.year}-${pad(parts.month)}-${pad(parts.day)}T${pad(parts.hour)}:${pad(parts.minute)}:${pad(parts.second)}`;

    return `${normalized}${formatOffset(offset)}`;
}

function dateParts(date: Date, timeZone: string | null): DateParts {
    if (!timeZone) {
        return {
            year: date.getFullYear(),
            month: date.getMonth() + 1,
            day: date.getDate(),
            hour: date.getHours(),
            minute: date.getMinutes(),
            second: date.getSeconds(),
        };
    }

    const values = Object.fromEntries(
        new Intl.DateTimeFormat('en-US', {
            timeZone,
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hourCycle: 'h23',
        })
            .formatToParts(date)
            .filter((part) => part.type !== 'literal')
            .map((part) => [part.type, Number(part.value)]),
    );

    return {
        year: values.year,
        month: values.month,
        day: values.day,
        hour: values.hour,
        minute: values.minute,
        second: values.second,
    };
}

function parseLocalDateTime(value: string): LocalDateTimeParts {
    const match = LOCAL_DATE_TIME_PATTERN.exec(value);
    if (!match) throw new Error('Select a valid local date and time.');

    const parts: LocalDateTimeParts = {
        year: Number(match[1]),
        month: Number(match[2]),
        day: Number(match[3]),
        hour: Number(match[4]),
        minute: Number(match[5]),
        second: Number(match[6] ?? '0'),
    };
    const roundTrip = new Date(Date.UTC(parts.year, parts.month - 1, parts.day, parts.hour, parts.minute, parts.second));

    if (
        roundTrip.getUTCFullYear() !== parts.year
        || roundTrip.getUTCMonth() + 1 !== parts.month
        || roundTrip.getUTCDate() !== parts.day
        || roundTrip.getUTCHours() !== parts.hour
        || roundTrip.getUTCMinutes() !== parts.minute
        || roundTrip.getUTCSeconds() !== parts.second
    ) {
        throw new Error('Select a valid local date and time.');
    }

    return parts;
}

function timeZoneOffsetMinutes(date: Date, timeZone: string): number {
    const parts = dateParts(date, timeZone);
    const represented = Date.UTC(parts.year, parts.month - 1, parts.day, parts.hour, parts.minute, parts.second);
    return Math.round((represented - date.getTime()) / MILLISECONDS_PER_MINUTE);
}

function sameDateParts(left: DateParts, right: DateParts): boolean {
    return left.year === right.year
        && left.month === right.month
        && left.day === right.day
        && left.hour === right.hour
        && left.minute === right.minute
        && left.second === right.second;
}

function formatOffset(offsetMinutes: number): string {
    const sign = offsetMinutes < 0 ? '-' : '+';
    const absolute = Math.abs(offsetMinutes);
    const hours = Math.floor(absolute / 60);
    const minutes = absolute % 60;
    return `${sign}${pad(hours)}:${pad(minutes)}`;
}

function isValidTimeZone(timeZone?: string | null): timeZone is string {
    if (!timeZone) return false;

    try {
        new Intl.DateTimeFormat('en-US', { timeZone }).format();
        return true;
    } catch {
        return false;
    }
}

function pad(value: number): string {
    return String(value).padStart(2, '0');
}
