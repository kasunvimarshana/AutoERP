import axios from 'axios';
import type { ApiErrorPayload } from '@/shared/types/api';

export class ApiError extends Error {
    constructor(
        message: string,
        public readonly status: number | null,
        public readonly code: string | null = null,
        public readonly type: string | null = null,
        public readonly fields: Record<string, string[]> = {},
    ) {
        super(message);
        this.name = 'ApiError';
    }
}

export function toApiError(error: unknown): ApiError {
    if (error instanceof ApiError) {
        return error;
    }
    if (axios.isCancel(error)) {
        return new ApiError('Request cancelled.', null, 'REQUEST_CANCELLED', 'cancelled');
    }
    if (!axios.isAxiosError<ApiErrorPayload>(error)) {
        return new ApiError(error instanceof Error ? error.message : 'Unexpected error.', null);
    }

    const payload = error.response?.data;
    const status = error.response?.status ?? null;
    const fields = payload?.errors ?? payload?.error?.details?.fields ?? {};
    const fallback = status === 401
        ? 'Your session is not authorized.'
        : status === 403
            ? 'You do not have permission to perform this action.'
            : status === 500
                ? 'The server reported an unexpected error.'
                : 'The request could not be completed.';

    return new ApiError(
        payload?.error?.message ?? payload?.message ?? fallback,
        status,
        payload?.error?.code ?? null,
        payload?.error?.type ?? null,
        fields,
    );
}

export function fieldError(error: ApiError | null, field: string): string | undefined {
    return error?.fields[field]?.[0];
}
