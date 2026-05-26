import { z } from 'zod';

export function emptyToUndefined(value: unknown) {
    if (value === '' || value === null || value === undefined) {
        return undefined;
    }

    if (typeof value === 'string') {
        const trimmed = value.trim();
        return trimmed === '' ? undefined : trimmed;
    }

    return value;
}

export function optionalInteger(message = 'Enter a valid value.') {
    return z.preprocess((value) => {
        const normalized = emptyToUndefined(value);
        if (normalized === undefined) {
            return undefined;
        }

        return Number(normalized);
    }, z.number().int(message).positive(message).optional());
}

export function requiredInteger(message: string) {
    return z.preprocess((value) => {
        const normalized = emptyToUndefined(value);
        if (normalized === undefined) {
            return undefined;
        }

        return Number(normalized);
    }, z.number().int(message).positive(message));
}

export function optionalDecimal(message = 'Enter a valid number.') {
    return z.preprocess((value) => {
        const normalized = emptyToUndefined(value);
        if (normalized === undefined) {
            return undefined;
        }

        return Number(normalized);
    }, z.number(message).finite(message).optional());
}

export function optionalTrimmedString(maxLength: number, message: string) {
    return z.preprocess(emptyToUndefined, z.string().max(maxLength, message).optional());
}

export function requiredBoolean(message: string) {
    return z.preprocess((value) => {
        if (typeof value === 'boolean') {
            return value;
        }

        if (value === 'true' || value === '1' || value === 1) {
            return true;
        }

        if (value === 'false' || value === '0' || value === 0) {
            return false;
        }

        return value;
    }, z.boolean({ message }));
}
