import { describe, expect, it } from 'vitest';
import { serializeQueryParams } from './queryParams';

describe('serializeQueryParams', () => {
    it('serializes report filters as nested query keys', () => {
        const query = serializeQueryParams({
            page: 1,
            filters: { warehouse_id: 7, warehouse_location_id: undefined },
        });

        const params = new URLSearchParams(query);
        expect(params.get('page')).toBe('1');
        expect(params.get('filters[warehouse_id]')).toBe('7');
        expect(params.has('filters[warehouse_location_id]')).toBe(false);
        expect(params.has('filters')).toBe(false);
    });

    it('preserves scalar and repeated query values', () => {
        const params = new URLSearchParams(serializeQueryParams({ search: 'brake pad', active: true, tags: ['a', 'b'] }));

        expect(params.get('search')).toBe('brake pad');
        expect(params.get('active')).toBe('1');
        expect(params.getAll('tags')).toEqual(['a', 'b']);
    });
});
