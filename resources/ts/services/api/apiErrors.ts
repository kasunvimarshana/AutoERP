export class ApiError extends Error {
    constructor(
        message: string,
        public readonly status = 500,
        public readonly errors: Record<string, string[]> = {},
    ) {
        super(message);
    }
}
