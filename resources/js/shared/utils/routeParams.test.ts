import { describe, expect, it } from 'vitest';
import { isValidPositiveInteger, parsePositiveInteger } from './routeParams';

describe('route parameter parsing', () => {
    it.each([
        ['1', 1],
        [42, 42],
        ['9007199254740991', Number.MAX_SAFE_INTEGER],
    ])('parses valid positive integer %s', (value, expected) => {
        expect(parsePositiveInteger(value)).toBe(expected);
        expect(isValidPositiveInteger(value)).toBe(true);
    });

    it.each([null, undefined, '', '0', 0, '-1', -1, '1.5', 1.5, 'abc', Number.MAX_SAFE_INTEGER + 1])(
        'rejects invalid identifier %s',
        (value) => {
            expect(parsePositiveInteger(value)).toBeNull();
            expect(isValidPositiveInteger(value)).toBe(false);
        },
    );
});
