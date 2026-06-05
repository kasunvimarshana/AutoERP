export type ApiErrorDetails = Record<string, unknown>;

export class ApiError extends Error {
    constructor(
        message: string,
        public readonly status = 500,
        public readonly errors: Record<string, string[]> = {},
        public readonly code = 'API_REQUEST_FAILED',
        public readonly type = 'http',
        public readonly details: ApiErrorDetails = {},
    ) {
        super(message);
    }
}
