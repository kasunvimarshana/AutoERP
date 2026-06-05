export type ApiResponse<T> = {
    data: T;
    message?: string;
};

export type ApiCollectionResponse<T> = {
    data: T[];
    links?: {
        first?: string;
        last?: string;
        next?: string | null;
        prev?: string | null;
    };
    meta?: {
        current_page: number;
        from: number;
        last_page: number;
        per_page: number;
        to: number;
        total: number;
    };
};

export type ApiPreviewResponse<TInput, TCalculated> = {
    breakdown: Array<Record<string, unknown>>;
    calculated: TCalculated;
    errors: string[];
    input: TInput;
    warnings: string[];
};
