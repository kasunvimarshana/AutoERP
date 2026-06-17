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
    const fallback = status === 422
        ? 'Please correct the highlighted fields and try again.'
        : status === 401
        ? 'Your session is not authorized.'
        : status === 403
            ? 'You do not have permission to perform this action.'
            : status !== null && status >= 500
                ? 'The server reported an unexpected error.'
                : status === null
                    ? networkErrorMessage(error)
                : 'The request could not be completed.';

    logAxiosDiagnostics(error);

    return new ApiError(
        payload?.error?.message ?? payload?.message ?? fallback,
        status,
        payload?.error?.code ?? error.code ?? null,
        payload?.error?.type ?? (status === null ? networkErrorType(error) : null),
        fields,
    );
}

export function fieldError(error: ApiError | null, field: string): string | undefined {
    return error?.fields[field]?.[0];
}

export function firstFieldError(error: ApiError | null, fields: string[]): string | undefined {
    for (const field of fields) {
        const message = fieldError(error, field);
        if (message) return message;
    }
    return undefined;
}

export function hasFieldError(error: ApiError | null, field: string): boolean {
    return Boolean(fieldError(error, field));
}

export function hasNestedFieldError(error: ApiError | null, prefix: string): boolean {
    if (!error) return false;
    return Object.keys(error.fields).some((field) => field === prefix || field.startsWith(`${prefix}.`));
}

export function nestedFieldError(error: ApiError | null, prefix: string, field: string): string | undefined {
    return firstFieldError(error, [`${prefix}.${field}`, field]);
}

function networkErrorMessage(error: unknown): string {
    if (!axios.isAxiosError(error)) return 'The request could not be completed.';
    if (error.code === 'ECONNABORTED' || error.message.toLowerCase().includes('timeout')) {
        return 'The request timed out. Check your connection and try again.';
    }
    if (typeof window !== 'undefined' && window.navigator.onLine === false) {
        return 'This device appears to be offline. Reconnect and try again.';
    }
    if (error.code === 'ERR_NETWORK') {
        return 'The browser blocked or lost the network request. Check connectivity and try again.';
    }

    return 'A network error prevented the request from completing. Try again.';
}

function networkErrorType(error: unknown): string {
    if (!axios.isAxiosError(error)) return 'network';
    if (error.code === 'ECONNABORTED' || error.message.toLowerCase().includes('timeout')) return 'timeout';
    if (typeof window !== 'undefined' && window.navigator.onLine === false) return 'offline';
    if (error.code === 'ERR_NETWORK') return 'network';

    return 'client';
}

function logAxiosDiagnostics(error: unknown): void {
    if (!import.meta.env.DEV || !axios.isAxiosError(error)) return;

    console.error('API request failed', {
        code: error.code,
        method: error.config?.method,
        baseURL: error.config?.baseURL,
        url: error.config?.url,
        timeout: error.config?.timeout,
        status: error.response?.status ?? null,
    });
}
