import type { ApiPaginatedEnvelope, ApiResourceEnvelope, PaginatedResult } from '../../types/api';
import { apiClient, unwrapPaginated, unwrapResource } from '../../api/client';
import type {
    Product,
    ProductBrand,
    ProductBrandListFilters,
    ProductBrandPayload,
    ProductCategory,
    ProductCategoryListFilters,
    ProductCategoryPayload,
    ProductIdentifier,
    ProductIdentifierListFilters,
    ProductIdentifierPayload,
    ProductListFilters,
    ProductPayload,
    ProductVariant,
    ProductVariantListFilters,
    ProductVariantPayload,
    UnitOfMeasure,
    UnitOfMeasureListFilters,
    UnitOfMeasurePayload,
    UomConversion,
    UomConversionPayload,
} from './types';

type QueryValue = string | number | boolean | null | undefined;

function toQuery(filters: Record<string, QueryValue>) {
    const query: Record<string, QueryValue> = {};

    for (const [key, value] of Object.entries(filters)) {
        if (value === undefined || value === null || value === '') {
            continue;
        }

        query[key] = value;
    }

    return query;
}

export const productsApi = {
    listProducts(filters: ProductListFilters): Promise<PaginatedResult<Product>> {
        return apiClient.get<ApiPaginatedEnvelope<Product>>('/products', { query: toQuery(filters) }).then((payload) => unwrapPaginated<Product>(payload));
    },
    getProduct(productId: number) {
        return apiClient.get<ApiResourceEnvelope<Product> | Product>(`/products/${productId}`).then((payload) => unwrapResource<Product>(payload));
    },
    createProduct(payload: ProductPayload) {
        return apiClient.post<ApiResourceEnvelope<Product> | Product>('/products', payload).then((result) => unwrapResource<Product>(result));
    },
    updateProduct(productId: number, payload: ProductPayload) {
        return apiClient.put<ApiResourceEnvelope<Product> | Product>(`/products/${productId}`, payload).then((result) => unwrapResource<Product>(result));
    },
    deleteProduct(productId: number) {
        return apiClient.delete<{ message: string }>(`/products/${productId}`);
    },
    listProductBrands(filters: ProductBrandListFilters): Promise<PaginatedResult<ProductBrand>> {
        return apiClient
            .get<ApiPaginatedEnvelope<ProductBrand>>('/product-brands', { query: toQuery(filters) })
            .then((payload) => unwrapPaginated<ProductBrand>(payload));
    },
    getProductBrand(brandId: number) {
        return apiClient
            .get<ApiResourceEnvelope<ProductBrand> | ProductBrand>(`/product-brands/${brandId}`)
            .then((payload) => unwrapResource<ProductBrand>(payload));
    },
    createProductBrand(payload: ProductBrandPayload) {
        return apiClient
            .post<ApiResourceEnvelope<ProductBrand> | ProductBrand>('/product-brands', payload)
            .then((result) => unwrapResource<ProductBrand>(result));
    },
    updateProductBrand(brandId: number, payload: ProductBrandPayload) {
        return apiClient
            .put<ApiResourceEnvelope<ProductBrand> | ProductBrand>(`/product-brands/${brandId}`, payload)
            .then((result) => unwrapResource<ProductBrand>(result));
    },
    deleteProductBrand(brandId: number) {
        return apiClient.delete<{ message: string }>(`/product-brands/${brandId}`);
    },
    listProductCategories(filters: ProductCategoryListFilters): Promise<PaginatedResult<ProductCategory>> {
        return apiClient
            .get<ApiPaginatedEnvelope<ProductCategory>>('/product-categories', { query: toQuery(filters) })
            .then((payload) => unwrapPaginated<ProductCategory>(payload));
    },
    getProductCategory(categoryId: number) {
        return apiClient
            .get<ApiResourceEnvelope<ProductCategory> | ProductCategory>(`/product-categories/${categoryId}`)
            .then((payload) => unwrapResource<ProductCategory>(payload));
    },
    createProductCategory(payload: ProductCategoryPayload) {
        return apiClient
            .post<ApiResourceEnvelope<ProductCategory> | ProductCategory>('/product-categories', payload)
            .then((result) => unwrapResource<ProductCategory>(result));
    },
    updateProductCategory(categoryId: number, payload: ProductCategoryPayload) {
        return apiClient
            .put<ApiResourceEnvelope<ProductCategory> | ProductCategory>(`/product-categories/${categoryId}`, payload)
            .then((result) => unwrapResource<ProductCategory>(result));
    },
    deleteProductCategory(categoryId: number) {
        return apiClient.delete<{ message: string }>(`/product-categories/${categoryId}`);
    },
    listUnitsOfMeasure(filters: UnitOfMeasureListFilters): Promise<PaginatedResult<UnitOfMeasure>> {
        return apiClient
            .get<ApiPaginatedEnvelope<UnitOfMeasure>>('/units-of-measure', { query: toQuery(filters) })
            .then((payload) => unwrapPaginated<UnitOfMeasure>(payload));
    },
    getUnitOfMeasure(unitId: number) {
        return apiClient
            .get<ApiResourceEnvelope<UnitOfMeasure> | UnitOfMeasure>(`/units-of-measure/${unitId}`)
            .then((payload) => unwrapResource<UnitOfMeasure>(payload));
    },
    createUnitOfMeasure(payload: UnitOfMeasurePayload) {
        return apiClient
            .post<ApiResourceEnvelope<UnitOfMeasure> | UnitOfMeasure>('/units-of-measure', payload)
            .then((result) => unwrapResource<UnitOfMeasure>(result));
    },
    updateUnitOfMeasure(unitId: number, payload: UnitOfMeasurePayload) {
        return apiClient
            .put<ApiResourceEnvelope<UnitOfMeasure> | UnitOfMeasure>(`/units-of-measure/${unitId}`, payload)
            .then((result) => unwrapResource<UnitOfMeasure>(result));
    },
    deleteUnitOfMeasure(unitId: number) {
        return apiClient.delete<{ message: string }>(`/units-of-measure/${unitId}`);
    },
    listProductVariants(filters: ProductVariantListFilters): Promise<PaginatedResult<ProductVariant>> {
        return apiClient
            .get<ApiPaginatedEnvelope<ProductVariant>>('/product-variants', { query: toQuery(filters) })
            .then((payload) => unwrapPaginated<ProductVariant>(payload));
    },
    createProductVariant(payload: ProductVariantPayload) {
        return apiClient
            .post<ApiResourceEnvelope<ProductVariant> | ProductVariant>('/product-variants', payload)
            .then((result) => unwrapResource<ProductVariant>(result));
    },
    updateProductVariant(variantId: number, payload: ProductVariantPayload) {
        return apiClient
            .put<ApiResourceEnvelope<ProductVariant> | ProductVariant>(`/product-variants/${variantId}`, payload)
            .then((result) => unwrapResource<ProductVariant>(result));
    },
    deleteProductVariant(variantId: number) {
        return apiClient.delete<{ message: string }>(`/product-variants/${variantId}`);
    },
    listProductIdentifiers(filters: ProductIdentifierListFilters): Promise<PaginatedResult<ProductIdentifier>> {
        return apiClient
            .get<ApiPaginatedEnvelope<ProductIdentifier>>('/product-identifiers', { query: toQuery(filters) })
            .then((payload) => unwrapPaginated<ProductIdentifier>(payload));
    },
    createProductIdentifier(payload: ProductIdentifierPayload) {
        return apiClient
            .post<ApiResourceEnvelope<ProductIdentifier> | ProductIdentifier>('/product-identifiers', payload)
            .then((result) => unwrapResource<ProductIdentifier>(result));
    },
    updateProductIdentifier(identifierId: number, payload: ProductIdentifierPayload) {
        return apiClient
            .put<ApiResourceEnvelope<ProductIdentifier> | ProductIdentifier>(`/product-identifiers/${identifierId}`, payload)
            .then((result) => unwrapResource<ProductIdentifier>(result));
    },
    deleteProductIdentifier(identifierId: number) {
        return apiClient.delete<{ message: string }>(`/product-identifiers/${identifierId}`);
    },
    listUomConversions(filters: { page?: number; per_page?: number; from_uom_id?: number; to_uom_id?: number; sort?: string }): Promise<PaginatedResult<UomConversion>> {
        return apiClient
            .get<ApiPaginatedEnvelope<UomConversion>>('/uom-conversions', { query: toQuery(filters) })
            .then((payload) => unwrapPaginated<UomConversion>(payload));
    },
    createUomConversion(payload: UomConversionPayload) {
        return apiClient
            .post<ApiResourceEnvelope<UomConversion> | UomConversion>('/uom-conversions', payload)
            .then((result) => unwrapResource<UomConversion>(result));
    },
    updateUomConversion(conversionId: number, payload: UomConversionPayload) {
        return apiClient
            .put<ApiResourceEnvelope<UomConversion> | UomConversion>(`/uom-conversions/${conversionId}`, payload)
            .then((result) => unwrapResource<UomConversion>(result));
    },
    deleteUomConversion(conversionId: number) {
        return apiClient.delete<{ message: string }>(`/uom-conversions/${conversionId}`);
    },
};
