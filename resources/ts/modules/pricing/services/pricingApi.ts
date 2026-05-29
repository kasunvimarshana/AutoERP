import type { ApiCollectionResponse, ApiPreviewResponse, ApiResponse } from '../../../services/api/apiResponse';
import { ApiError } from '../../../services/api/apiErrors';
import { httpClient } from '../../../services/api/httpClient';
import { mockCollectionResponse, mockPreviewResponse, mockResponse } from '../../../services/mock/mockResponse';
import {
    customerPriceLists,
    discountRules,
    discounts,
    getPriceListById,
    getPricingRuleById,
    mockResolvePrice,
    priceHistory,
    priceListItems,
    priceLists,
    pricingActivity,
    pricingRuleConditions,
    pricingRules,
    pricingTiers,
    pricingUsage,
    supplierPriceLists,
} from '../mock/pricingMock';
import type {
    CustomerPriceList,
    Discount,
    DiscountRule,
    PriceHistory,
    PriceList,
    PriceListFormInput,
    PriceListItem,
    PriceListStatus,
    PriceListType,
    PriceResolveRequest,
    PriceResolveResult,
    PricingAuditEntry,
    PricingRule,
    PricingRuleCondition,
    PricingRuleFormInput,
    PricingTier,
    PricingUsageSummary,
    SupplierPriceList,
} from '../types/pricing.types';

type BackendRecord = Record<string, unknown>;
type DiscountPreviewInput = {
    baseAmount: string;
    discountType: 'percentage' | 'fixed';
    discountValue: string;
    quantity: string;
};
type DiscountPreviewCalculated = {
    appliedDiscounts: string;
    discountAmount: string;
    netAmount: string;
};

const PRICING_API_MODE = import.meta.env.VITE_PRICING_API_MODE ?? 'auto';

function shouldUseMockOnly() {
    return PRICING_API_MODE === 'mock';
}

async function withMockFallback<T>(realCall: () => Promise<T>, mockCall: () => Promise<T>, fallbackStatuses = [401, 403, 404, 419]): Promise<T> {
    if (shouldUseMockOnly()) {
        return mockCall();
    }

    try {
        return await realCall();
    } catch (error) {
        if (PRICING_API_MODE === 'real') {
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

function normalizeStatus(raw: BackendRecord): PriceListStatus {
    if (raw.status) {
        const status = asString(raw.status).toLowerCase();
        if (['active', 'inactive', 'draft', 'expired'].includes(status)) {
            return status as PriceListStatus;
        }
    }

    return raw.is_active === false ? 'inactive' : 'active';
}

function normalizePriceListType(value: unknown): PriceListType {
    const type = asString(value, 'sales').toLowerCase();
    const allowed: PriceListType[] = ['sales', 'purchase', 'customer', 'supplier', 'service', 'rental'];

    return allowed.includes(type as PriceListType) ? (type as PriceListType) : 'sales';
}

function metadata(raw: BackendRecord): BackendRecord {
    return raw.metadata && typeof raw.metadata === 'object' ? raw.metadata as BackendRecord : {};
}

function normalizePriceList(raw: BackendRecord): PriceList {
    const meta = metadata(raw);
    const type = normalizePriceListType(raw.type ?? meta.type);

    return {
        code: asString(raw.code ?? meta.code, `PL-${asString(raw.id, 'backend')}`),
        currency: asString(raw.currency ?? meta.currency ?? raw.currency_code, 'Backend currency'),
        description: asString(raw.description ?? meta.description, ''),
        id: asString(raw.id),
        isCustomerSpecific: asBool(meta.is_customer_specific, type === 'customer'),
        isSupplierSpecific: asBool(meta.is_supplier_specific, type === 'supplier'),
        moduleUsage: Array.isArray(meta.module_usage) ? meta.module_usage as PriceList['moduleUsage'] : ['sales'],
        name: asString(raw.name, 'Unnamed price list'),
        priority: asString(raw.priority ?? meta.priority, 'Backend priority'),
        status: normalizeStatus(raw),
        type,
        updatedAt: asString(raw.updated_at, 'Backend timestamp pending'),
        validFrom: asString(raw.valid_from, ''),
        validTo: asString(raw.valid_to, ''),
    };
}

function normalizePriceListItem(raw: BackendRecord): PriceListItem {
    const meta = metadata(raw);

    return {
        active: raw.is_active === false ? false : true,
        effectiveFrom: asString(raw.valid_from, ''),
        effectiveTo: asString(raw.valid_to, ''),
        id: asString(raw.id),
        itemCode: asString(raw.item_code ?? meta.item_code ?? raw.item_id, 'Backend item'),
        itemId: asString(raw.item_id),
        itemName: asString(raw.item_name ?? meta.item_name, 'Backend item'),
        minQuantity: asString(raw.min_quantity, 'Backend min quantity'),
        priceListId: asString(raw.price_list_id),
        uom: asString(raw.uom_code ?? meta.uom ?? raw.uom_id, 'Backend UOM'),
        unitPrice: asString(raw.price, 'Backend price'),
    };
}

function normalizePricingRule(raw: BackendRecord): PricingRule {
    const meta = metadata(raw);

    return {
        actionType: asString(raw.action_type, 'price_list') as PricingRule['actionType'],
        actionValue: asString(raw.action_value, 'Backend action value'),
        code: asString(raw.code, `RULE-${asString(raw.id, 'backend')}`),
        description: asString(raw.description, ''),
        id: asString(raw.id),
        isExclusive: asBool(raw.is_exclusive),
        isStackable: asBool(raw.is_stackable, true),
        name: asString(raw.name, 'Unnamed pricing rule'),
        priority: asString(raw.priority, 'Backend priority'),
        ruleType: asString(meta.rule_type ?? raw.applies_to_type, 'price_resolve') as PricingRule['ruleType'],
        sourceType: asString(raw.source_type, 'all') as PricingRule['sourceType'],
        status: normalizeStatus(raw),
        validFrom: asString(raw.valid_from, ''),
        validTo: asString(raw.valid_to, ''),
    };
}

function normalizeRuleCondition(raw: BackendRecord): PricingRuleCondition {
    return {
        conditionType: asString(raw.condition_type, 'condition'),
        field: asString(raw.field, 'Backend field'),
        id: asString(raw.id),
        operator: asString(raw.operator, 'equals'),
        ruleId: asString(raw.pricing_rule_id ?? raw.rule_id),
        sequence: Number(raw.sequence ?? 1),
        value: asString(raw.value_text ?? raw.value_number ?? raw.value_boolean ?? raw.value_date, 'Backend value'),
    };
}

function normalizeDiscount(raw: BackendRecord): Discount {
    return {
        code: asString(raw.code, `DISC-${asString(raw.id, 'backend')}`),
        discountType: asString(raw.discount_type, 'percentage') as Discount['discountType'],
        discountValue: asString(raw.discount_value, 'Backend discount value'),
        id: asString(raw.id),
        isExclusive: asBool(raw.is_exclusive),
        isStackable: asBool(raw.is_stackable, true),
        name: asString(raw.name, 'Unnamed discount'),
        priority: asString(raw.priority, 'Backend priority'),
        status: normalizeStatus(raw),
        validFrom: asString(raw.valid_from, ''),
        validTo: asString(raw.valid_to, ''),
    };
}

function normalizeTier(raw: BackendRecord): PricingTier {
    const meta = metadata(raw);

    return {
        active: raw.is_active === false ? false : true,
        id: asString(raw.id),
        itemName: asString(raw.item_name ?? meta.item_name ?? raw.item_id, 'Backend item'),
        maxQuantity: asString(raw.max_quantity, 'Backend max quantity'),
        minQuantity: asString(raw.min_quantity, 'Backend min quantity'),
        priceListId: asString(raw.price_list_id),
        tierName: asString(raw.name ?? meta.name, 'Backend tier'),
        unitPrice: asString(raw.price ?? raw.unit_price, 'Backend tier price'),
    };
}

function normalizeHistory(raw: BackendRecord): PriceHistory {
    return {
        actor: asString(raw.actor_name ?? raw.created_by, 'Backend actor'),
        change: asString(raw.change_note ?? raw.change_type, 'Backend price change'),
        effectiveDate: asString(raw.effective_date ?? raw.created_at, ''),
        id: asString(raw.id),
        itemName: asString(raw.item_name ?? raw.item_id, 'Backend item'),
        newPrice: asString(raw.new_price, 'Backend new price'),
        oldPrice: asString(raw.old_price, 'Backend old price'),
        priceListName: asString(raw.price_list_name ?? raw.price_list_id, 'Backend price list'),
    };
}

function toPriceListPayload(input: PriceListFormInput) {
    return {
        is_active: input.isActive,
        metadata: {
            code: input.code,
            currency: input.currency,
            description: input.description,
            is_customer_specific: input.isCustomerSpecific,
            is_supplier_specific: input.isSupplierSpecific,
            module_usage: input.moduleUsage,
            priority: input.priority,
        },
        name: input.name,
        type: input.type,
        valid_from: input.validFrom || null,
        valid_to: input.validTo || null,
    };
}

function toPricingRulePayload(input: PricingRuleFormInput) {
    return {
        action_type: input.actionType,
        action_value: input.actionValue,
        code: input.ruleCode,
        description: input.description,
        is_active: input.isActive,
        is_exclusive: input.isExclusive,
        is_stackable: input.isStackable,
        metadata: {
            conditions_note: input.conditionsNote,
            rule_type: input.ruleType,
        },
        name: input.name,
        priority: input.priority,
        source_type: input.sourceType === 'all' ? null : input.sourceType,
        valid_from: input.validFrom || null,
        valid_to: input.validTo || null,
    };
}

export const pricingApi = {
    activatePriceList: (priceListId: string) => pricingApi.updatePriceList(priceListId, { ...getPriceListById(priceListId), isActive: true }),
    activatePricingRule: (ruleId: string) => pricingApi.updatePricingRule(ruleId, { ...getPricingRuleById(ruleId), isActive: true, ruleCode: getPricingRuleById(ruleId).code }),
    createDiscount: (input: Partial<Discount>) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>('/api/pricing/discounts', { body: input, method: 'POST' });
                return { ...response, data: normalizeDiscount(response.data) };
            },
            () => mockResponse({ ...discounts[0], ...input, id: 'mock-discount' }),
        ),
    createPriceList: (input: PriceListFormInput) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>('/api/pricing/price-lists', { body: toPriceListPayload(input), method: 'POST' });
                return { ...response, data: normalizePriceList(response.data) };
            },
            () => mockResponse({ ...priceLists[0], code: input.code, id: 'mock-price-list', isCustomerSpecific: input.isCustomerSpecific, isSupplierSpecific: input.isSupplierSpecific, moduleUsage: input.moduleUsage, name: input.name, status: input.isActive ? 'active' : 'inactive', type: input.type }),
        ),
    createPricingRule: (input: PricingRuleFormInput) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>('/api/pricing/pricing-rules', { body: toPricingRulePayload(input), method: 'POST' });
                return { ...response, data: normalizePricingRule(response.data) };
            },
            () => mockResponse({ ...pricingRules[0], code: input.ruleCode, id: 'mock-pricing-rule', name: input.name, status: input.isActive ? 'active' : 'inactive' }),
        ),
    deactivatePriceList: (priceListId: string) => pricingApi.updatePriceList(priceListId, { ...getPriceListById(priceListId), isActive: false }),
    deactivatePricingRule: (ruleId: string) => pricingApi.updatePricingRule(ruleId, { ...getPricingRuleById(ruleId), isActive: false, ruleCode: getPricingRuleById(ruleId).code }),
    getPriceList: (priceListId: string) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/pricing/price-lists/${priceListId}`);
                return { ...response, data: normalizePriceList(response.data) };
            },
            () => mockResponse(getPriceListById(priceListId)),
        ),
    getPricingActivity: (_id?: string): Promise<ApiCollectionResponse<PricingAuditEntry>> => mockCollectionResponse(pricingActivity),
    getPricingRule: (ruleId: string) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/pricing/pricing-rules/${ruleId}`);
                return { ...response, data: normalizePricingRule(response.data) };
            },
            () => mockResponse(getPricingRuleById(ruleId)),
        ),
    getPricingUsage: (_id?: string): Promise<ApiResponse<PricingUsageSummary>> => mockResponse(pricingUsage),
    listCustomerPriceLists: (): Promise<ApiCollectionResponse<CustomerPriceList>> => mockCollectionResponse(customerPriceLists),
    listDiscountRules: (): Promise<ApiCollectionResponse<DiscountRule>> => mockCollectionResponse(discountRules),
    listDiscounts: () =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/pricing/discounts');
                return { ...response, data: response.data.map(normalizeDiscount) };
            },
            () => mockCollectionResponse(discounts),
        ),
    listPriceHistory: () =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/pricing/price-histories');
                return { ...response, data: response.data.map(normalizeHistory) };
            },
            () => mockCollectionResponse(priceHistory),
        ),
    listPriceListItems: (priceListId?: string) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/pricing/price-list-items', { query: { price_list_id: priceListId } });
                return { ...response, data: response.data.map(normalizePriceListItem) };
            },
            () => mockCollectionResponse(priceListItems.filter((item) => !priceListId || item.priceListId === priceListId)),
        ),
    listPriceLists: () =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/pricing/price-lists');
                return { ...response, data: response.data.map(normalizePriceList) };
            },
            () => mockCollectionResponse(priceLists),
        ),
    listPricingRuleConditions: (ruleId?: string) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/pricing/pricing-rule-conditions', { query: { pricing_rule_id: ruleId } });
                return { ...response, data: response.data.map(normalizeRuleCondition) };
            },
            () => mockCollectionResponse(pricingRuleConditions.filter((condition) => !ruleId || condition.ruleId === ruleId)),
        ),
    listPricingRules: () =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/pricing/pricing-rules');
                return { ...response, data: response.data.map(normalizePricingRule) };
            },
            () => mockCollectionResponse(pricingRules),
        ),
    listPricingTiers: (): Promise<ApiCollectionResponse<PricingTier>> =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/pricing/pricing-tiers');
                return { ...response, data: response.data.map(normalizeTier) };
            },
            () => mockCollectionResponse(pricingTiers),
        ),
    listSupplierPriceLists: (): Promise<ApiCollectionResponse<SupplierPriceList>> => mockCollectionResponse(supplierPriceLists),
    previewDiscount: (input: DiscountPreviewInput): Promise<ApiPreviewResponse<DiscountPreviewInput, DiscountPreviewCalculated>> =>
        withMockFallback(
            async () => {
                const response = await httpClient<BackendRecord>('/api/pricing/discounts/preview-calculate', {
                    body: {
                        base_amount: input.baseAmount,
                        discount_type: input.discountType,
                        discount_value: input.discountValue,
                        quantity: input.quantity,
                    },
                    method: 'POST',
                });
                const calculated = (response.calculated && typeof response.calculated === 'object' ? response.calculated : response) as BackendRecord;

                return {
                    breakdown: Array.isArray(response.breakdown) ? response.breakdown as Array<Record<string, unknown>> : [{ label: 'Backend discount preview', value: 'Discount service returned calculated result' }],
                    calculated: {
                        appliedDiscounts: asString(calculated.applied_discounts, 'Backend applied discounts'),
                        discountAmount: asString(calculated.discount_amount, 'Backend discount amount'),
                        netAmount: asString(calculated.net_amount, 'Backend net amount'),
                    },
                    errors: Array.isArray(response.errors) ? response.errors.map(String) : [],
                    input,
                    warnings: Array.isArray(response.warnings) ? response.warnings.map(String) : [],
                };
            },
            () => mockPreviewResponse(input, { appliedDiscounts: 'Backend/mock discount applied', discountAmount: 'Backend/mock discount amount', netAmount: 'Backend/mock net amount' }),
        ),
    removePriceListItem: (itemId: string) =>
        withMockFallback(
            () => httpClient<ApiResponse<unknown>>(`/api/pricing/price-list-items/${itemId}`, { method: 'DELETE' }),
            () => mockResponse({ action: 'remove-price-list-item', itemId }),
        ),
    removePricingRuleCondition: (conditionId: string) =>
        withMockFallback(
            () => httpClient<ApiResponse<unknown>>(`/api/pricing/pricing-rule-conditions/${conditionId}`, { method: 'DELETE' }),
            () => mockResponse({ action: 'remove-rule-condition', conditionId }),
        ),
    removePricingTier: (tierId: string) =>
        withMockFallback(
            () => httpClient<ApiResponse<unknown>>(`/api/pricing/pricing-tiers/${tierId}`, { method: 'DELETE' }),
            () => mockResponse({ action: 'remove-pricing-tier', tierId }),
        ),
    resolvePrice: (input: PriceResolveRequest): Promise<PriceResolveResult> =>
        withMockFallback(
            async () => {
                const response = await httpClient<BackendRecord>('/api/pricing/resolve-price', {
                    body: {
                        item_id: Number(input.itemId) || 1,
                        party_id: input.customerId || input.supplierId || null,
                        party_type: input.customerId ? 'customer' : input.supplierId ? 'supplier' : null,
                        price_list_id: input.priceListId || null,
                        quantity: input.quantity,
                        source_type: input.moduleSource,
                        date: input.date,
                        uom_id: Number(input.uomId) || 1,
                    },
                    method: 'POST',
                });
                const calculated = (response.calculated && typeof response.calculated === 'object' ? response.calculated : response) as BackendRecord;

                return {
                    appliedDiscount: asString(calculated.discount_amount ?? response.applied_discount ?? response.discount, 'Backend discount result'),
                    appliedRule: asString(response.applied_rule ?? response.rule, 'Backend applied rule'),
                    breakdown: Array.isArray(response.breakdown) ? response.breakdown as PriceResolveResult['breakdown'] : [{ label: 'Backend resolver', value: 'Resolved price service returned result' }],
                    errors: Array.isArray(response.errors) ? response.errors.map(String) : [],
                    input,
                    netUnitPrice: asString(calculated.net_unit_price ?? calculated.net_price ?? calculated.net_amount, 'Backend net unit price'),
                    resolvedUnitPrice: asString(calculated.unit_price ?? calculated.resolved_unit_price ?? calculated.price, 'Backend resolved unit price'),
                    selectedPriceList: asString(calculated.price_list_name ?? calculated.price_list_id, 'Backend selected price list'),
                    tierInfo: asString(calculated.tier_info ?? calculated.tier, 'Backend tier result'),
                    warnings: Array.isArray(response.warnings) ? response.warnings.map(String) : [],
                };
            },
            () => mockResponse(mockResolvePrice(input)).then((response) => response.data),
        ),
    updateDiscount: (discountId: string, input: Partial<Discount>) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/pricing/discounts/${discountId}`, { body: input, method: 'PUT' });
                return { ...response, data: normalizeDiscount(response.data) };
            },
            () => mockResponse({ ...discounts[0], ...input, id: discountId }),
        ),
    updatePriceList: (priceListId: string, input: PriceListFormInput | (PriceList & { isActive?: boolean })) =>
        withMockFallback(
            async () => {
                const payload = 'isActive' in input && input.isActive !== undefined ? { is_active: input.isActive } : toPriceListPayload(input as PriceListFormInput);
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/pricing/price-lists/${priceListId}`, { body: payload, method: 'PUT' });
                return { ...response, data: normalizePriceList(response.data) };
            },
            () => mockResponse({ ...getPriceListById(priceListId), ...input, status: 'isActive' in input ? (input.isActive ? 'active' : 'inactive') : getPriceListById(priceListId).status }),
        ),
    updatePricingRule: (ruleId: string, input: PricingRuleFormInput | (PricingRule & { isActive?: boolean; ruleCode?: string })) =>
        withMockFallback(
            async () => {
                const payload = 'conditionsNote' in input ? toPricingRulePayload(input) : { is_active: input.isActive ?? input.status === 'active' };
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/pricing/pricing-rules/${ruleId}`, { body: payload, method: 'PUT' });
                return { ...response, data: normalizePricingRule(response.data) };
            },
            () => mockResponse({ ...getPricingRuleById(ruleId), ...input, status: 'isActive' in input ? (input.isActive ? 'active' : 'inactive') : getPricingRuleById(ruleId).status }),
        ),
    upsertPriceListItem: (input: Partial<PriceListItem>) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(input.id ? `/api/pricing/price-list-items/${input.id}` : '/api/pricing/price-list-items', { body: input, method: input.id ? 'PUT' : 'POST' });
                return { ...response, data: normalizePriceListItem(response.data) };
            },
            () => mockResponse({ ...priceListItems[0], ...input, id: input.id ?? 'mock-price-list-item' }),
        ),
    upsertPricingRuleCondition: (input: Partial<PricingRuleCondition>) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(input.id ? `/api/pricing/pricing-rule-conditions/${input.id}` : '/api/pricing/pricing-rule-conditions', { body: input, method: input.id ? 'PUT' : 'POST' });
                return { ...response, data: normalizeRuleCondition(response.data) };
            },
            () => mockResponse({ ...pricingRuleConditions[0], ...input, id: input.id ?? 'mock-rule-condition' }),
        ),
    upsertPricingTier: (input: Partial<PricingTier>) =>
        withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(input.id ? `/api/pricing/pricing-tiers/${input.id}` : '/api/pricing/pricing-tiers', { body: input, method: input.id ? 'PUT' : 'POST' });
                return { ...response, data: normalizeTier(response.data) };
            },
            () => mockResponse({ ...pricingTiers[0], ...input, id: input.id ?? 'mock-pricing-tier' }),
        ),
};
