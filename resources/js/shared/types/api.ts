import type { PaginationLinks, PaginationMeta } from './pagination';

export interface ApiResource<T> {
    data: T;
}

export interface ApiCollection<T> {
    data: T[];
    meta?: PaginationMeta;
    links?: PaginationLinks;
}

export interface ApiErrorPayload {
    message?: string;
    errors?: Record<string, string[]>;
    error?: {
        code?: string;
        type?: string;
        message?: string;
        details?: {
            fields?: Record<string, string[]>;
            [key: string]: unknown;
        };
    };
}

export interface ListParams {
    page?: number;
    per_page?: number;
    search?: string;
    [key: string]: string | number | boolean | null | undefined;
}
