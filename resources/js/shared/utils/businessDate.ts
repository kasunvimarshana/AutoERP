let configuredTimeZone: string | null = null;

interface DateParts {
    year: number;
    month: number;
    day: number;
    hour: number;
    minute: number;
}

export function configureBusinessTimeZone(timeZone?: string | null): void {
    configuredTimeZone = isValidTimeZone(timeZone) ? timeZone : null;
}

export function businessDateInputValue(date = new Date(), timeZone = configuredTimeZone): string {
    const parts = dateParts(date, timeZone);
    return `${parts.year}-${pad(parts.month)}-${pad(parts.day)}`;
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

function dateParts(date: Date, timeZone: string | null): DateParts {
    if (!timeZone) {
        return {
            year: date.getFullYear(),
            month: date.getMonth() + 1,
            day: date.getDate(),
            hour: date.getHours(),
            minute: date.getMinutes(),
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
    };
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
