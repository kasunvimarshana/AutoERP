export function slugify(value: string) {
    return value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .replace(/-{2,}/g, '-');
}

export function formatDate(value: string | null | undefined) {
    if (!value) {
        return '-';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

export function parsePositiveInteger(value: string | null, fallback: number) {
    if (!value) {
        return fallback;
    }

    const parsed = Number(value);
    return Number.isInteger(parsed) && parsed > 0 ? parsed : fallback;
}

export function parseBooleanSearchParam(value: string | null): boolean | undefined {
    if (value === '1' || value === 'true') {
        return true;
    }

    if (value === '0' || value === 'false') {
        return false;
    }

    return undefined;
}
