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

const UOM_CATEGORIES: UomCategory[] = [
    { id: 'UNIT', name: 'Unit', type: 'UNIT' },
    { id: 'MASS', name: 'Mass', type: 'MASS' },
    { id: 'VOLUME', name: 'Volume', type: 'VOLUME' },
    { id: 'LENGTH', name: 'Length', type: 'LENGTH' },
    { id: 'AREA', name: 'Area', type: 'AREA' },
    { id: 'TIME', name: 'Time', type: 'TIME' },
    { id: 'DISTANCE', name: 'Distance', type: 'DISTANCE' },
    { id: 'OTHER', name: 'Other', type: 'OTHER' },
];

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
        updatedAt: asString(raw.updated_at, 'Backend timestamp pending'),
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
        updatedAt: asString(raw.updated_at, 'Backend timestamp pending'),
    };
}

function normalizePreview(raw: BackendRecord, input: UomConversionPreview['input']): UomConversionPreview {
    const calculated = (raw.calculated && typeof raw.calculated === 'object' ? raw.calculated : {}) as BackendRecord;

    return {
        breakdown: Array.isArray(raw.breakdown) ? raw.breakdown as UomConversionPreview['breakdown'] : [],
        calculated: {
            convertedQuantity: asString(calculated.converted_quantity ?? raw.converted_quantity, 'Backend conversion unavailable'),
            factor: asString(calculated.factor ?? raw.factor, 'Backend factor unavailable'),
            precision: asString(calculated.precision ?? raw.precision, 'Backend precision unavailable'),
        },
        errors: Array.isArray(raw.errors) ? raw.errors.map(String) : [],
        input,
        warnings: Array.isArray(raw.warnings) ? raw.warnings.map(String) : [],
    };
}

function toBackendUnitPayload(input: UomUnitFormInput) {
    const type = input.type || 'UNIT';

    return {
        allow_fractional_quantity: input.allowFractional,
        category: type,
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
    activateUnit: (unitId: string, unit: UomUnit) => uomApi.updateUnit(unitId, { ...unit, status: 'active' }),
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
    deactivateConversion: async (conversion: UomConversion) => {
        const response = await uomApi.updateConversion(conversion.id, {
            effectiveFrom: conversion.effectiveFrom,
            effectiveTo: conversion.effectiveTo,
            factor: conversion.factor,
            fromUnitId: conversion.fromUnitId,
            isActive: false,
            isBidirectional: conversion.direction === 'bidirectional',
            isItemSpecific: conversion.isItemSpecific,
            itemId: conversion.itemId,
            notes: conversion.notes,
            toUnitId: conversion.toUnitId,
        });

        return response;
    },
    deactivateUnit: (unitId: string, unit: UomUnit) => uomApi.updateUnit(unitId, { ...unit, status: 'inactive' }),
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
    getUnitActivity: (_unitId: string): Promise<ApiCollectionResponse<UomAuditEntry>> => Promise.resolve({ data: [] }),
    getUnitUsage: (_unitId: string): Promise<ApiResponse<UomItemUsage>> =>
        Promise.resolve({
            data: {
                inventory: 'No backend usage endpoint exposed yet.',
                items: 'No backend usage endpoint exposed yet.',
                pricing: 'No backend usage endpoint exposed yet.',
                purchase: 'No backend usage endpoint exposed yet.',
                rental: 'No backend usage endpoint exposed yet.',
                sales: 'No backend usage endpoint exposed yet.',
                service: 'No backend usage endpoint exposed yet.',
            },
        }),
    listCategories: (): Promise<ApiCollectionResponse<UomCategory>> => Promise.resolve({ data: UOM_CATEGORIES }),
    listConversions: async () => {
        const [units, items] = await Promise.all([unitLookupMap(), itemLookupMap()]);
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/uom/uom-conversions', {
            query: { per_page: 200 },
        });

        return { ...response, data: response.data.map((record) => normalizeConversion(record, units, items)) };
    },
    listItemOptions: async () => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/item/items', {
            query: { per_page: 200 },
        });

        return { ...response, data: response.data.map(normalizeLookupOption) };
    },
    listUnits: async () => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/uom/units-of-measure', {
            query: { per_page: 200 },
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
