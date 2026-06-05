import { getStoredTenantId } from './authTokenStorage';
import { httpClient } from './httpClient';
import type { ApiResponse } from './apiResponse';

type BackendRecord = Record<string, unknown>;

export type ItemUomOption = {
    allowedContexts: string[];
    category: string;
    code: string;
    conversionFactor: string | null;
    id: string;
    isBase: boolean;
    isDefaultForContext: boolean;
    label: string;
    name: string;
    symbol: string;
};

function asString(value: unknown, fallback = ''): string {
    return value === null || value === undefined || value === '' ? fallback : String(value);
}

function asBoolean(value: unknown): boolean {
    return value === true || value === 1 || value === '1' || value === 'true';
}

function normalizeUom(row: BackendRecord): ItemUomOption {
    const code = asString(row.code);
    const symbol = asString(row.symbol);
    const name = asString(row.name);

    return {
        allowedContexts: Array.isArray(row.allowed_contexts) ? row.allowed_contexts.map((value) => String(value)) : [],
        category: asString(row.category),
        code,
        conversionFactor: row.conversion_factor === null || row.conversion_factor === undefined ? null : asString(row.conversion_factor),
        id: asString(row.id),
        isBase: asBoolean(row.is_base),
        isDefaultForContext: asBoolean(row.is_default_for_context),
        label: code || symbol || name,
        name,
        symbol,
    };
}

export const itemUomApi = {
    async listForItem(itemId: string, context: string): Promise<ItemUomOption[]> {
        if (!itemId) {
            return [];
        }

        const response = await httpClient<ApiResponse<{ allowed_uoms?: BackendRecord[] }>>(`/api/item/items/${itemId}/uom-setup`, {
            query: {
                context,
                tenant_id: getStoredTenantId() || undefined,
            },
        });

        return (response.data.allowed_uoms ?? []).map(normalizeUom);
    },
};
