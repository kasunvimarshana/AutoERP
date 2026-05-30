import type { ApiCollectionResponse, ApiResponse } from '../../../services/api/apiResponse';
import { ApiError } from '../../../services/api/apiErrors';
import { httpClient } from '../../../services/api/httpClient';
import { mockCollectionResponse, mockResponse } from '../../../services/mock/mockResponse';
import {
    getUomConversionById,
    getUomUnitById,
    mockUomPreview,
    uomActivity,
    uomCategories,
    uomConversions,
    uomUnits,
    uomUsage,
} from '../mock/uomMock';
import type {
    UomAuditEntry,
    UomCategory,
    UomConversion,
    UomConversionFormInput,
    UomConversionPreview,
    UomItemUsage,
    UomUnit,
    UomUnitFormInput,
    UomUnitStatus,
    UomUnitType,
} from '../types/uom.types';

type BackendRecord = Record<string, unknown>;

const UOM_API_MODE = import.meta.env.VITE_UOM_API_MODE ?? 'auto';

function shouldUseMockOnly() {
    return UOM_API_MODE === 'mock';
}

async function withMockFallback<T>(realCall: () => Promise<T>, mockCall: () => Promise<T>, fallbackStatuses = [401, 403, 404, 419]): Promise<T> {
    if (shouldUseMockOnly()) {
        return mockCall();
    }

    try {
        return await realCall();
    } catch (error) {
        if (UOM_API_MODE === 'real') {
            throw error;
        }

        if (error instanceof ApiError && !fallbackStatuses.includes(error.status)) {
            throw error;
        }

        return mockCall();
    }
}

function asString(value: unknown, fallback = '') {
    return value === null || value === undefined ? fallback : String(value);
}

function asBool(value: unknown, fallback = false) {
    return value === null || value === undefined ? fallback : Boolean(value);
}

function normalizeType(value: unknown): UomUnitType {
    const type = asString(value, 'count').toLowerCase();
    const allowed: UomUnitType[] = ['count', 'distance', 'duration', 'mass', 'service', 'volume'];

    return allowed.includes(type as UomUnitType) ? (type as UomUnitType) : 'count';
}

function categoryForType(type: UomUnitType) {
    return uomCategories.find((category) => category.type === type)?.name ?? 'Count';
}

function normalizeUnit(raw: BackendRecord): UomUnit {
    const type = normalizeType(raw.type);
    const symbol = asString(raw.symbol ?? raw.code, 'UOM');
    const metadata = raw.metadata && typeof raw.metadata === 'object' ? raw.metadata as BackendRecord : {};

    return {
        allowFractional: Number(raw.precision ?? raw.decimal_precision ?? 0) > 0,
        category: asString(raw.category_name, categoryForType(type)),
        code: asString(raw.code ?? raw.symbol, symbol.toUpperCase()),
        description: asString(raw.description, ''),
        id: asString(raw.id),
        isBase: asBool(raw.is_base),
        name: asString(raw.name, 'Unnamed unit'),
        precision: Number(raw.precision ?? raw.decimal_precision ?? 0),
        status: raw.is_active === false ? 'inactive' : 'active',
        symbol,
        type,
        updatedAt: asString(raw.updated_at, 'Backend timestamp pending'),
        usableForCharge: asBool(metadata.usable_for_charge ?? raw.usable_for_charge, ['duration', 'distance', 'service'].includes(type)),
        usableForConsumption: asBool(metadata.usable_for_consumption ?? raw.usable_for_consumption, ['count', 'duration', 'volume'].includes(type)),
        usableForInventory: asBool(raw.usable_for_inventory, ['count', 'mass', 'volume'].includes(type)),
        usableForIssue: asBool(metadata.usable_for_issue ?? raw.usable_for_issue, ['count', 'mass', 'volume'].includes(type)),
        usableForReceipt: asBool(metadata.usable_for_receipt ?? raw.usable_for_receipt, ['count', 'mass', 'volume'].includes(type)),
    };
}

function normalizeConversion(raw: BackendRecord): UomConversion {
    const fromUnitId = asString(raw.from_uom_id ?? raw.fromUnitId);
    const toUnitId = asString(raw.to_uom_id ?? raw.toUnitId);
    const fromUnit = uomUnits.find((unit) => unit.id === fromUnitId);
    const toUnit = uomUnits.find((unit) => unit.id === toUnitId);

    return {
        category: fromUnit && toUnit && fromUnit.category === toUnit.category ? fromUnit.category : asString(raw.category, 'Backend category'),
        direction: asBool(raw.is_bidirectional, true) ? 'bidirectional' : 'one_way',
        factor: asString(raw.factor, 'Backend factor'),
        fromUnitCode: fromUnit?.code ?? asString(raw.from_uom_code ?? raw.from_uom_id, 'FROM'),
        fromUnitId,
        id: asString(raw.id),
        isActive: asBool(raw.is_active, true),
        isItemSpecific: raw.item_id !== null && raw.item_id !== undefined,
        itemName: asString(raw.item_name, ''),
        toUnitCode: toUnit?.code ?? asString(raw.to_uom_code ?? raw.to_uom_id, 'TO'),
        toUnitId,
        updatedAt: asString(raw.updated_at, 'Backend timestamp pending'),
    };
}

function normalizePreview(raw: BackendRecord, input: UomConversionPreview['input']): UomConversionPreview {
    const calculated = (raw.calculated && typeof raw.calculated === 'object' ? raw.calculated : {}) as BackendRecord;

    return {
        breakdown: Array.isArray(raw.breakdown) ? raw.breakdown as UomConversionPreview['breakdown'] : [{ label: 'Backend response', value: 'Conversion service returned result' }],
        calculated: {
            convertedQuantity: asString(calculated.converted_quantity ?? raw.result ?? raw.calculated_quantity ?? raw.converted_quantity, 'Backend-owned conversion result'),
            factor: asString(calculated.factor ?? raw.factor, 'Backend factor lookup'),
            precision: asString(calculated.precision ?? raw.precision, 'Backend precision / rounding'),
        },
        errors: Array.isArray(raw.errors) ? raw.errors.map(String) : [],
        input,
        warnings: Array.isArray(raw.warnings) ? raw.warnings.map(String) : [],
    };
}

function toBackendUnitPayload(input: UomUnitFormInput) {
    return {
        is_base: input.isBase,
        metadata: {
            code: input.code,
            description: input.description,
            precision: input.precision,
            usable_for_charge: input.usableForCharge,
            usable_for_consumption: input.usableForConsumption,
            usable_for_inventory: input.usableForInventory,
            usable_for_issue: input.usableForIssue,
            usable_for_receipt: input.usableForReceipt,
        },
        name: input.name,
        symbol: input.symbol || input.code,
        type: input.type,
    };
}

function toBackendConversionPayload(input: UomConversionFormInput) {
    return {
        factor: input.factor,
        from_uom_id: Number(input.fromUnitId) || 1,
        is_active: input.isActive,
        is_bidirectional: input.isBidirectional,
        item_id: input.isItemSpecific && input.itemId ? Number(input.itemId) : null,
        metadata: {
            category: input.category,
            effective_from: input.effectiveFrom || null,
            effective_to: input.effectiveTo || null,
        },
        to_uom_id: Number(input.toUnitId) || 1,
    };
}

export const uomApi = {
    activateUnit: (unitId: string) => uomApi.updateUnit(unitId, { ...getUomUnitById(unitId), status: 'active' }),
    convertQuantity: (input: UomConversionPreview['input']) => uomApi.previewConversion(input),
    createConversion: (input: UomConversionFormInput) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>('/api/uom/uom-conversions', { body: toBackendConversionPayload(input), method: 'POST' });
                return { ...response, data: normalizeConversion(response.data) };
            },
            () => mockResponse({ ...uomConversions[0], id: 'mock-conversion', isActive: input.isActive }),
        ),
    createUnit: (input: UomUnitFormInput) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>('/api/uom/units-of-measure', { body: toBackendUnitPayload(input), method: 'POST' });
                return { ...response, data: normalizeUnit(response.data) };
            },
            () => mockResponse({ ...uomUnits[0], code: input.code, id: 'mock-uom-unit', name: input.name, status: input.status, symbol: input.symbol, type: input.type }),
        ),
    deactivateConversion: (conversionId: string) =>
        withMockFallback(
            () => httpClient<ApiResponse<BackendRecord>>(`/api/uom/uom-conversions/${conversionId}`, { body: { is_active: false }, method: 'PATCH' }),
            () => mockResponse({ ...getUomConversionById(conversionId), isActive: false }),
        ),
    deactivateUnit: (unitId: string) => uomApi.updateUnit(unitId, { ...getUomUnitById(unitId), status: 'inactive' }),
    deleteConversion: (conversionId: string) =>
        withMockFallback(
            () => httpClient<ApiResponse<unknown>>(`/api/uom/uom-conversions/${conversionId}`, { method: 'DELETE' }),
            () => mockResponse({ action: 'delete-conversion', conversionId }),
        ),
    getConversion: (conversionId: string) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/uom/uom-conversions/${conversionId}`);
                return { ...response, data: normalizeConversion(response.data) };
            },
            () => mockResponse(getUomConversionById(conversionId)),
        ),
    getUnit: (unitId: string) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/uom/units-of-measure/${unitId}`);
                return { ...response, data: normalizeUnit(response.data) };
            },
            () => mockResponse(getUomUnitById(unitId)),
        ),
    getUnitActivity: (_unitId: string): Promise<ApiCollectionResponse<UomAuditEntry>> => mockCollectionResponse(uomActivity),
    getUnitUsage: (unitId: string): Promise<ApiResponse<UomItemUsage>> =>
        mockResponse(uomUsage[unitId] ?? {
            inventory: 'Backend usage pending',
            items: 'Backend usage pending',
            pricing: 'Backend usage pending',
            charge: 'Backend usage pending',
            consumption: 'Backend usage pending',
            issue: 'Backend usage pending',
            receipt: 'Backend usage pending',
        }),
    listCategories: (): Promise<ApiCollectionResponse<UomCategory>> => mockCollectionResponse(uomCategories),
    listConversions: () =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/uom/uom-conversions');
                return { ...response, data: response.data.map(normalizeConversion) };
            },
            () => mockCollectionResponse(uomConversions),
        ),
    listUnits: () =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/uom/units-of-measure');
                return { ...response, data: response.data.map(normalizeUnit) };
            },
            () => mockCollectionResponse(uomUnits),
        ),
    previewConversion: (input: UomConversionPreview['input']) =>
        withMockFallback(
            async () => {
                const response = await httpClient<BackendRecord>('/api/uom/convert', {
                    body: {
                        from_uom_id: Number(input.fromUnitId) || 1,
                        item_id: input.itemId ? Number(input.itemId) : null,
                        quantity: input.quantity,
                        to_uom_id: Number(input.toUnitId) || 1,
                    },
                    method: 'POST',
                });
                return normalizePreview(response, input);
            },
            () => mockResponse(mockUomPreview(input)).then((response) => response.data),
        ),
    updateConversion: (conversionId: string, input: UomConversionFormInput) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/uom/uom-conversions/${conversionId}`, { body: toBackendConversionPayload(input), method: 'PUT' });
                return { ...response, data: normalizeConversion(response.data) };
            },
            () => mockResponse({ ...getUomConversionById(conversionId), isActive: input.isActive }),
        ),
    updateUnit: (unitId: string, input: UomUnitFormInput | UomUnit) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/uom/units-of-measure/${unitId}`, { body: toBackendUnitPayload(input as UomUnitFormInput), method: 'PUT' });
                return { ...response, data: normalizeUnit(response.data) };
            },
            () => mockResponse({ ...getUomUnitById(unitId), ...input }),
        ),
};
