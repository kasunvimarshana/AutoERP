import { ApiError } from './apiErrors';
import { getStoredAccessToken, getStoredOrganizationUnitId, getStoredTenantId } from './authTokenStorage';

type HttpMethod = 'DELETE' | 'GET' | 'PATCH' | 'POST' | 'PUT';

type HttpClientOptions<TBody = unknown> = {
    body?: TBody;
    method?: HttpMethod;
    query?: Record<string, string | number | boolean | null | undefined>;
};

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL ?? '';

type ApiErrorPayload = {
    error?: {
        code?: unknown;
        details?: unknown;
        message?: unknown;
        type?: unknown;
    };
    errors?: unknown;
    message?: unknown;
};

function buildUrl(path: string, query?: HttpClientOptions['query']) {
    const url = new URL(`${API_BASE_URL}${path}`, window.location.origin);

    Object.entries(query ?? {}).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '') {
            url.searchParams.set(key, String(value));
        }
    });

    return url.toString();
}

function authHeaders() {
    const token = getStoredAccessToken();
    const tenantId = getStoredTenantId();
    const organizationUnitId = getStoredOrganizationUnitId();

    return {
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
        ...(tenantId ? { 'X-Tenant-ID': tenantId } : {}),
        ...(organizationUnitId ? { 'X-Organization-Unit-ID': organizationUnitId } : {}),
    };
}

function asFieldErrors(value: unknown): Record<string, string[]> {
    if (!value || typeof value !== 'object' || Array.isArray(value)) {
        return {};
    }

    return Object.entries(value as Record<string, unknown>).reduce<Record<string, string[]>>((errors, [field, messages]) => {
        if (Array.isArray(messages)) {
            errors[field] = messages.map((message) => String(message));

            return errors;
        }

        errors[field] = [String(messages)];

        return errors;
    }, {});
}

function asDetails(value: unknown): Record<string, unknown> {
    return value && typeof value === 'object' && !Array.isArray(value) ? (value as Record<string, unknown>) : {};
}

function apiErrorFromPayload(payload: ApiErrorPayload, status: number): ApiError {
    const normalized = payload.error && typeof payload.error === 'object' ? payload.error : undefined;
    const details = asDetails(normalized?.details);
    const fieldErrors = asFieldErrors(payload.errors ?? details.fields);

    return new ApiError(
        String(normalized?.message ?? payload.message ?? 'API request failed.'),
        status,
        fieldErrors,
        String(normalized?.code ?? 'API_REQUEST_FAILED'),
        String(normalized?.type ?? 'http'),
        details,
    );
}

export async function httpClient<TResponse, TBody = unknown>(
    path: string,
    { body, method = 'GET', query }: HttpClientOptions<TBody> = {},
): Promise<TResponse> {
    const response = await fetch(buildUrl(path, query), {
        body: body === undefined ? undefined : JSON.stringify(body),
        credentials: 'include',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...authHeaders(),
        },
        method,
    });

    if (response.status === 204) {
        return undefined as TResponse;
    }

    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw apiErrorFromPayload(payload as ApiErrorPayload, response.status);
    }

    return payload as TResponse;
}
