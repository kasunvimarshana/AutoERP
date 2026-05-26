type QueryValue = string | number | boolean | null | undefined;

export function toQuery(filters: Record<string, QueryValue>) {
    const query: Record<string, QueryValue> = {};

    for (const [key, value] of Object.entries(filters)) {
        if (value === undefined || value === null || value === '') {
            continue;
        }

        query[key] = value;
    }

    return query;
}
