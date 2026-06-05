import type { ApiCollectionResponse, ApiResponse } from '../../../services/api/apiResponse';
import { httpClient } from '../../../services/api/httpClient';
import type {
    Item,
    ItemAttribute,
    ItemAttributeInput,
    ItemAttributeValue,
    ItemAuditEntry,
    ItemBrand,
    ItemCapabilitySummary,
    ItemCategory,
    ItemCategoryInput,
    ItemComboComponent,
    ItemComboComponentInput,
    ItemFormInput,
    ItemIdentifier,
    ItemIdentifierInput,
    ItemInventorySummary,
    ItemListQuery,
    ItemLookupOption,
    ItemStatus,
    ItemType,
    ItemTypeOption,
    ItemTypeSetupPreview,
    ItemUnit,
    ItemUsageSummary,
    ItemVariant,
    ItemVariantInput,
    StockBehavior,
    UomOption,
} from '../types/item.types';

type BackendRecord = Record<string, unknown>;

type LookupContext = {
    brands?: Map<string, ItemBrand>;
    categories?: Map<string, ItemCategory>;
    items?: Map<string, Item>;
    itemTypes?: Map<string, ItemTypeOption>;
    uoms?: Map<string, UomOption>;
};

const FALLBACK_ITEM_TYPES: ItemTypeOption[] = [
    { code: 'INVENTORY_PRODUCT', isChargeable: false, isRentable: false, isService: false, isStockable: true, label: 'Inventory Product', value: 'inventory_product' },
    { code: 'SERVICE', isChargeable: true, isRentable: false, isService: true, isStockable: false, label: 'Service Item', value: 'service' },
    { code: 'LABOUR', isChargeable: true, isRentable: false, isService: true, isStockable: false, label: 'Labour Item', value: 'labour' },
    { code: 'NON_INVENTORY', isChargeable: true, isRentable: false, isService: false, isStockable: false, label: 'Non-Inventory Item', value: 'non_inventory' },
    { code: 'COMBO', isChargeable: true, isRentable: false, isService: false, isStockable: false, label: 'Combo / Bundle Item', value: 'combo' },
    { code: 'RENTAL_CHARGE', isChargeable: true, isRentable: true, isService: false, isStockable: false, label: 'Rental Charge Item', value: 'rental_charge' },
    { code: 'EXTERNAL_SERVICE', isChargeable: true, isRentable: false, isService: true, isStockable: false, label: 'External Service Item', value: 'external_service' },
    { code: 'CUSTOMER_SUPPLIED', isChargeable: false, isRentable: false, isService: false, isStockable: false, label: 'Customer-Supplied Reference', value: 'customer_supplied' },
    { code: 'FEE_ADJUSTMENT', isChargeable: true, isRentable: false, isService: false, isStockable: false, label: 'Fee / Adjustment Item', value: 'fee_adjustment' },
];

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

function asNumberOrUndefined(value: string) {
    const parsed = Number(value);
    return Number.isFinite(parsed) && parsed > 0 ? parsed : undefined;
}

function asDecimalString(value: unknown, fallback = '0') {
    if (value === null || value === undefined || value === '') {
        return fallback;
    }

    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed.toLocaleString(undefined, { maximumFractionDigits: 4 }) : String(value);
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

function normalizeStatus(raw: BackendRecord): ItemStatus {
    if (raw.is_active === false) {
        return 'inactive';
    }

    const status = asString(raw.status, 'active').toLowerCase();
    if (status === 'draft' || status === 'inactive' || status === 'discontinued') {
        return status;
    }

    return 'active';
}

function statusToBackend(status: ItemStatus) {
    return status.toUpperCase();
}

function normalizeItemType(value: unknown, flags: Partial<Record<'is_rentable' | 'is_service' | 'is_stockable', unknown>> = {}): ItemType {
    const type = asString(value, '').toLowerCase().replaceAll('-', '_').replaceAll(' ', '_');
    const aliases: Record<string, ItemType> = {
        adjustment: 'fee_adjustment',
        bundle: 'combo',
        charge: 'fee_adjustment',
        customer_supplied_reference: 'customer_supplied',
        fee: 'fee_adjustment',
        inventory: 'inventory_product',
        physical: 'inventory_product',
        product: 'inventory_product',
        stock: 'inventory_product',
        stock_item: 'inventory_product',
    };
    const allowed: ItemType[] = ['combo', 'customer_supplied', 'external_service', 'fee_adjustment', 'inventory_product', 'labour', 'non_inventory', 'rental_charge', 'service'];

    if (allowed.includes(type as ItemType)) {
        return type as ItemType;
    }

    if (aliases[type]) {
        return aliases[type];
    }

    if (asBool(flags.is_rentable)) {
        return 'rental_charge';
    }

    if (asBool(flags.is_service)) {
        return 'service';
    }

    if (asBool(flags.is_stockable)) {
        return 'inventory_product';
    }

    return 'non_inventory';
}

function normalizeStockBehavior(raw: BackendRecord, itemType: ItemType): StockBehavior {
    if (itemType === 'customer_supplied') {
        return 'reference_only';
    }

    return asBool(raw.is_stockable, itemType === 'inventory_product') ? 'stock_tracked' : 'no_stock_impact';
}

function normalizeTypeOption(raw: BackendRecord): ItemTypeOption {
    const code = asString(raw.code, '').toUpperCase();
    const value = normalizeItemType(code);

    return {
        code,
        id: asOptionalString(raw.id),
        isChargeable: asBool(raw.is_chargeable),
        isRentable: asBool(raw.is_rentable),
        isService: asBool(raw.is_service),
        isStockable: asBool(raw.is_stockable),
        label: asString(raw.name, FALLBACK_ITEM_TYPES.find((option) => option.value === value)?.label ?? code),
        value,
    };
}

function normalizeCategory(raw: BackendRecord): ItemCategory {
    return {
        code: asString(raw.code),
        description: asOptionalString(raw.description),
        id: asString(raw.id),
        isActive: asBool(raw.is_active, true),
        name: asString(raw.name, 'Uncategorized'),
        status: normalizeStatus(raw),
    };
}

function normalizeBrand(raw: BackendRecord): ItemBrand {
    return {
        code: asOptionalString(raw.code),
        id: asString(raw.id),
        isActive: asBool(raw.is_active, true),
        name: asString(raw.name, 'Unbranded'),
    };
}

function normalizeUom(raw: BackendRecord): UomOption {
    const symbol = asString(raw.symbol);
    const name = asString(raw.name, symbol || 'Unit');

    return {
        id: asString(raw.id),
        isBase: asBool(raw.is_base),
        label: symbol ? `${name} (${symbol})` : name,
        name,
        symbol,
        type: asString(raw.type),
    };
}

function normalizeLookupOption(raw: BackendRecord): ItemLookupOption {
    const code = asOptionalString(raw.code ?? raw.account_code ?? raw.tax_code);
    const name = asString(raw.name ?? raw.account_name ?? raw.tax_name ?? raw.label, code ?? 'Unnamed option');

    return {
        code,
        id: asString(raw.id),
        label: code ? `${code} - ${name}` : name,
        name,
    };
}

function normalizeItem(raw: BackendRecord, lookups: LookupContext = {}): Item {
    const categoryId = asOptionalString(raw.category_id);
    const brandId = asOptionalString(raw.brand_id);
    const baseUomId = asOptionalString(raw.base_uom_id);
    const itemTypeId = asOptionalString(raw.item_type_id);
    const typeFromLookup = itemTypeId ? lookups.itemTypes?.get(itemTypeId)?.value : undefined;
    const itemType = typeFromLookup ?? normalizeItemType(raw.type ?? raw.item_type ?? raw.item_type_code, raw);
    const category = categoryId ? lookups.categories?.get(categoryId)?.name : undefined;
    const brand = brandId ? lookups.brands?.get(brandId)?.name : undefined;
    const baseUom = baseUomId ? lookups.uoms?.get(baseUomId)?.label : undefined;

    return {
        allowChargeUsage: asBool(raw.is_chargeable),
        allowConsumptionUsage: asBool(raw.is_stockable),
        allowIssueUsage: asBool(raw.is_sellable, true),
        allowReceiptUsage: asBool(raw.is_purchasable, true),
        barcode: asOptionalString(raw.barcode),
        baseUom: baseUom ?? asString(raw.base_uom_name ?? raw.base_uom_code ?? raw.uom ?? raw.base_uom_id, 'Backend UOM'),
        baseUomId,
        brand: brand ?? asOptionalString(raw.brand_name ?? raw.brand),
        brandId,
        cogsAccountId: asOptionalString(raw.cogs_account_id),
        category: category ?? asString(raw.category_name ?? raw.category, 'Uncategorized'),
        categoryId,
        code: asString(raw.sku ?? raw.code),
        defaultChargeUomId: asOptionalString(raw.default_charge_uom_id),
        defaultConsumptionUomId: asOptionalString(raw.default_consumption_uom_id),
        defaultIssueUomId: asOptionalString(raw.default_issue_uom_id),
        defaultReceiptUomId: asOptionalString(raw.default_receipt_uom_id),
        description: asOptionalString(raw.description),
        displayName: asString(raw.display_name ?? raw.name, 'Unnamed item'),
        expenseAccountId: asOptionalString(raw.expense_account_id),
        id: asString(raw.id),
        incomeAccountId: asOptionalString(raw.income_account_id),
        inventoryAccountId: asOptionalString(raw.inventory_account_id),
        isBatchTracked: asBool(raw.is_batch_tracked),
        isRentable: asBool(raw.is_rentable, itemType === 'rental_charge'),
        isSerialTracked: asBool(raw.is_serial_tracked),
        isService: asBool(raw.is_service, ['external_service', 'labour', 'service'].includes(itemType)),
        isStockable: asBool(raw.is_stockable, itemType === 'inventory_product'),
        itemType,
        itemTypeId,
        leadTimeDays: asOptionalString(raw.lead_time_days),
        maximumStock: asOptionalString(raw.maximum_stock),
        minimumStock: asOptionalString(raw.minimum_stock),
        name: asString(raw.name, 'Unnamed item'),
        reorderPoint: asOptionalString(raw.reorder_point),
        reorderQuantity: asOptionalString(raw.reorder_quantity),
        safetyStock: asOptionalString(raw.safety_stock),
        standardCost: asOptionalString(raw.standard_cost),
        status: normalizeStatus(raw),
        stockBehavior: normalizeStockBehavior(raw, itemType),
        taxGroupId: asOptionalString(raw.tax_group_id),
        updatedAt: asString(raw.updated_at ?? raw.updatedAt, 'Not updated yet'),
        valuationMethod: asOptionalString(raw.valuation_method),
    };
}

function normalizeCapabilities(raw: BackendRecord): ItemCapabilitySummary {
    return {
        affectsInventory: asBool(raw.affects_inventory),
        batchTracking: asBool(raw.batch_tracking),
        chargeable: asBool(raw.chargeable),
        hasComboComponents: asBool(raw.has_combo_components),
        hasIdentifiers: asBool(raw.has_identifiers),
        hasVariants: asBool(raw.has_variants),
        inventoryReferencesCount: Number(raw.inventory_references_count ?? 0),
        itemType: normalizeItemType(raw.item_type),
        purchasable: asBool(raw.purchasable),
        rentalUsable: asBool(raw.rental_usable),
        sellable: asBool(raw.sellable),
        serialTracking: asBool(raw.serial_tracking),
        serviceUsable: asBool(raw.service_usable),
        stockable: asBool(raw.stockable),
        uomConfigured: asBool(raw.uom_configured),
    };
}

function toBackendItemPayload(input: ItemFormInput) {
    const typeDefaults = FALLBACK_ITEM_TYPES.find((option) => option.value === input.itemType);
    const isService = input.itemType === 'service' || input.itemType === 'labour' || input.itemType === 'external_service';
    const isRentable = input.itemType === 'rental_charge';
    const isChargeable = input.allowChargeUsage || Boolean(typeDefaults?.isChargeable) || isService || isRentable;

    const payload: BackendRecord = {
        barcode: input.barcode || null,
        base_uom_id: asNumberOrUndefined(input.baseUomId),
        brand_id: asNumberOrUndefined(input.brandId) ?? null,
        category_id: asNumberOrUndefined(input.categoryId) ?? null,
        cogs_account_id: asNumberOrUndefined(input.cogsAccountId) ?? null,
        default_charge_uom_id: asNumberOrUndefined(input.defaultChargeUomId) ?? null,
        default_consumption_uom_id: asNumberOrUndefined(input.defaultConsumptionUomId) ?? null,
        default_issue_uom_id: asNumberOrUndefined(input.defaultIssueUomId) ?? null,
        default_receipt_uom_id: asNumberOrUndefined(input.defaultReceiptUomId) ?? null,
        description: input.description || null,
        expense_account_id: asNumberOrUndefined(input.expenseAccountId) ?? null,
        income_account_id: asNumberOrUndefined(input.incomeAccountId) ?? null,
        inventory_account_id: asNumberOrUndefined(input.inventoryAccountId) ?? null,
        is_batch_tracked: input.trackBatch,
        is_chargeable: isChargeable,
        is_purchasable: input.allowReceiptUsage,
        is_rentable: isRentable,
        is_sellable: input.allowIssueUsage,
        is_serial_tracked: input.trackSerial,
        is_service: isService,
        is_stockable: input.stockable,
        item_type_id: asNumberOrUndefined(input.itemTypeId) ?? null,
        lead_time_days: input.leadTimeDays ? Number(input.leadTimeDays) : null,
        maximum_stock: input.maximumStock ? Number(input.maximumStock) : null,
        minimum_stock: input.minimumStock ? Number(input.minimumStock) : 0,
        name: input.name,
        reorder_point: input.reorderPoint ? Number(input.reorderPoint) : 0,
        reorder_quantity: input.reorderQuantity ? Number(input.reorderQuantity) : null,
        safety_stock: input.safetyStock ? Number(input.safetyStock) : 0,
        sku: input.code,
        standard_cost: input.standardCost ? Number(input.standardCost) : null,
        status: statusToBackend(input.status),
        tax_group_id: asNumberOrUndefined(input.taxGroupId) ?? null,
        type: input.itemType,
        valuation_method: input.valuationMethod || null,
    };

    const comboItems = input.comboItems
        .filter((row) => asNumberOrUndefined(row.componentItemId) && asNumberOrUndefined(row.uomId))
        .map((row) => ({
            component_item_id: asNumberOrUndefined(row.componentItemId),
            quantity: Number(row.quantity) > 0 ? Number(row.quantity) : 1,
            uom_id: asNumberOrUndefined(row.uomId),
        }));

    if (input.itemType === 'combo' && comboItems.length > 0) {
        payload.combo_items = comboItems;
    }

    return payload;
}

async function fetchCategories() {
    const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/item/item-categories', { query: { is_active: true, per_page: 200 } });
    const data = response.data.map(normalizeCategory);
    return { ...response, data, meta: collectionMeta({ ...response, data }) };
}

async function fetchBrands() {
    const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/item/item-brands', { query: { is_active: true, per_page: 200 } });
    const data = response.data.map(normalizeBrand);
    return { ...response, data, meta: collectionMeta({ ...response, data }) };
}

async function fetchItemTypes() {
    const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/item/item-types', { query: { is_active: true, per_page: 200 } });
    const data = response.data.map(normalizeTypeOption);
    return { ...response, data, meta: collectionMeta({ ...response, data }) };
}

async function fetchUoms() {
    const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/uom/units-of-measure', { query: { per_page: 200 } });
    const data = response.data.map(normalizeUom);
    return { ...response, data, meta: collectionMeta({ ...response, data }) };
}

async function fetchAccounts() {
    const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/finance/accounts', { query: { is_active: true, per_page: 200 } });
    const data = response.data.map(normalizeLookupOption);
    return { ...response, data, meta: collectionMeta({ ...response, data }) };
}

async function fetchTaxGroups() {
    const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/finance/tax-groups', { query: { is_active: true, per_page: 200 } });
    const data = response.data.map(normalizeLookupOption);
    return { ...response, data, meta: collectionMeta({ ...response, data }) };
}

async function fetchAttributeValues() {
    const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/item/item-attribute-values', { query: { per_page: 200 } });
    const data: ItemAttributeValue[] = response.data.map((record) => ({
        attributeId: asString(record.attribute_id),
        id: asString(record.id),
        value: asString(record.value),
    }));

    return { ...response, data, meta: collectionMeta({ ...response, data }) };
}

async function fetchVariantAttributeValues() {
    const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/item/item-variant-attribute-values', { query: { per_page: 200 } });
    const data = response.data.map((record) => ({
        attributeValueId: asString(record.attribute_value_id),
        id: asString(record.id),
        variantId: asString(record.variant_id),
    }));

    return { ...response, data, meta: collectionMeta({ ...response, data }) };
}

async function lookupContext(): Promise<LookupContext> {
    const [categories, brands, itemTypes, uoms] = await Promise.all([
        fetchCategories(),
        fetchBrands(),
        fetchItemTypes(),
        fetchUoms(),
    ]);

    return {
        brands: new Map(brands.data.map((brand) => [brand.id, brand])),
        categories: new Map(categories.data.map((category) => [category.id, category])),
        itemTypes: new Map(itemTypes.data.flatMap((option) => option.id ? [[option.id, option] as const] : [])),
        uoms: new Map(uoms.data.map((uom) => [uom.id, uom])),
    };
}

function queryForItems(input: ItemListQuery) {
    return {
        is_active: input.status === 'inactive' ? false : undefined,
        is_stockable: input.isStockable,
        page: input.page,
        per_page: input.perPage ?? 25,
        search: input.search,
        status: input.status && input.status !== 'inactive' ? statusToBackend(input.status) : undefined,
        type: input.type,
    };
}

export const itemApi = {
    activateItem: async (itemId: string) => {
        const context = await lookupContext();
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/item/items/${itemId}/activate`, { method: 'PATCH' });
        return { ...response, data: normalizeItem(response.data, context) };
    },
    createItem: async (input: ItemFormInput) => {
        const response = await httpClient<ApiResponse<BackendRecord>>('/api/item/items', {
            body: toBackendItemPayload(input),
            method: 'POST',
        });
        const context = await lookupContext();
        return { ...response, data: normalizeItem(response.data, context) };
    },
    createAttribute: async (input: ItemAttributeInput) => {
        const response = await httpClient<ApiResponse<BackendRecord>>('/api/item/item-attributes', {
            body: {
                group_id: asNumberOrUndefined(input.groupId ?? '') ?? null,
                is_required: input.isRequired ?? false,
                name: input.name,
                type: input.type,
            },
            method: 'POST',
        });
        return {
            ...response,
            data: {
                group: asString(response.data.group_name ?? response.data.group_id),
                id: asString(response.data.id),
                name: asString(response.data.name),
                type: asString(response.data.type, 'text'),
            },
        };
    },
    createCategory: async (input: ItemCategoryInput) => {
        const response = await httpClient<ApiResponse<BackendRecord>>('/api/item/item-categories', {
            body: {
                code: input.code || null,
                description: input.description || null,
                is_active: input.isActive ?? true,
                name: input.name,
            },
            method: 'POST',
        });
        return { ...response, data: normalizeCategory(response.data) };
    },
    createComboComponent: async (input: ItemComboComponentInput) => {
        const response = await httpClient<ApiResponse<BackendRecord>>('/api/item/combo-items', {
            body: {
                combo_item_id: asNumberOrUndefined(input.comboItemId),
                component_item_id: asNumberOrUndefined(input.componentItemId),
                quantity: Number(input.quantity) > 0 ? Number(input.quantity) : 1,
                uom_id: asNumberOrUndefined(input.uomId),
            },
            method: 'POST',
        });
        return response;
    },
    createIdentifier: async (input: ItemIdentifierInput) => {
        const response = await httpClient<ApiResponse<BackendRecord>>('/api/item/item-identifiers', {
            body: {
                format: input.format || null,
                is_active: input.isActive ?? true,
                is_primary: input.isPrimary ?? false,
                item_id: asNumberOrUndefined(input.itemId),
                technology: input.technology || 'barcode_1d',
                value: input.value,
                variant_id: asNumberOrUndefined(input.variantId ?? '') ?? null,
            },
            method: 'POST',
        });
        return response;
    },
    createVariant: async (input: ItemVariantInput) => {
        const response = await httpClient<ApiResponse<BackendRecord>>('/api/item/item-variants', {
            body: {
                is_active: input.isActive ?? true,
                is_default: input.isDefault ?? false,
                item_id: asNumberOrUndefined(input.itemId),
                name: input.name,
                sku: input.sku || null,
            },
            method: 'POST',
        });
        return response;
    },
    deactivateItem: async (itemId: string) => {
        const context = await lookupContext();
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/item/items/${itemId}/deactivate`, { method: 'PATCH' });
        return { ...response, data: normalizeItem(response.data, context) };
    },
    deleteAttribute: (id: string) => httpClient<void>(`/api/item/item-attributes/${id}`, { method: 'DELETE' }),
    deleteCategory: (id: string) => httpClient<void>(`/api/item/item-categories/${id}`, { method: 'DELETE' }),
    deleteComboComponent: (id: string) => httpClient<void>(`/api/item/combo-items/${id}`, { method: 'DELETE' }),
    deleteIdentifier: (id: string) => httpClient<void>(`/api/item/item-identifiers/${id}`, { method: 'DELETE' }),
    deleteVariant: (id: string) => httpClient<void>(`/api/item/item-variants/${id}`, { method: 'DELETE' }),
    getInventorySummary: async (itemId: string): Promise<ApiResponse<ItemInventorySummary>> => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/item/items/${itemId}/inventory-summary`);

        return {
            ...response,
            data: {
                availableQuantity: asDecimalString(response.data.quantity_available),
                cogsAccountId: asOptionalString(response.data.cogs_account_id),
                inventoryAccountId: asOptionalString(response.data.inventory_account_id),
                isStockable: asBool(response.data.is_stockable),
                minimumStock: asDecimalString(response.data.minimum_stock),
                quantityOnHand: asDecimalString(response.data.quantity_on_hand),
                quantityReserved: asDecimalString(response.data.quantity_reserved),
                reorderPoint: asDecimalString(response.data.reorder_point),
                reorderQuantity: asOptionalString(response.data.reorder_quantity),
                safetyStock: asDecimalString(response.data.safety_stock),
                standardCost: asOptionalString(response.data.standard_cost),
                stockLevelCount: Number(response.data.stock_level_count ?? 0),
                valuationMethod: asOptionalString(response.data.valuation_method),
            },
        };
    },
    getItem: async (itemId: string) => {
        const [response, context] = await Promise.all([
            httpClient<ApiResponse<BackendRecord>>(`/api/item/items/${itemId}`),
            lookupContext(),
        ]);
        return { ...response, data: normalizeItem(response.data, context) };
    },
    getItemActivity: async (itemId: string): Promise<ApiCollectionResponse<ItemAuditEntry>> => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/audit/audit-logs', {
            query: { auditable_id: itemId, auditable_type: 'items', per_page: 50 },
        });
        const data = response.data.map((entry) => ({
            actor: asString(entry.user_name ?? entry.user_id ?? 'System'),
            description: asString(entry.description ?? entry.event ?? 'Item activity'),
            id: asString(entry.id),
            time: asString(entry.occurred_at ?? entry.created_at ?? entry.updated_at),
        }));

        return { ...response, data, meta: collectionMeta({ ...response, data }) };
    },
    getItemUnits: async (itemId: string): Promise<ApiCollectionResponse<ItemUnit>> => {
        const [setup, item, uoms] = await Promise.all([
            httpClient<ApiResponse<BackendRecord>>(`/api/item/items/${itemId}/uom-setup`),
            itemApi.getItem(itemId),
            itemApi.listUoms(),
        ]);
        const uomMap = new Map(uoms.data.map((uom) => [uom.id, uom]));
        const baseUomId = asOptionalString(setup.data.base_uom_id) ?? item.data.baseUomId;
        const units: ItemUnit[] = [
            { id: baseUomId ?? 'base-uom', isBase: true, purpose: 'base', unit: baseUomId ? uomMap.get(baseUomId)?.label ?? `UOM #${baseUomId}` : item.data.baseUom },
            { id: asString(setup.data.default_receipt_uom_id), isBase: false, purpose: 'receipt', unit: setup.data.default_receipt_uom_id ? uomMap.get(asString(setup.data.default_receipt_uom_id))?.label ?? `UOM #${asString(setup.data.default_receipt_uom_id)}` : 'Not configured' },
            { id: asString(setup.data.default_issue_uom_id), isBase: false, purpose: 'issue', unit: setup.data.default_issue_uom_id ? uomMap.get(asString(setup.data.default_issue_uom_id))?.label ?? `UOM #${asString(setup.data.default_issue_uom_id)}` : 'Not configured' },
            { id: asString(setup.data.default_consumption_uom_id), isBase: false, purpose: 'consumption', unit: setup.data.default_consumption_uom_id ? uomMap.get(asString(setup.data.default_consumption_uom_id))?.label ?? `UOM #${asString(setup.data.default_consumption_uom_id)}` : 'Not configured' },
            { id: asString(setup.data.default_charge_uom_id), isBase: false, purpose: 'charge', unit: setup.data.default_charge_uom_id ? uomMap.get(asString(setup.data.default_charge_uom_id))?.label ?? `UOM #${asString(setup.data.default_charge_uom_id)}` : 'Not configured' },
        ];

        return {
            data: units.map((unit, index) => ({ ...unit, id: unit.id || `${itemId}-uom-${index}` })),
        };
    },
    getItemUsage: async (itemId: string): Promise<ApiResponse<ItemUsageSummary>> => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/item/items/${itemId}/usage-summary`);
        return { ...response, data: { capabilities: normalizeCapabilities(response.data) } };
    },
    getTypeSetupPreview: async (input: Partial<ItemFormInput>): Promise<ApiResponse<ItemTypeSetupPreview>> => {
        const response = await httpClient<ApiResponse<BackendRecord>>('/api/item/items/preview-type-setup', {
            body: toBackendItemPayload({
                allowChargeUsage: input.allowChargeUsage ?? false,
                allowConsumptionUsage: input.allowConsumptionUsage ?? false,
                allowIssueUsage: input.allowIssueUsage ?? false,
                allowReceiptUsage: input.allowReceiptUsage ?? false,
                barcode: input.barcode ?? '',
                baseUomId: input.baseUomId ?? '',
                brandId: input.brandId ?? '',
                categoryId: input.categoryId ?? '',
                cogsAccountId: input.cogsAccountId ?? '',
                code: input.code ?? 'PREVIEW',
                comboItems: input.comboItems ?? [],
                defaultChargeUomId: input.defaultChargeUomId ?? '',
                defaultConsumptionUomId: input.defaultConsumptionUomId ?? '',
                defaultIssueUomId: input.defaultIssueUomId ?? '',
                defaultReceiptUomId: input.defaultReceiptUomId ?? '',
                description: input.description ?? '',
                displayName: input.displayName ?? '',
                expenseAccountId: input.expenseAccountId ?? '',
                incomeAccountId: input.incomeAccountId ?? '',
                inventoryAccountId: input.inventoryAccountId ?? '',
                itemType: input.itemType ?? 'inventory_product',
                itemTypeId: input.itemTypeId ?? '',
                leadTimeDays: input.leadTimeDays ?? '',
                maximumStock: input.maximumStock ?? '',
                minimumStock: input.minimumStock ?? '',
                name: input.name ?? 'Preview',
                reorderPoint: input.reorderPoint ?? '',
                reorderQuantity: input.reorderQuantity ?? '',
                safetyStock: input.safetyStock ?? '',
                standardCost: input.standardCost ?? '',
                status: input.status ?? 'draft',
                stockable: input.stockable ?? false,
                taxGroupId: input.taxGroupId ?? '',
                trackBatch: input.trackBatch ?? false,
                trackSerial: input.trackSerial ?? false,
                valuationMethod: input.valuationMethod ?? '',
            }),
            method: 'POST',
        });
        return {
            ...response,
            data: {
                capabilities: normalizeCapabilities((response.data.capabilities ?? {}) as BackendRecord),
                warnings: Array.isArray(response.data.warnings) ? response.data.warnings.map(String) : [],
            },
        };
    },
    listAttributes: async () => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/item/item-attributes', { query: { per_page: 200 } });
        const data = response.data.map((item) => ({
            group: asString(item.group_name ?? item.group_id),
            id: asString(item.id),
            name: asString(item.name),
            type: asString(item.type, 'text'),
        }));
        return { ...response, data, meta: collectionMeta({ ...response, data }) };
    },
    listBrands: fetchBrands,
    listCategories: fetchCategories,
    listComboComponents: async (itemId?: string) => {
        const [response, itemsResponse, uomResponse] = await Promise.all([
            httpClient<ApiCollectionResponse<BackendRecord>>('/api/item/combo-items', {
                query: { combo_item_id: itemId, per_page: 200 },
            }),
            itemApi.listItems({ perPage: 200 }),
            itemApi.listUoms(),
        ]);
        const itemMap = new Map(itemsResponse.data.map((item) => [item.id, item]));
        const uomMap = new Map(uomResponse.data.map((uom) => [uom.id, uom]));
        const data = response.data.map((component) => ({
            comboItemId: asOptionalString(component.combo_item_id),
            componentItemId: asOptionalString(component.component_item_id),
            componentItemName: itemMap.get(asString(component.component_item_id))?.name ?? `Item #${asString(component.component_item_id)}`,
            componentType: itemMap.get(asString(component.component_item_id))?.itemType ?? 'inventory_product' as ItemType,
            id: asString(component.id),
            quantity: asString(component.quantity, '1'),
            stockImpact: itemMap.get(asString(component.component_item_id))?.isStockable ? 'Component can affect stock through backend expansion' : 'No stock impact in item setup',
            uom: uomMap.get(asString(component.uom_id))?.label ?? asString(component.uom_id, 'UOM'),
            uomId: asOptionalString(component.uom_id),
        }));
        return { ...response, data, meta: collectionMeta({ ...response, data }) };
    },
    listIdentifiers: async (itemId?: string) => {
        const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/item/item-identifiers', {
            query: { item_id: itemId, per_page: 200 },
        });
        const data = response.data.map((item) => {
            const technology = asString(item.technology, 'barcode_1d').toLowerCase();
            const type: ItemIdentifier['type'] = technology.includes('qr')
                ? 'qr'
                : technology.includes('rfid')
                    ? 'rfid'
                    : technology.includes('manufacturer')
                        ? 'manufacturer'
                        : technology.includes('internal')
                            ? 'internal'
                            : 'barcode';

            return {
                id: asString(item.id),
                itemId: asOptionalString(item.item_id),
                type,
                value: asString(item.value),
            };
        });
        return { ...response, data, meta: collectionMeta({ ...response, data }) };
    },
    listItems: async (query: ItemListQuery = {}) => {
        const [response, context] = await Promise.all([
            httpClient<ApiCollectionResponse<BackendRecord>>('/api/item/items', { query: queryForItems(query) }),
            lookupContext(),
        ]);
        const data = response.data.map((item) => normalizeItem(item, context));
        return { ...response, data, meta: collectionMeta({ ...response, data }) };
    },
    listFinanceAccounts: fetchAccounts,
    listItemTypes: fetchItemTypes,
    listTaxGroups: fetchTaxGroups,
    listUoms: fetchUoms,
    listVariants: async (itemId?: string) => {
        const [response, attributes, attributeValues, variantAttributeValues] = await Promise.all([
            httpClient<ApiCollectionResponse<BackendRecord>>('/api/item/item-variants', {
                query: { item_id: itemId, per_page: 200 },
            }),
            itemApi.listAttributes(),
            fetchAttributeValues(),
            fetchVariantAttributeValues(),
        ]);
        const attributeMap = new Map(attributes.data.map((attribute) => [attribute.id, attribute]));
        const attributeValueMap = new Map(attributeValues.data.map((value) => [value.id, value]));
        const data = response.data.map((item) => ({
            attributes: variantAttributeValues.data
                .filter((link) => link.variantId === asString(item.id))
                .map((link) => {
                    const value = attributeValueMap.get(link.attributeValueId);
                    const attribute = value ? attributeMap.get(value.attributeId) : undefined;

                    return {
                        attribute: attribute?.name ?? `Attribute #${value?.attributeId ?? ''}`,
                        value: value?.value ?? `Value #${link.attributeValueId}`,
                    };
                }),
            id: asString(item.id),
            isActive: asBool(item.is_active, true),
            itemId: asOptionalString(item.item_id),
            name: asString(item.name),
            sku: asString(item.sku),
        }));
        return { ...response, data, meta: collectionMeta({ ...response, data }) };
    },
    updateItem: async (itemId: string, input: ItemFormInput) => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/item/items/${itemId}`, {
            body: toBackendItemPayload(input),
            method: 'PUT',
        });
        const context = await lookupContext();
        return { ...response, data: normalizeItem(response.data, context) };
    },
};
