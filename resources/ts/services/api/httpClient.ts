import { ApiError } from './apiErrors';

type HttpMethod = 'DELETE' | 'GET' | 'PATCH' | 'POST' | 'PUT';

type HttpClientOptions<TBody = unknown> = {
    body?: TBody;
    method?: HttpMethod;
    query?: Record<string, string | number | boolean | null | undefined>;
};

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL ?? '';

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
    const token = window.localStorage.getItem('auth_token');
    const tenantId = window.localStorage.getItem('tenant_id');
    const organizationUnitId = window.localStorage.getItem('organization_unit_id');

    return {
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
        ...(tenantId ? { 'X-Tenant-ID': tenantId } : {}),
        ...(organizationUnitId ? { 'X-Organization-Unit-ID': organizationUnitId } : {}),
    };
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
        throw new ApiError(payload.message ?? 'API request failed.', response.status, payload.errors ?? {});
    }

    return payload as TResponse;
}
