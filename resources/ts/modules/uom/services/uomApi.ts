import type { ApiCollectionResponse, ApiResponse } from '../../../services/api/apiResponse';
import { httpClient } from '../../../services/api/httpClient';
import type {
    UomAuditEntry,
    UomCategory,
    UomConversion,
    UomConversionFormInput,
    UomConversionPreview,
    UomItemUsage,
    UomLookupOption,
    UomUnit,
    UomUnitFormInput,
    UomUnitStatus,
    UomUnitType,
} from '../types/uom.types';

type BackendRecord = Record<string, unknown>;

const LOOKUP_PAGE_SIZE = 25;

function asString(value: unknown, fallback = '') {
    return value === null || value === undefined ? fallback : String(value);
}

function asOptionalString(value: unknown) {
    const normalized = asString(value).trim();

    return normalized === '' ? undefined : normalized;
}

function asBool(value: unknown, fallback = false) {
    if (value === null || value === undefined) {
        return fallback;
    }

    return Boolean(value);
}

function asNumberOrUndefined(value: unknown) {
    if (value === null || value === undefined || value === '') {
        return undefined;
    }

    const parsed = Number(value);

    return Number.isFinite(parsed) ? parsed : undefined;
}

function normalizeType(value: unknown): UomUnitType {
    const upper = asString(value, 'UNIT').toUpperCase();
    const allowed: UomUnitType[] = ['UNIT', 'MASS', 'VOLUME', 'LENGTH', 'AREA', 'TIME', 'DISTANCE', 'OTHER'];

    return allowed.includes(upper as UomUnitType) ? (upper as UomUnitType) : 'OTHER';
}

function normalizeUnit(raw: BackendRecord): UomUnit {
    const type = normalizeType(raw.type ?? raw.category);
    const isActive = asBool(raw.is_active, true);

    return {
        allowFractional: asBool(raw.allow_fractional_quantity, Number(raw.decimal_precision ?? 0) > 0),
        category: normalizeType(raw.category ?? type),
        code: asString(raw.code ?? raw.symbol, 'UOM').toUpperCase(),
        description: asOptionalString(raw.description),
        id: asString(raw.id),
        isActive,
        isBase: asBool(raw.is_base),
        name: asString(raw.name, 'Unnamed unit'),
        precision: Number(raw.decimal_precision ?? 0),
        status: isActive ? 'active' : 'inactive',
        symbol: asString(raw.symbol ?? raw.code, 'uom'),
        type,
        updatedAt: asString(raw.updated_at, ''),
        usableForInventory: asBool(raw.usable_for_inventory, true),
        usableForPurchase: asBool(raw.usable_for_purchase, true),
        usableForRental: asBool(raw.usable_for_rental, false),
        usableForSales: asBool(raw.usable_for_sales, true),
        usableForService: asBool(raw.usable_for_service, true),
    };
}

function normalizeLookupOption(raw: BackendRecord): UomLookupOption {
    const id = asString(raw.id);
    const code = asOptionalString(raw.code);
    const name = asOptionalString(raw.name ?? raw.display_name);

    return {
        id,
        label: [code, name].filter(Boolean).join(' - ') || id,
    };
}

function normalizeCategory(raw: BackendRecord): UomCategory {
    const type = normalizeType(raw.type ?? raw.id);

    return {
        id: asString(raw.id ?? type, type),
        name: asString(raw.name, type),
        type,
        unitCount: Number(raw.unit_count ?? 0),
    };
}

function normalizeConversion(raw: BackendRecord, units = new Map<string, UomUnit>(), items = new Map<string, UomLookupOption>()): UomConversion {
    const fromUnitId = asString(raw.from_uom_id);
    const toUnitId = asString(raw.to_uom_id);
    const itemId = asOptionalString(raw.item_id);

    return {
        category: normalizeType(raw.category ?? units.get(fromUnitId)?.category ?? 'OTHER'),
        direction: asBool(raw.is_bidirectional, true) ? 'bidirectional' : 'one_way',
        effectiveFrom: asOptionalString(raw.effective_from),
        effectiveTo: asOptionalString(raw.effective_to),
        factor: asString(raw.factor, '0'),
        fromUnitCode: units.get(fromUnitId)?.code ?? asString(raw.from_uom_code ?? raw.from_uom_id, 'FROM'),
        fromUnitId,
        id: asString(raw.id),
        isActive: asBool(raw.is_active, true),
        isItemSpecific: itemId !== undefined,
        itemId,
        itemName: itemId ? items.get(itemId)?.label ?? asOptionalString(raw.item_name) : undefined,
        notes: asOptionalString(raw.notes),
        toUnitCode: units.get(toUnitId)?.code ?? asString(raw.to_uom_code ?? raw.to_uom_id, 'TO'),
        toUnitId,
        updatedAt: asString(raw.updated_at, ''),
    };
}

function normalizePreview(raw: BackendRecord, input: UomConversionPreview['input']): UomConversionPreview {
    const calculated = (raw.calculated && typeof raw.calculated === 'object' ? raw.calculated : {}) as BackendRecord;

    return {
        breakdown: Array.isArray(raw.breakdown) ? raw.breakdown as UomConversionPreview['breakdown'] : [],
        calculated: {
            convertedQuantity: asString(calculated.converted_quantity ?? raw.converted_quantity, ''),
            factor: asString(calculated.factor ?? raw.factor, ''),
            precision: asString(calculated.precision ?? raw.precision, ''),
        },
        errors: Array.isArray(raw.errors) ? raw.errors.map(String) : [],
        input,
        warnings: Array.isArray(raw.warnings) ? raw.warnings.map(String) : [],
    };
}

function normalizeUsage(raw: BackendRecord): UomItemUsage {
    const counts = (raw.counts && typeof raw.counts === 'object' ? raw.counts : raw) as BackendRecord;

    return {
        conversionsFrom: Number(counts.conversions_from ?? 0),
        conversionsTo: Number(counts.conversions_to ?? 0),
        inventory: Number(counts.inventory ?? 0),
        items: Number(counts.items ?? 0),
        pricing: Number(counts.pricing ?? 0),
        purchase: Number(counts.purchase ?? 0),
        rental: Number(counts.rental ?? 0),
        sales: Number(counts.sales ?? 0),
        service: Number(counts.service ?? 0),
    };
}

function toBackendUnitPayload(input: UomUnitFormInput) {
    const type = input.type || 'UNIT';

    return {
        allow_fractional_quantity: input.allowFractional,
        category: input.category || type,
        code: input.code.trim().toUpperCase(),
        decimal_precision: asNumberOrUndefined(input.precision) ?? 0,
        description: input.description || null,
        is_active: input.status === 'active',
        is_base: input.isBase,
        metadata: {},
        name: input.name,
        symbol: input.symbol || input.code,
        type,
        usable_for_inventory: input.usableForInventory,
        usable_for_purchase: input.usableForPurchase,
        usable_for_rental: input.usableForRental,
        usable_for_sales: input.usableForSales,
        usable_for_service: input.usableForService,
    };
}

function toBackendConversionPayload(input: UomConversionFormInput) {
    return {
        category: input.category || null,
        effective_from: input.effectiveFrom || null,
        effective_to: input.effectiveTo || null,
        factor: input.factor,
        from_uom_id: asNumberOrUndefined(input.fromUnitId),
        is_active: input.isActive,
        is_bidirectional: input.isBidirectional,
        item_id: input.isItemSpecific ? asNumberOrUndefined(input.itemId) ?? null : null,
        metadata: {},
        notes: input.notes || null,
        to_uom_id: asNumberOrUndefined(input.toUnitId),
    };
}

async function unitLookupMap() {
    const units = await uomApi.listUnits();

    return new Map(units.data.map((unit) => [unit.id, unit]));
}

async function itemLookupMap() {
    const items = await uomApi.listItemOptions();

    return new Map(items.data.map((item) => [item.id, item]));
}

export const uomApi = {
    activateConversion: async (conversionId: string) => {
        const [units, items] = await Promise.all([unitLookupMap(), itemLookupMap()]);
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/uom/uom-conversions/${conversionId}/activate`, { method: 'PATCH' });

        return { ...response, data: normalizeConversion(response.data, units, items) };
    },
    activateUnit: async (unitId: string) => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/uom/units-of-measure/${unitId}/activate`, { method: 'PATCH' });

        return { ...response, data: normalizeUnit(response.data) };
    },
    convertQuantity: (input: UomConversionPreview['input']) => uomApi.previewConversion(input),
    createConversion: async (input: UomConversionFormInput) => {
        const [units, items] = await Promise.all([unitLookupMap(), itemLookupMap()]);
        const response = await httpClient<ApiResponse<BackendRecord>>('/api/uom/uom-conversions', {
            body: toBackendConversionPayload(input),
            method: 'POST',
        });

        return { ...response, data: normalizeConversion(response.data, units, items) };
    },
    createUnit: async (input: UomUnitFormInput) => {
        const response = await httpClient<ApiResponse<BackendRecord>>('/api/uom/units-of-measure', {
            body: toBackendUnitPayload(input),
            method: 'POST',
        });

        return { ...response, data: normalizeUnit(response.data) };
    },
    deactivateConversion: async (conversionId: string) => {
        const [units, items] = await Promise.all([unitLookupMap(), itemLookupMap()]);
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/uom/uom-conversions/${conversionId}/deactivate`, { method: 'PATCH' });

        return { ...response, data: normalizeConversion(response.data, units, items) };
    },
    deactivateUnit: async (unitId: string) => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/uom/units-of-measure/${unitId}/deactivate`, { method: 'PATCH' });

        return { ...response, data: normalizeUnit(response.data) };
    },
    deleteConversion: (conversionId: string) => httpClient<void>(`/api/uom/uom-conversions/${conversionId}`, { method: 'DELETE' }),
    getConversion: async (conversionId: string) => {
        const [units, items] = await Promise.all([unitLookupMap(), itemLookupMap()]);
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/uom/uom-conversions/${conversionId}`);

        return { ...response, data: normalizeConversion(response.data, units, items) };
    },
    getUnit: async (unitId: string) => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/uom/units-of-measure/${unitId}`);

        return { ...response, data: normalizeUnit(response.data) };
    },
    getUnitActivity: async (unitId: string): Promise<ApiCollectionResponse<UomAuditEntry>> => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/audit/audit-logs', {
            query: { auditable_id: unitId, auditable_type: 'unit_of_measures', per_page: 50 },
        });

        return {
            ...response,
            data: response.data.map((entry) => ({
                actor: asString(entry.actor_name ?? entry.actor_id ?? 'System'),
                description: asString(entry.description ?? entry.event ?? 'UOM activity'),
                id: asString(entry.id),
                time: asString(entry.created_at ?? entry.occurred_at ?? ''),
            })),
        };
    },
    getUnitUsage: async (unitId: string): Promise<ApiResponse<UomItemUsage>> => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/uom/units-of-measure/${unitId}/usage`);

        return { ...response, data: normalizeUsage(response.data) };
    },
    listCategories: async (): Promise<ApiCollectionResponse<UomCategory>> => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/uom/categories');

        return { ...response, data: response.data.map(normalizeCategory) };
    },
    listConversions: async () => {
        const [units, items] = await Promise.all([unitLookupMap(), itemLookupMap()]);
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/uom/uom-conversions', {
            query: { per_page: LOOKUP_PAGE_SIZE },
        });

        return { ...response, data: response.data.map((record) => normalizeConversion(record, units, items)) };
    },
    listItemOptions: async () => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/item/items', {
            query: { per_page: LOOKUP_PAGE_SIZE },
        });

        return { ...response, data: response.data.map(normalizeLookupOption) };
    },
    listUnits: async () => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/uom/units-of-measure', {
            query: { per_page: LOOKUP_PAGE_SIZE },
        });

        return { ...response, data: response.data.map(normalizeUnit) };
    },
    previewConversion: async (input: UomConversionPreview['input']) => {
        const response = await httpClient<BackendRecord>('/api/uom/convert', {
            body: {
                from_uom_id: asNumberOrUndefined(input.fromUnitId),
                item_id: asNumberOrUndefined(input.itemId) ?? null,
                quantity: input.quantity,
                to_uom_id: asNumberOrUndefined(input.toUnitId),
            },
            method: 'POST',
        });

        return normalizePreview(response, input);
    },
    updateConversion: async (conversionId: string, input: UomConversionFormInput) => {
        const [units, items] = await Promise.all([unitLookupMap(), itemLookupMap()]);
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/uom/uom-conversions/${conversionId}`, {
            body: toBackendConversionPayload(input),
            method: 'PUT',
        });

        return { ...response, data: normalizeConversion(response.data, units, items) };
    },
    updateUnit: async (unitId: string, input: UomUnitFormInput | UomUnit) => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/uom/units-of-measure/${unitId}`, {
            body: toBackendUnitPayload(input as UomUnitFormInput),
            method: 'PUT',
        });

        return { ...response, data: normalizeUnit(response.data) };
    },
};
