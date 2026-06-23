export function parsePositiveInteger(value: string | number | null | undefined): number | null {
    if (value === null || value === undefined || value === '') return null;

    const numeric = typeof value === 'number' ? value : Number(value);
    return Number.isSafeInteger(numeric) && numeric > 0 ? numeric : null;
}

export function isValidPositiveInteger(value: string | number | null | undefined): boolean {
    return parsePositiveInteger(value) !== null;
}
