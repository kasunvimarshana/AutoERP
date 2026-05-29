export type ApiListResponse<T> = {
    data: T[];
    links?: Record<string, string | null>;
    meta?: Record<string, unknown>;
};

export type ApiResourceResponse<T> = {
    data: T;
};

export type ApiValidationErrors = Record<string, string[]>;
