type QueryParam = string | number | boolean | null | undefined;
type QueryParams = Record<string, QueryParam | QueryParam[] | Record<string, QueryParam>>;

export function serializeQueryParams(params?: QueryParams): string {
    const searchParams = new URLSearchParams();

    for (const [key, rawValue] of Object.entries(params ?? {})) {
        if (rawValue !== null && typeof rawValue === 'object' && !Array.isArray(rawValue)) {
            for (const [nestedKey, value] of Object.entries(rawValue)) {
                if (value === null || value === undefined) continue;
                searchParams.append(`${key}[${nestedKey}]`, typeof value === 'boolean' ? (value ? '1' : '0') : String(value));
            }
            continue;
        }

        const values = Array.isArray(rawValue) ? rawValue : [rawValue];

        for (const value of values) {
            if (value === null || value === undefined) continue;
            searchParams.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : String(value));
        }
    }

    return searchParams.toString();
}
