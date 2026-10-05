let configuredTimeZone: string | null = null;

interface DateParts {
    year: number;
    month: number;
    day: number;
    hour: number;
    minute: number;
}

interface DateTimeParts extends DateParts {
    second: number;
}

const LOCAL_DATE_PATTERN = /^(\d{4})-(\d{2})-(\d{2})$/;
const LOCAL_DATE_TIME_PATTERN = /^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})(?::(\d{2}))?$/;
const MINUTES_PER_HOUR = 60;
const HOURS_PER_DAY = 24;
const MILLISECONDS_PER_MINUTE = 60_000;
const MILLISECONDS_PER_DAY = HOURS_PER_DAY * MINUTES_PER_HOUR * MILLISECONDS_PER_MINUTE;
const MAX_TIME_ZONE_RESOLUTION_PASSES = 4;
const TIME_ZONE_TRANSITION_PROBE_DAYS = 1;

export function configureBusinessTimeZone(timeZone?: string | null): void {
    configuredTimeZone = isValidTimeZone(timeZone) ? timeZone : null;
}

export function businessTimeZoneLabel(timeZone = configuredTimeZone): string {
    return timeZone ?? Intl.DateTimeFormat().resolvedOptions().timeZone;
}

export function businessDateInputValue(date = new Date(), timeZone = configuredTimeZone): string {
    const parts = dateParts(date, timeZone);
    return `${parts.year}-${pad(parts.month)}-${pad(parts.day)}`;
}

export function formatBusinessDate(
    value: string | null | undefined,
    fallback = '-',
): string {
    if (value === null || value === undefined || value === '') return fallback;
    const match = LOCAL_DATE_PATTERN.exec(value);
    if (!match) return fallback;

    const year = Number(match[1]);
    const month = Number(match[2]);
    const day = Number(match[3]);
    const date = new Date(Date.UTC(year, month - 1, day));

    if (date.getUTCFullYear() !== year || date.getUTCMonth() + 1 !== month || date.getUTCDate() !== day) {
        return fallback;
    }

    return new Intl.DateTimeFormat(undefined, {
        timeZone: 'UTC',
        dateStyle: 'medium',
    }).format(date);
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
): string {
    const parts = dateParts(date, timeZone);
    const adjusted = new Date(Date.UTC(
        parts.year,
        parts.month - 1,
        parts.day + calendarDays,
        parts.hour,
        parts.minute,
    ));

    return [
        adjusted.getUTCFullYear(),
        pad(adjusted.getUTCMonth() + 1),
        pad(adjusted.getUTCDate()),
    ].join('-') + `T${pad(adjusted.getUTCHours())}:${pad(adjusted.getUTCMinutes())}`;
}

export function businessDateTimeInputValueWithSeconds(
    date = new Date(),
    timeZone = configuredTimeZone,
): string {
    const parts = dateTimeParts(date, timeZone);
    return localTimestamp(parts);
}

export function businessTimestampWithOffset(value: string, timeZone = configuredTimeZone): string {
    const parts = parseLocalDateTime(value);
    if (!parts) throw new Error('Select a valid business date and time.');

    if (!timeZone) {
        const localDate = new Date(
            parts.year,
            parts.month - 1,
            parts.day,
            parts.hour,
            parts.minute,
            parts.second,
        );
        if (!sameLocalParts(localDate, parts)) throw new Error('Select a valid business date and time.');

        const browserTimeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        if (isValidTimeZone(browserTimeZone)) {
            const desiredUtc = utcTimestamp(parts);
            if (matchingTimeZoneInstants(parts, browserTimeZone, desiredUtc, localDate).length !== 1) {
                throw new Error('Select a valid business date and time.');
            }
        }

        return `${localTimestamp(parts)}${offsetText(-localDate.getTimezoneOffset())}`;
    }

    const desiredUtc = utcTimestamp(parts);
    let candidate = desiredUtc;

    for (let pass = 0; pass < MAX_TIME_ZONE_RESOLUTION_PASSES; pass += 1) {
        const offset = timeZoneOffsetMinutes(new Date(candidate), timeZone);
        const next = desiredUtc - offset * MILLISECONDS_PER_MINUTE;
        if (next === candidate) break;
        candidate = next;
    }

    const instant = new Date(candidate);
    if (!sameDateTimeParts(dateTimeParts(instant, timeZone), parts)) {
        throw new Error('Select a valid business date and time.');
    }
    if (matchingTimeZoneInstants(parts, timeZone, desiredUtc, instant).length !== 1) {
        throw new Error('Select a valid business date and time.');
    }

    return `${localTimestamp(parts)}${offsetText(timeZoneOffsetMinutes(instant, timeZone))}`;
}

function parseLocalDateTime(value: string): DateTimeParts | null {
    const match = LOCAL_DATE_TIME_PATTERN.exec(value);
    if (!match) return null;

    const parts: DateTimeParts = {
        year: Number(match[1]),
        month: Number(match[2]),
        day: Number(match[3]),
        hour: Number(match[4]),
        minute: Number(match[5]),
        second: Number(match[6] ?? 0),
    };

    const validation = new Date(Date.UTC(
        parts.year,
        parts.month - 1,
        parts.day,
        parts.hour,
        parts.minute,
        parts.second,
    ));

    return validation.getUTCFullYear() === parts.year
        && validation.getUTCMonth() + 1 === parts.month
        && validation.getUTCDate() === parts.day
        && validation.getUTCHours() === parts.hour
        && validation.getUTCMinutes() === parts.minute
        && validation.getUTCSeconds() === parts.second
        ? parts
        : null;
}

function dateParts(date: Date, timeZone: string | null): DateParts {
    const parts = dateTimeParts(date, timeZone);
    return {
        year: parts.year,
        month: parts.month,
        day: parts.day,
        hour: parts.hour,
        minute: parts.minute,
    };
}

function dateTimeParts(date: Date, timeZone: string | null): DateTimeParts {
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

function timeZoneOffsetMinutes(date: Date, timeZone: string): number {
    const parts = dateTimeParts(date, timeZone);
    const representedAsUtc = Date.UTC(
        parts.year,
        parts.month - 1,
        parts.day,
        parts.hour,
        parts.minute,
        parts.second,
    );
    return Math.round((representedAsUtc - date.getTime()) / MILLISECONDS_PER_MINUTE);
}

function matchingTimeZoneInstants(
    parts: DateTimeParts,
    timeZone: string,
    desiredUtc: number,
    resolvedInstant: Date,
): Date[] {
    const probeDistance = TIME_ZONE_TRANSITION_PROBE_DAYS * MILLISECONDS_PER_DAY;
    const offsets = new Set<number>([
        timeZoneOffsetMinutes(resolvedInstant, timeZone),
        timeZoneOffsetMinutes(new Date(resolvedInstant.getTime() - probeDistance), timeZone),
        timeZoneOffsetMinutes(new Date(resolvedInstant.getTime() + probeDistance), timeZone),
    ]);

    const matches = new Map<number, Date>();
    for (const offset of offsets) {
        const instant = new Date(desiredUtc - offset * MILLISECONDS_PER_MINUTE);
        if (sameDateTimeParts(dateTimeParts(instant, timeZone), parts)) {
            matches.set(instant.getTime(), instant);
        }
    }

    return [...matches.values()];
}

function utcTimestamp(parts: DateTimeParts): number {
    return Date.UTC(
        parts.year,
        parts.month - 1,
        parts.day,
        parts.hour,
        parts.minute,
        parts.second,
    );
}

function sameLocalParts(date: Date, parts: DateTimeParts): boolean {
    return date.getFullYear() === parts.year
        && date.getMonth() + 1 === parts.month
        && date.getDate() === parts.day
        && date.getHours() === parts.hour
        && date.getMinutes() === parts.minute
        && date.getSeconds() === parts.second;
}

function sameDateTimeParts(left: DateTimeParts, right: DateTimeParts): boolean {
    return left.year === right.year
        && left.month === right.month
        && left.day === right.day
        && left.hour === right.hour
        && left.minute === right.minute
        && left.second === right.second;
}

function localTimestamp(parts: DateTimeParts): string {
    return `${parts.year}-${pad(parts.month)}-${pad(parts.day)}T${pad(parts.hour)}:${pad(parts.minute)}:${pad(parts.second)}`;
}

function offsetText(offsetMinutes: number): string {
    const sign = offsetMinutes < 0 ? '-' : '+';
    const absolute = Math.abs(offsetMinutes);
    return `${sign}${pad(Math.floor(absolute / MINUTES_PER_HOUR))}:${pad(absolute % MINUTES_PER_HOUR)}`;
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
