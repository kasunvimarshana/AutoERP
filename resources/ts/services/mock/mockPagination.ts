import type { ApiCollectionResponse } from '../api/apiResponse';

export function mockPagination<T>(data: T[], page = 1, perPage = 25): Pick<ApiCollectionResponse<T>, 'links' | 'meta'> {
    return {
        links: {
            first: '#',
            last: '#',
            next: null,
            prev: null,
        },
        meta: {
            current_page: page,
            from: data.length ? 1 : 0,
            last_page: 1,
            per_page: perPage,
            to: data.length,
            total: data.length,
        },
    };
}
