import type { ApiCollectionResponse, ApiPreviewResponse, ApiResponse } from '../../../services/api/apiResponse';
import { httpClient } from '../../../services/api/httpClient';
import type {
    CustomerPriceList,
    Discount,
    DiscountInput,
    DiscountRule,
    LookupOption,
    PriceHistory,
    PriceList,
    PriceListFormInput,
    PriceListItem,
    PriceListItemInput,
    PriceListStatus,
    PriceListType,
    PriceResolveRequest,
    PriceResolveResult,
    PricingAuditEntry,
    PricingRule,
    PricingRuleCondition,
    PricingRuleFormInput,
    PricingTier,
    PricingTierInput,
    PricingUsageSummary,
    SupplierPriceList,
} from '../types/pricing.types';

type BackendRecord = Record<string, unknown>;

const LOOKUP_PAGE_SIZE = 50;
const DETAIL_PAGE_SIZE = 100;

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

function asString(value: unknown, fallback = '') {
    return value === null || value === undefined ? fallback : String(value);
}

function asOptionalString(value: unknown) {
    const text = asString(value).trim();

    return text === '' ? undefined : text;
}

function asBool(value: unknown, fallback = false) {
    if (value === null || value === undefined || value === '') {
        return fallback;
    }

    if (typeof value === 'boolean') {
        return value;
    }

    if (typeof value === 'number') {
        return value !== 0;
    }

    return ['1', 'true', 'yes', 'active'].includes(String(value).toLowerCase());
}

function asNumberOrUndefined(value: unknown) {
    if (value === null || value === undefined || value === '') {
        return undefined;
    }

    const parsed = Number(value);

    return Number.isFinite(parsed) && parsed > 0 ? parsed : undefined;
}

function asDecimal(value: unknown, fallback = '0') {
    if (value === null || value === undefined || value === '') {
        return fallback;
    }

    return String(value);
}

function collectionMeta<T>(response: ApiCollectionResponse<T>) {
    return response.meta ?? {
        current_page: 1,
        from: response.data.length ? 1 : 0,
        last_page: 1,
        per_page: response.data.length,
        to: response.data.length,
        total: response.data.length,
    };
}

function metadata(raw: BackendRecord): BackendRecord {
    return raw.metadata && typeof raw.metadata === 'object' && !Array.isArray(raw.metadata) ? raw.metadata as BackendRecord : {};
}

function normalizeStatus(raw: BackendRecord): PriceListStatus {
    return asBool(raw.is_active, true) ? 'active' : 'inactive';
}

function normalizePriceListType(value: unknown): PriceListType {
    const type = asString(value, 'generic').toLowerCase();
    const allowed: PriceListType[] = ['sales', 'purchase', 'customer', 'supplier', 'service', 'rental', 'generic'];

    return allowed.includes(type as PriceListType) ? type as PriceListType : 'generic';
}

function normalizeLookup(raw: BackendRecord): LookupOption {
    const code = asOptionalString(raw.code ?? raw.sku ?? raw.symbol);
    const name = asString(raw.name ?? raw.display_name ?? raw.label, code ?? 'Unnamed option');

    return {
        code,
        id: asString(raw.id),
        label: code ? `${code} - ${name}` : name,
        name,
    };
}

function normalizePriceList(raw: BackendRecord, currencies = new Map<string, LookupOption>()): PriceList {
    const meta = metadata(raw);
    const currencyId = asOptionalString(raw.currency_id);
    const currency = currencyId ? currencies.get(currencyId)?.code ?? currencies.get(currencyId)?.name ?? `Currency #${currencyId}` : asString(meta.currency, '');
    const scopeType = asString(raw.scope_type, 'generic');

    return {
        code: asString(raw.code ?? meta.code, `PL-${asString(raw.id)}`),
        currency,
        currencyId,
        description: asString(raw.description ?? meta.description, ''),
        id: asString(raw.id),
        isCustomerSpecific: scopeType === 'customer',
        isDefault: asBool(raw.is_default),
        isExclusive: asBool(raw.is_exclusive),
        isStackable: asBool(raw.is_stackable, true),
        isSupplierSpecific: scopeType === 'supplier',
        moduleUsage: Array.isArray(meta.module_usage) ? meta.module_usage as PriceList['moduleUsage'] : [],
        name: asString(raw.name, 'Unnamed price list'),
        priority: asString(raw.priority, '0'),
        status: normalizeStatus(raw),
        type: normalizePriceListType(raw.type),
        updatedAt: asString(raw.updated_at, ''),
        validFrom: asString(raw.valid_from, ''),
        validTo: asString(raw.valid_to, ''),
    };
}

function normalizePriceListItem(raw: BackendRecord, items = new Map<string, LookupOption>(), uoms = new Map<string, LookupOption>()): PriceListItem {
    const itemId = asString(raw.item_id);
    const uomId = asString(raw.uom_id);
    const item = items.get(itemId);
    const uom = uoms.get(uomId);

    return {
        active: asBool(raw.is_active, true),
        discountType: asString(raw.discount_type, 'percentage') as PriceListItem['discountType'],
        discountValue: asDecimal(raw.discount_value, '0'),
        effectiveFrom: asString(raw.valid_from, ''),
        effectiveTo: asString(raw.valid_to, ''),
        id: asString(raw.id),
        itemCode: item?.code ?? asString(raw.item_code, ''),
        itemId,
        itemName: item?.name ?? asString(raw.item_name ?? raw.item_label, 'Backend item'),
        maxQuantity: asString(raw.max_quantity, ''),
        minQuantity: asDecimal(raw.min_quantity, '1'),
        priceListId: asString(raw.price_list_id),
        priority: asString(raw.priority, '0'),
        uom: uom?.code ?? uom?.name ?? asString(raw.uom_label ?? raw.uom_code ?? raw.uom_symbol ?? raw.uom_name, 'Backend UOM'),
        uomId,
        unitPrice: asDecimal(raw.price),
    };
}

function normalizePricingRule(raw: BackendRecord): PricingRule {
    const meta = metadata(raw);

    return {
        actionType: asString(raw.action_type, 'adjust_price') as PricingRule['actionType'],
        actionValue: asString(raw.action_value, ''),
        code: asString(raw.code, `RULE-${asString(raw.id)}`),
        description: asString(raw.description, ''),
        id: asString(raw.id),
        isExclusive: asBool(raw.is_exclusive),
        isStackable: asBool(raw.is_stackable, true),
        name: asString(raw.name, 'Unnamed pricing rule'),
        priority: asString(raw.priority, '0'),
        ruleType: asString(meta.rule_type ?? raw.applies_to_type, 'generic') as PricingRule['ruleType'],
        sourceType: asString(raw.source_type, 'all') as PricingRule['sourceType'],
        status: normalizeStatus(raw),
        validFrom: asString(raw.valid_from, ''),
        validTo: asString(raw.valid_to, ''),
    };
}

function normalizeRuleCondition(raw: BackendRecord): PricingRuleCondition {
    return {
        conditionType: asString(raw.condition_type, 'field'),
        field: asString(raw.field),
        id: asString(raw.id),
        operator: asString(raw.operator, 'equals'),
        ruleId: asString(raw.pricing_rule_id),
        sequence: Number(raw.sequence ?? 1),
        value: asString(raw.value_text ?? raw.value_number ?? raw.value_boolean ?? raw.value_date, ''),
    };
}

function normalizeDiscount(raw: BackendRecord): Discount {
    return {
        code: asString(raw.code, `DISC-${asString(raw.id)}`),
        discountType: asString(raw.discount_type, 'percentage') as Discount['discountType'],
        discountValue: asDecimal(raw.discount_value),
        id: asString(raw.id),
        isExclusive: asBool(raw.is_exclusive),
        isStackable: asBool(raw.is_stackable, true),
        name: asString(raw.name, 'Unnamed discount'),
        priority: asString(raw.priority, '0'),
        status: normalizeStatus(raw),
        validFrom: asString(raw.valid_from, ''),
        validTo: asString(raw.valid_to, ''),
    };
}

function normalizeTier(raw: BackendRecord, priceListItems = new Map<string, PriceListItem>(), uoms = new Map<string, LookupOption>()): PricingTier {
    const priceListItemId = asString(raw.price_list_item_id);
    const uomId = asString(raw.uom_id);
    const linkedItem = priceListItems.get(priceListItemId);

    return {
        active: asBool(raw.is_active, true),
        adjustmentType: asString(raw.adjustment_type, '') as PricingTier['adjustmentType'],
        adjustmentValue: asString(raw.adjustment_value, ''),
        id: asString(raw.id),
        maxQuantity: asString(raw.max_quantity, ''),
        minQuantity: asDecimal(raw.min_quantity, '1'),
        priceListItemId,
        pricingRuleId: asString(raw.pricing_rule_id),
        sequence: asString(raw.sequence, '1'),
        tierName: linkedItem ? `${linkedItem.itemName} tier ${asString(raw.sequence, '1')}` : `Tier ${asString(raw.sequence, asString(raw.id))}`,
        unitPrice: asString(raw.price, ''),
        uomId,
    };
}

function normalizeHistory(raw: BackendRecord): PriceHistory {
    return {
        actor: asString(raw.actor_name ?? raw.changed_by ?? raw.created_by, 'System'),
        change: asString(raw.reason ?? raw.field_name ?? 'Price change'),
        effectiveDate: asString(raw.changed_at ?? raw.effective_date ?? raw.created_at, ''),
        id: asString(raw.id),
        itemName: asString(raw.item_name ?? raw.entity_type, ''),
        newPrice: asString(raw.new_text ?? raw.new_price, ''),
        oldPrice: asString(raw.old_text ?? raw.old_price, ''),
        priceListName: asString(raw.price_list_name ?? raw.entity_type, ''),
    };
}

function normalizeUsage(raw: BackendRecord): PricingUsageSummary {
    const counts = (raw.counts && typeof raw.counts === 'object' ? raw.counts : raw) as BackendRecord;

    return {
        counts: {
            conditions: Number(counts.conditions ?? 0),
            customerLinks: Number(counts.customer_links ?? 0),
            historyEntries: Number(counts.history_entries ?? 0),
            priceListItems: Number(counts.price_list_items ?? 0),
            purchaseReferences: Number(counts.purchase_references ?? 0),
            rentalReferences: Number(counts.rental_references ?? 0),
            salesReferences: Number(counts.sales_references ?? 0),
            serviceReferences: Number(counts.service_references ?? 0),
            supplierLinks: Number(counts.supplier_links ?? 0),
            tiers: Number(counts.tiers ?? 0),
        },
    };
}

function priceListPayload(input: PriceListFormInput): BackendRecord {
    return {
        code: input.code || null,
        currency_id: asNumberOrUndefined(input.currencyId) ?? null,
        description: input.description || null,
        is_active: input.isActive,
        is_default: input.isDefault,
        is_exclusive: input.isExclusive,
        is_stackable: input.isStackable,
        metadata: { module_usage: input.moduleUsage },
        name: input.name,
        priority: Number(input.priority || 0),
        scope_type: input.isCustomerSpecific ? 'customer' : input.isSupplierSpecific ? 'supplier' : 'generic',
        type: input.type,
        valid_from: input.validFrom || null,
        valid_to: input.validTo || null,
    };
}

function priceListItemPayload(input: PriceListItemInput): BackendRecord {
    return {
        discount_type: input.discountType,
        discount_value: Number(input.discountValue || 0),
        is_active: input.isActive,
        item_id: asNumberOrUndefined(input.itemId),
        max_quantity: input.maxQuantity ? Number(input.maxQuantity) : null,
        min_quantity: Number(input.minQuantity || 1),
        price: Number(input.unitPrice || 0),
        price_list_id: asNumberOrUndefined(input.priceListId),
        priority: Number(input.priority || 0),
        uom_id: asNumberOrUndefined(input.uomId),
        valid_from: input.effectiveFrom || null,
        valid_to: input.effectiveTo || null,
    };
}

function pricingRulePayload(input: PricingRuleFormInput): BackendRecord {
    const conditions = input.conditionField.trim()
        ? [{
            condition_type: 'field',
            field: input.conditionField,
            operator: input.conditionOperator || 'equals',
            sequence: 1,
            value_text: input.conditionValue,
        }]
        : [];

    return {
        action_type: input.actionType,
        action_value: input.actionValue === '' ? null : Number(input.actionValue),
        applies_to_type: input.ruleType,
        code: input.ruleCode || null,
        conditions,
        description: input.description || null,
        is_active: input.isActive,
        is_exclusive: input.isExclusive,
        is_stackable: input.isStackable,
        metadata: { rule_type: input.ruleType },
        name: input.name,
        priority: Number(input.priority || 0),
        source_type: input.sourceType === 'all' ? null : input.sourceType,
        valid_from: input.validFrom || null,
        valid_to: input.validTo || null,
    };
}

function discountPayload(input: DiscountInput): BackendRecord {
    return {
        code: input.code || null,
        discount_type: input.discountType,
        discount_value: Number(input.discountValue || 0),
        is_active: input.isActive,
        is_exclusive: input.isExclusive,
        is_stackable: input.isStackable,
        name: input.name,
        priority: Number(input.priority || 0),
        valid_from: input.validFrom || null,
        valid_to: input.validTo || null,
    };
}

function tierPayload(input: PricingTierInput): BackendRecord {
    return {
        adjustment_type: input.adjustmentType || null,
        adjustment_value: input.adjustmentValue ? Number(input.adjustmentValue) : null,
        is_active: input.isActive,
        max_quantity: input.maxQuantity ? Number(input.maxQuantity) : null,
        min_quantity: Number(input.minQuantity || 1),
        price: input.unitPrice ? Number(input.unitPrice) : null,
        price_list_item_id: asNumberOrUndefined(input.priceListItemId) ?? null,
        pricing_rule_id: asNumberOrUndefined(input.pricingRuleId) ?? null,
        sequence: Number(input.sequence || 1),
        uom_id: asNumberOrUndefined(input.uomId) ?? null,
    };
}

async function currencyMap() {
    const response = await pricingApi.listCurrencies();

    return new Map(response.data.map((row) => [row.id, row]));
}

async function itemMap() {
    const response = await pricingApi.listItems();

    return new Map(response.data.map((row) => [row.id, row]));
}

async function uomMap() {
    const response = await pricingApi.listUoms();

    return new Map(response.data.map((row) => [row.id, row]));
}

async function priceListItemMap() {
    const response = await pricingApi.listPriceListItems();

    return new Map(response.data.map((row) => [row.id, row]));
}

export const pricingApi = {
    activatePriceList: async (priceListId: string) => {
        const currencies = await currencyMap();
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/pricing/price-lists/${priceListId}/activate`, { method: 'PATCH' });

        return { ...response, data: normalizePriceList(response.data, currencies) };
    },
    activatePricingRule: async (ruleId: string) => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/pricing/pricing-rules/${ruleId}/activate`, { method: 'PATCH' });

        return { ...response, data: normalizePricingRule(response.data) };
    },
    createDiscount: async (input: DiscountInput) => {
        const response = await httpClient<ApiResponse<BackendRecord>>('/api/pricing/discounts', { body: discountPayload(input), method: 'POST' });

        return { ...response, data: normalizeDiscount(response.data) };
    },
    createPriceList: async (input: PriceListFormInput) => {
        const currencies = await currencyMap();
        const response = await httpClient<ApiResponse<BackendRecord>>('/api/pricing/price-lists', { body: priceListPayload(input), method: 'POST' });

        return { ...response, data: normalizePriceList(response.data, currencies) };
    },
    createPricingRule: async (input: PricingRuleFormInput) => {
        const response = await httpClient<ApiResponse<BackendRecord>>('/api/pricing/pricing-rules', { body: pricingRulePayload(input), method: 'POST' });

        return { ...response, data: normalizePricingRule(response.data) };
    },
    deactivatePriceList: async (priceListId: string) => {
        const currencies = await currencyMap();
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/pricing/price-lists/${priceListId}/deactivate`, { method: 'PATCH' });

        return { ...response, data: normalizePriceList(response.data, currencies) };
    },
    deactivatePricingRule: async (ruleId: string) => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/pricing/pricing-rules/${ruleId}/deactivate`, { method: 'PATCH' });

        return { ...response, data: normalizePricingRule(response.data) };
    },
    deletePriceListItem: (itemId: string) => httpClient<void>(`/api/pricing/price-list-items/${itemId}`, { method: 'DELETE' }),
    deletePricingTier: (tierId: string) => httpClient<void>(`/api/pricing/pricing-tiers/${tierId}`, { method: 'DELETE' }),
    getPriceList: async (priceListId: string) => {
        const currencies = await currencyMap();
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/pricing/price-lists/${priceListId}`);

        return { ...response, data: normalizePriceList(response.data, currencies) };
    },
    getPricingActivity: async (id?: string): Promise<ApiCollectionResponse<PricingAuditEntry>> => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/audit/audit-logs', {
            query: { auditable_id: id, auditable_type: 'pricing', per_page: 50 },
        });
        const data = response.data.map((entry) => ({
            actor: asString(entry.user_name ?? entry.actor_name ?? entry.user_id ?? 'System'),
            description: asString(entry.description ?? entry.event ?? 'Pricing activity'),
            id: asString(entry.id),
            time: asString(entry.occurred_at ?? entry.created_at ?? entry.updated_at),
        }));

        return { ...response, data, meta: collectionMeta({ ...response, data }) };
    },
    getPricingRule: async (ruleId: string) => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/pricing/pricing-rules/${ruleId}`);

        return { ...response, data: normalizePricingRule(response.data) };
    },
    getPricingUsage: async (id: string, type: 'price-list' | 'rule' = 'price-list'): Promise<ApiResponse<PricingUsageSummary>> => {
        const path = type === 'rule' ? `/api/pricing/pricing-rules/${id}/usage` : `/api/pricing/price-lists/${id}/usage`;
        const response = await httpClient<ApiResponse<BackendRecord>>(path);

        return { ...response, data: normalizeUsage(response.data) };
    },
    listCurrencies: async () => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/configuration/currencies', { query: { per_page: LOOKUP_PAGE_SIZE } });
        const data = response.data.map(normalizeLookup);

        return { ...response, data, meta: collectionMeta({ ...response, data }) };
    },
    listCustomerPriceLists: async (): Promise<ApiCollectionResponse<CustomerPriceList>> => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/pricing/customer-price-lists', { query: { per_page: DETAIL_PAGE_SIZE } });
        const data = response.data.map((row) => ({
            customerName: asString(row.customer_name ?? row.customer_id, `Customer #${asString(row.customer_id)}`),
            id: asString(row.id),
            priceListId: asString(row.price_list_id),
            status: normalizeStatus(row),
        }));

        return { ...response, data, meta: collectionMeta({ ...response, data }) };
    },
    listDiscountRules: async (): Promise<ApiCollectionResponse<DiscountRule>> => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/pricing/discount-rules', { query: { per_page: DETAIL_PAGE_SIZE } });
        const data = response.data.map((row) => ({
            discountId: asString(row.discount_id),
            id: asString(row.id),
            scope: asString(row.scope_type ?? row.applies_to_type ?? 'generic'),
            status: normalizeStatus(row),
        }));

        return { ...response, data, meta: collectionMeta({ ...response, data }) };
    },
    listDiscounts: async () => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/pricing/discounts', { query: { per_page: DETAIL_PAGE_SIZE } });
        const data = response.data.map(normalizeDiscount);

        return { ...response, data, meta: collectionMeta({ ...response, data }) };
    },
    listItems: async () => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/item/items', { query: { per_page: LOOKUP_PAGE_SIZE } });
        const data = response.data.map(normalizeLookup);

        return { ...response, data, meta: collectionMeta({ ...response, data }) };
    },
    listPriceHistory: async () => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/pricing/price-histories', { query: { per_page: DETAIL_PAGE_SIZE } });
        const data = response.data.map(normalizeHistory);

        return { ...response, data, meta: collectionMeta({ ...response, data }) };
    },
    listPriceListItems: async (priceListId?: string) => {
        const [items, uoms, response] = await Promise.all([
            itemMap(),
            uomMap(),
            httpClient<ApiCollectionResponse<BackendRecord>>('/api/pricing/price-list-items', { query: { per_page: DETAIL_PAGE_SIZE, price_list_id: priceListId } }),
        ]);
        const data = response.data.map((row) => normalizePriceListItem(row, items, uoms));

        return { ...response, data, meta: collectionMeta({ ...response, data }) };
    },
    listPriceLists: async () => {
        const currencies = await currencyMap();
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/pricing/price-lists', { query: { per_page: DETAIL_PAGE_SIZE } });
        const data = response.data.map((row) => normalizePriceList(row, currencies));

        return { ...response, data, meta: collectionMeta({ ...response, data }) };
    },
    listPricingRuleConditions: async (ruleId?: string) => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/pricing/pricing-rule-conditions', { query: { per_page: DETAIL_PAGE_SIZE, pricing_rule_id: ruleId } });
        const data = response.data.map(normalizeRuleCondition);

        return { ...response, data, meta: collectionMeta({ ...response, data }) };
    },
    listPricingRules: async () => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/pricing/pricing-rules', { query: { per_page: DETAIL_PAGE_SIZE } });
        const data = response.data.map(normalizePricingRule);

        return { ...response, data, meta: collectionMeta({ ...response, data }) };
    },
    listPricingTiers: async (): Promise<ApiCollectionResponse<PricingTier>> => {
        const [items, uoms, response] = await Promise.all([
            priceListItemMap(),
            uomMap(),
            httpClient<ApiCollectionResponse<BackendRecord>>('/api/pricing/pricing-tiers', { query: { per_page: DETAIL_PAGE_SIZE } }),
        ]);
        const data = response.data.map((row) => normalizeTier(row, items, uoms));

        return { ...response, data, meta: collectionMeta({ ...response, data }) };
    },
    listSupplierPriceLists: async (): Promise<ApiCollectionResponse<SupplierPriceList>> => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/pricing/supplier-price-lists', { query: { per_page: DETAIL_PAGE_SIZE } });
        const data = response.data.map((row) => ({
            id: asString(row.id),
            priceListId: asString(row.price_list_id),
            status: normalizeStatus(row),
            supplierName: asString(row.supplier_name ?? row.supplier_id, `Supplier #${asString(row.supplier_id)}`),
        }));

        return { ...response, data, meta: collectionMeta({ ...response, data }) };
    },
    listUoms: async () => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/uom/units-of-measure', { query: { per_page: LOOKUP_PAGE_SIZE } });
        const data = response.data.map(normalizeLookup);

        return { ...response, data, meta: collectionMeta({ ...response, data }) };
    },
    previewDiscount: async (input: DiscountPreviewInput): Promise<ApiPreviewResponse<DiscountPreviewInput, DiscountPreviewCalculated>> => {
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
            breakdown: Array.isArray(response.breakdown) ? response.breakdown as Array<Record<string, unknown>> : [],
            calculated: {
                appliedDiscounts: JSON.stringify(calculated.applied_discounts ?? []),
                discountAmount: asDecimal(calculated.discount_amount),
                netAmount: asDecimal(calculated.net_amount),
            },
            errors: Array.isArray(response.errors) ? response.errors.map(String) : [],
            input,
            warnings: Array.isArray(response.warnings) ? response.warnings.map(String) : [],
        };
    },
    resolvePrice: async (input: PriceResolveRequest): Promise<PriceResolveResult> => {
        const response = await httpClient<BackendRecord>('/api/pricing/resolve-price', {
            body: {
                currency_id: asNumberOrUndefined(input.currencyId) ?? null,
                date: input.date || null,
                item_id: asNumberOrUndefined(input.itemId),
                party_id: asNumberOrUndefined(input.customerId) ?? asNumberOrUndefined(input.supplierId) ?? null,
                party_type: input.customerId ? 'customer' : input.supplierId ? 'supplier' : null,
                price_list_id: asNumberOrUndefined(input.priceListId) ?? null,
                quantity: input.quantity,
                source_type: input.moduleSource,
                uom_id: asNumberOrUndefined(input.uomId) ?? null,
            },
            method: 'POST',
        });
        const calculated = (response.calculated && typeof response.calculated === 'object' ? response.calculated : response) as BackendRecord;

        return {
            appliedDiscount: asString(calculated.discount_amount, '0'),
            appliedRule: asString(response.applied_rule ?? ''),
            breakdown: Array.isArray(response.breakdown)
                ? (response.breakdown as BackendRecord[]).map((row) => ({ label: asString(row.label), value: asString(row.value) }))
                : [],
            errors: Array.isArray(response.errors) ? response.errors.map(String) : [],
            input,
            netUnitPrice: asString(calculated.net_amount, '0'),
            resolvedUnitPrice: asString(calculated.resolved_unit_price, '0'),
            selectedPriceList: asString(calculated.price_list_id, ''),
            tierInfo: asString(calculated.tier_info ?? ''),
            warnings: Array.isArray(response.warnings) ? response.warnings.map(String) : [],
        };
    },
    updateDiscount: async (discountId: string, input: DiscountInput) => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/pricing/discounts/${discountId}`, { body: discountPayload(input), method: 'PUT' });

        return { ...response, data: normalizeDiscount(response.data) };
    },
    updatePriceList: async (priceListId: string, input: PriceListFormInput) => {
        const currencies = await currencyMap();
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/pricing/price-lists/${priceListId}`, { body: priceListPayload(input), method: 'PUT' });

        return { ...response, data: normalizePriceList(response.data, currencies) };
    },
    updatePricingRule: async (ruleId: string, input: PricingRuleFormInput) => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/pricing/pricing-rules/${ruleId}`, { body: pricingRulePayload(input), method: 'PUT' });

        return { ...response, data: normalizePricingRule(response.data) };
    },
    upsertPriceListItem: async (input: PriceListItemInput) => {
        const [items, uoms] = await Promise.all([itemMap(), uomMap()]);
        const response = await httpClient<ApiResponse<BackendRecord>>(input.id ? `/api/pricing/price-list-items/${input.id}` : '/api/pricing/price-list-items', {
            body: priceListItemPayload(input),
            method: input.id ? 'PUT' : 'POST',
        });

        return { ...response, data: normalizePriceListItem(response.data, items, uoms) };
    },
    upsertPricingTier: async (input: PricingTierInput) => {
        const [items, uoms] = await Promise.all([priceListItemMap(), uomMap()]);
        const response = await httpClient<ApiResponse<BackendRecord>>(input.id ? `/api/pricing/pricing-tiers/${input.id}` : '/api/pricing/pricing-tiers', {
            body: tierPayload(input),
            method: input.id ? 'PUT' : 'POST',
        });

        return { ...response, data: normalizeTier(response.data, items, uoms) };
    },
};
