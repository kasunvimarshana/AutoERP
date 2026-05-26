export type ApiValidationErrors = Record<string, string[]>;

export type ApiResourceEnvelope<T> = {
    data: T;
};

export type ApiPaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type ApiPaginationMeta = {
    current_page: number;
    from: number | null;
    last_page: number;
    path: string;
    per_page: number;
    to: number | null;
    total: number;
    links?: ApiPaginationLink[];
};

export type ApiPaginatedEnvelope<T> = {
    data: T[];
    links?: {
        first?: string | null;
        last?: string | null;
        prev?: string | null;
        next?: string | null;
    };
    meta?: ApiPaginationMeta;
};

export type PaginatedResult<T> = {
    items: T[];
    meta: ApiPaginationMeta | null;
    links: ApiPaginatedEnvelope<T>['links'] | null;
};
