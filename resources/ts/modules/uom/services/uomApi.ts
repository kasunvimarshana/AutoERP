import type { ApiCollectionResponse, ApiResponse } from '../../../services/api/apiResponse';
import { httpClient } from '../../../services/api/httpClient';
import type { Uom, UomInput, UomListItem, UomLookup, UomPage, UomStatus } from '../types/uom.types';

type UomRecord = {
    created_at?: string;
    decimal_precision?: number;
    id: number;
    is_base?: boolean;
    name: string;
    notes?: string | null;
    organization_unit_id?: number | null;
    row_version?: number;
    status?: UomStatus;
    symbol?: string | null;
    tenant_id?: number;
    uom_code: string;
    updated_at?: string;
};

let lookupCache: UomLookup[] | null = null;
let lookupRequest: Promise<UomLookup[]> | null = null;

function lookup(record: UomRecord): UomLookup {
    return {
        id: record.id,
        name: record.name,
        symbol: record.symbol ?? undefined,
        uomCode: record.uom_code,
    };
}

function listItem(record: UomRecord): UomListItem {
    return {
        ...lookup(record),
        createdAt: record.created_at ?? '',
        decimalPrecision: record.decimal_precision ?? 2,
        isBase: record.is_base ?? false,
        organizationUnitId: record.organization_unit_id ?? undefined,
        status: record.status ?? 'active',
        updatedAt: record.updated_at ?? '',
    };
}

function detail(record: UomRecord): Uom {
    return {
        ...listItem(record),
        notes: record.notes ?? undefined,
        rowVersion: record.row_version ?? 1,
        tenantId: record.tenant_id ?? 0,
    };
}

function payload(input: UomInput) {
    return {
        decimal_precision: input.decimalPrecision,
        is_base: input.isBase,
        name: input.name.trim(),
        notes: input.notes?.trim() || null,
        status: input.status,
        symbol: input.symbol?.trim() || null,
        uom_code: input.uomCode.trim(),
    };
}

function invalidateLookup() {
    lookupCache = null;
    lookupRequest = null;
}

export const uomApi = {
    async create(input: UomInput): Promise<Uom> {
        const response = await httpClient<ApiResponse<UomRecord>>('/api/uom/uoms', {
            body: payload(input),
            method: 'POST',
        });
        invalidateLookup();

        return detail(response.data);
    },
    async get(id: number): Promise<Uom> {
        const response = await httpClient<ApiResponse<UomRecord>>(`/api/uom/uoms/${id}`);

        return detail(response.data);
    },
    async list(query: { page: number; perPage: number; search?: string; status?: UomStatus }): Promise<UomPage> {
        const response = await httpClient<ApiCollectionResponse<UomRecord>>('/api/uom/uoms', {
            query: {
                page: query.page,
                per_page: query.perPage,
                search: query.search,
                status: query.status,
            },
        });

        return {
            items: response.data.map(listItem),
            meta: {
                currentPage: response.meta?.current_page ?? query.page,
                lastPage: response.meta?.last_page ?? 1,
                perPage: response.meta?.per_page ?? query.perPage,
                total: response.meta?.total ?? response.data.length,
            },
        };
    },
    lookup(): Promise<UomLookup[]> {
        if (lookupCache) return Promise.resolve(lookupCache);
        if (lookupRequest) return lookupRequest;

        lookupRequest = httpClient<ApiCollectionResponse<UomRecord>>('/api/uom/uoms/lookup')
            .then((response) => {
                lookupCache = response.data.map(lookup);

                return lookupCache;
            })
            .finally(() => {
                lookupRequest = null;
            });

        return lookupRequest;
    },
    async remove(id: number): Promise<void> {
        await httpClient<void>(`/api/uom/uoms/${id}`, { method: 'DELETE' });
        invalidateLookup();
    },
    async update(id: number, input: UomInput): Promise<Uom> {
        const response = await httpClient<ApiResponse<UomRecord>>(`/api/uom/uoms/${id}`, {
            body: payload(input),
            method: 'PUT',
        });
        invalidateLookup();

        return detail(response.data);
    },
};
