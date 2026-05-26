import type { ApiPaginatedEnvelope, ApiResourceEnvelope, ApiValidationErrors, PaginatedResult } from '../types/api';

const API_BASE_URL = 'http://127.0.0.1:8000/api';
const ACCESS_TOKEN_STORAGE_KEY = 'autoerp.auth.access_token';
const TENANT_ID_STORAGE_KEY = 'autoerp.tenant.id';

let unauthorizedHandler: (() => void) | null = null;

export class ApiError extends Error {
    status: number;
    data: unknown;

    constructor(message: string, status: number, data: unknown) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.data = data;
    }
}

export class UnauthorizedError extends ApiError {
    constructor(message: string, data: unknown) {
        super(message, 401, data);
        this.name = 'UnauthorizedError';
    }
}

export class ForbiddenError extends ApiError {
    constructor(message: string, data: unknown) {
        super(message, 403, data);
        this.name = 'ForbiddenError';
    }
}

export class ValidationError extends ApiError {
    errors: ApiValidationErrors;

    constructor(message: string, errors: ApiValidationErrors, data: unknown) {
        super(message, 422, data);
        this.name = 'ValidationError';
        this.errors = errors;
    }
}

type RequestOptions = {
    body?: BodyInit | Record<string, unknown> | null;
    headers?: HeadersInit;
    includeTenantHeader?: boolean;
    signal?: AbortSignal;
    query?: Record<string, string | number | boolean | null | undefined>;
};

export function getStoredAccessToken() {
    return window.localStorage.getItem(ACCESS_TOKEN_STORAGE_KEY);
}

export function setStoredAccessToken(token: string | null) {
    if (token) {
        window.localStorage.setItem(ACCESS_TOKEN_STORAGE_KEY, token);
        return;
    }

    window.localStorage.removeItem(ACCESS_TOKEN_STORAGE_KEY);
}

export function getStoredTenantId() {
    return window.localStorage.getItem(TENANT_ID_STORAGE_KEY);
}

export function setStoredTenantId(tenantId: string | null) {
    if (tenantId) {
        window.localStorage.setItem(TENANT_ID_STORAGE_KEY, tenantId);
        return;
    }

    window.localStorage.removeItem(TENANT_ID_STORAGE_KEY);
}

export function registerUnauthorizedHandler(handler: (() => void) | null) {
    unauthorizedHandler = handler;
}

function buildHeaders(body: RequestOptions['body'], headers?: HeadersInit, includeTenantHeader = true) {
    const nextHeaders = new Headers(headers);

    nextHeaders.set('Accept', 'application/json');

    if (body && !(body instanceof FormData)) {
        nextHeaders.set('Content-Type', 'application/json');
    }

    const token = getStoredAccessToken();
    if (token) {
        nextHeaders.set('Authorization', `Bearer ${token}`);
    }

    if (includeTenantHeader) {
        const tenantId = getStoredTenantId();
        if (tenantId) {
            nextHeaders.set('X-Tenant-ID', tenantId);
        }
    }

    return nextHeaders;
}

function buildUrl(path: string, query?: RequestOptions['query']) {
    const url = new URL(`${API_BASE_URL}${path}`);

    if (query) {
        for (const [key, value] of Object.entries(query)) {
            if (value === null || value === undefined || value === '') {
                continue;
            }

            url.searchParams.set(key, String(value));
        }
    }

    return url.toString();
}

const modulePathAliases: Record<string, string> = {
    '/suppliers': '/supplier/suppliers',
    '/supplier-contacts': '/supplier/supplier-contacts',
    '/supplier-addresses': '/supplier/supplier-addresses',
    '/supplier-vehicles': '/supplier/supplier-vehicles',
    '/supplier-items': '/supplier/supplier-items',
    '/products': '/item/items',
    '/product-categories': '/item/item-categories',
    '/product-brands': '/item/item-brands',
    '/product-variants': '/item/item-variants',
    '/product-identifiers': '/item/item-identifiers',
    '/units-of-measure': '/uom/units-of-measure',
    '/uom-conversions': '/uom/uom-conversions',
    '/warehouses': '/warehouse/warehouses',
    '/warehouse-locations': '/warehouse/warehouse-locations',
    '/tax/groups': '/finance/tax-groups',
    '/tax-rates': '/finance/tax-rates',
    '/tax-rules': '/finance/tax-rules',
    '/accounts': '/finance/accounts',
    '/journal-entries': '/finance/journal-entries',
    '/payments': '/payment/payments',
    '/payment-allocations': '/payment/payment-allocations',
    '/advance-payments': '/payment/advance-payments',
    '/purchase-orders': '/purchase/purchase-orders',
    '/purchase-order-lines': '/purchase/purchase-order-lines',
    '/grns': '/purchase/grn-headers',
    '/grn-lines': '/purchase/grn-lines',
    '/purchase-returns': '/purchase/purchase-returns',
    '/purchase-return-lines': '/purchase/purchase-return-lines',
    '/purchase-invoices': '/invoice/invoices',
    '/invoices': '/invoice/invoices',
    '/invoice-references': '/invoice/invoice-references',
    '/invoice-lines': '/invoice/invoice-lines',
    '/currencies': '/configuration/currencies',
    '/countries': '/configuration/countries',
};

function aliasPath(path: string) {
    const match = Object.keys(modulePathAliases)
        .sort((left, right) => right.length - left.length)
        .find((candidate) => path === candidate || path.startsWith(`${candidate}/`));

    return match ? `${modulePathAliases[match]}${path.slice(match.length)}` : null;
}

function serializeBody(body: RequestOptions['body']) {
    if (!body || body instanceof FormData) {
        return body;
    }

    return JSON.stringify(body);
}

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null;
}

function parseValidationErrors(data: unknown): ApiValidationErrors {
    if (!isRecord(data) || !isRecord(data.errors)) {
        return {};
    }

    const errors: ApiValidationErrors = {};

    for (const [key, value] of Object.entries(data.errors)) {
        if (Array.isArray(value)) {
            errors[key] = value.filter((item): item is string => typeof item === 'string');
        }
    }

    return errors;
}

function extractMessage(data: unknown, fallback: string) {
    if (isRecord(data) && typeof data.message === 'string' && data.message.trim()) {
        return data.message;
    }

    return fallback;
}

async function parseResponse(response: Response) {
    if (response.status === 204) {
        return null;
    }

    const contentType = response.headers.get('content-type');

    if (contentType?.includes('application/json')) {
        return response.json();
    }

    const text = await response.text();
    return text || null;
}

async function request<T>(method: string, path: string, options: RequestOptions = {}): Promise<T> {
    const includeTenantHeader = options.includeTenantHeader ?? true;
    const response = await fetch(buildUrl(path, options.query), {
        method,
        headers: buildHeaders(options.body, options.headers, includeTenantHeader),
        body: serializeBody(options.body),
        signal: options.signal,
    });

    const data = await parseResponse(response);

    if (response.ok) {
        return data as T;
    }

    const fallbackPath = response.status === 404 ? aliasPath(path) : null;
    if (fallbackPath && fallbackPath !== path) {
        const fallbackResponse = await fetch(buildUrl(fallbackPath, options.query), {
            method,
            headers: buildHeaders(options.body, options.headers, includeTenantHeader),
            body: serializeBody(options.body),
            signal: options.signal,
        });
        const fallbackData = await parseResponse(fallbackResponse);

        if (fallbackResponse.ok) {
            return fallbackData as T;
        }

        return handleErrorResponse(fallbackResponse, fallbackData);
    }

    return handleErrorResponse(response, data);
}

function handleErrorResponse<T>(response: Response, data: unknown): T {
    const message = extractMessage(data, 'Request failed.');

    if (response.status === 401) {
        unauthorizedHandler?.();
        throw new UnauthorizedError(message, data);
    }

    if (response.status === 403) {
        throw new ForbiddenError(message, data);
    }

    if (response.status === 422) {
        throw new ValidationError(message, parseValidationErrors(data), data);
    }

    throw new ApiError(message, response.status, data);
}

export function unwrapResource<T>(payload: ApiResourceEnvelope<T> | T): T {
    if (isRecord(payload) && 'data' in payload) {
        return payload.data as T;
    }

    return payload as T;
}

export function unwrapPaginated<T>(payload: ApiPaginatedEnvelope<T> | T[]): PaginatedResult<T> {
    if (Array.isArray(payload)) {
        return { items: payload, meta: null, links: null };
    }

    return {
        items: payload.data,
        meta: payload.meta ?? null,
        links: payload.links ?? null,
    };
}

export const apiClient = {
    get<T>(path: string, options?: Omit<RequestOptions, 'body'>) {
        return request<T>('GET', path, options);
    },
    post<T>(path: string, body?: RequestOptions['body'], options?: Omit<RequestOptions, 'body'>) {
        return request<T>('POST', path, { ...options, body });
    },
    put<T>(path: string, body?: RequestOptions['body'], options?: Omit<RequestOptions, 'body'>) {
        return request<T>('PUT', path, { ...options, body });
    },
    patch<T>(path: string, body?: RequestOptions['body'], options?: Omit<RequestOptions, 'body'>) {
        return request<T>('PATCH', path, { ...options, body });
    },
    delete<T>(path: string, options?: Omit<RequestOptions, 'body'>) {
        return request<T>('DELETE', path, options);
    },
};
