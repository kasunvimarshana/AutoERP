import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { productsApi } from './api';
import type {
    ProductBrandListFilters,
    ProductBrandPayload,
    ProductCategoryListFilters,
    ProductCategoryPayload,
    ProductIdentifierListFilters,
    ProductIdentifierPayload,
    ProductListFilters,
    ProductPayload,
    ProductVariantListFilters,
    ProductVariantPayload,
    UnitOfMeasureListFilters,
    UnitOfMeasurePayload,
    UomConversionPayload,
} from './types';

const productKeys = {
    all: ['products'] as const,
    lists: () => [...productKeys.all, 'list'] as const,
    list: (filters: ProductListFilters) => [...productKeys.lists(), filters] as const,
    details: () => [...productKeys.all, 'detail'] as const,
    detail: (productId: number) => [...productKeys.details(), productId] as const,
    brands: ['product-brands'] as const,
    brandLists: () => [...productKeys.brands, 'list'] as const,
    brandList: (filters: ProductBrandListFilters) => [...productKeys.brandLists(), filters] as const,
    brandDetails: () => [...productKeys.brands, 'detail'] as const,
    brandDetail: (brandId: number) => [...productKeys.brandDetails(), brandId] as const,
    categories: ['product-categories'] as const,
    categoryLists: () => [...productKeys.categories, 'list'] as const,
    categoryList: (filters: ProductCategoryListFilters) => [...productKeys.categoryLists(), filters] as const,
    categoryDetails: () => [...productKeys.categories, 'detail'] as const,
    categoryDetail: (categoryId: number) => [...productKeys.categoryDetails(), categoryId] as const,
    units: ['units-of-measure'] as const,
    unitLists: () => [...productKeys.units, 'list'] as const,
    unitList: (filters: UnitOfMeasureListFilters) => [...productKeys.unitLists(), filters] as const,
    unitDetails: () => [...productKeys.units, 'detail'] as const,
    unitDetail: (unitId: number) => [...productKeys.unitDetails(), unitId] as const,
    variants: ['product-variants'] as const,
    variantList: (filters: ProductVariantListFilters) => [...productKeys.variants, filters] as const,
    identifiers: ['product-identifiers'] as const,
    identifierList: (filters: ProductIdentifierListFilters) => [...productKeys.identifiers, filters] as const,
    conversions: ['uom-conversions'] as const,
    conversionList: (filters: { page?: number; per_page?: number; from_uom_id?: number; to_uom_id?: number; sort?: string }) =>
        [...productKeys.conversions, filters] as const,
};

export function useProducts(filters: ProductListFilters) {
    return useQuery({
        queryKey: productKeys.list(filters),
        queryFn: () => productsApi.listProducts(filters),
    });
}

export function useProduct(productId: number, enabled = true) {
    return useQuery({
        queryKey: productKeys.detail(productId),
        queryFn: () => productsApi.getProduct(productId),
        enabled,
    });
}

export function useCreateProduct() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: ProductPayload) => productsApi.createProduct(payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: productKeys.lists() });
        },
    });
}

export function useUpdateProduct(productId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: ProductPayload) => productsApi.updateProduct(productId, payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: productKeys.detail(productId) });
            void queryClient.invalidateQueries({ queryKey: productKeys.lists() });
        },
    });
}

export function useDeleteProduct() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (productId: number) => productsApi.deleteProduct(productId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: productKeys.lists() });
        },
    });
}

export function useProductBrands(filters: ProductBrandListFilters) {
    return useQuery({
        queryKey: productKeys.brandList(filters),
        queryFn: () => productsApi.listProductBrands(filters),
    });
}

export function useProductBrand(brandId: number, enabled = true) {
    return useQuery({
        queryKey: productKeys.brandDetail(brandId),
        queryFn: () => productsApi.getProductBrand(brandId),
        enabled,
    });
}

export function useCreateProductBrand() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: ProductBrandPayload) => productsApi.createProductBrand(payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: productKeys.brandLists() });
        },
    });
}

export function useUpdateProductBrand(brandId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: ProductBrandPayload) => productsApi.updateProductBrand(brandId, payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: productKeys.brandDetail(brandId) });
            void queryClient.invalidateQueries({ queryKey: productKeys.brandLists() });
        },
    });
}

export function useDeleteProductBrand() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (brandId: number) => productsApi.deleteProductBrand(brandId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: productKeys.brandLists() });
        },
    });
}

export function useProductCategories(filters: ProductCategoryListFilters) {
    return useQuery({
        queryKey: productKeys.categoryList(filters),
        queryFn: () => productsApi.listProductCategories(filters),
    });
}

export function useProductCategory(categoryId: number, enabled = true) {
    return useQuery({
        queryKey: productKeys.categoryDetail(categoryId),
        queryFn: () => productsApi.getProductCategory(categoryId),
        enabled,
    });
}

export function useCreateProductCategory() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: ProductCategoryPayload) => productsApi.createProductCategory(payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: productKeys.categoryLists() });
        },
    });
}

export function useUpdateProductCategory(categoryId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: ProductCategoryPayload) => productsApi.updateProductCategory(categoryId, payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: productKeys.categoryDetail(categoryId) });
            void queryClient.invalidateQueries({ queryKey: productKeys.categoryLists() });
        },
    });
}

export function useDeleteProductCategory() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (categoryId: number) => productsApi.deleteProductCategory(categoryId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: productKeys.categoryLists() });
        },
    });
}

export function useUnitsOfMeasure(filters: UnitOfMeasureListFilters) {
    return useQuery({
        queryKey: productKeys.unitList(filters),
        queryFn: () => productsApi.listUnitsOfMeasure(filters),
    });
}

export function useUnitOfMeasure(unitId: number, enabled = true) {
    return useQuery({
        queryKey: productKeys.unitDetail(unitId),
        queryFn: () => productsApi.getUnitOfMeasure(unitId),
        enabled,
    });
}

export function useCreateUnitOfMeasure() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: UnitOfMeasurePayload) => productsApi.createUnitOfMeasure(payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: productKeys.unitLists() });
        },
    });
}

export function useUpdateUnitOfMeasure(unitId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: UnitOfMeasurePayload) => productsApi.updateUnitOfMeasure(unitId, payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: productKeys.unitDetail(unitId) });
            void queryClient.invalidateQueries({ queryKey: productKeys.unitLists() });
        },
    });
}

export function useDeleteUnitOfMeasure() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (unitId: number) => productsApi.deleteUnitOfMeasure(unitId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: productKeys.unitLists() });
        },
    });
}

export function useProductVariants(filters: ProductVariantListFilters, enabled = true) {
    return useQuery({
        queryKey: productKeys.variantList(filters),
        queryFn: () => productsApi.listProductVariants(filters),
        enabled,
    });
}

export function useCreateProductVariant() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: ProductVariantPayload) => productsApi.createProductVariant(payload),
        onSuccess: (_, payload) => {
            void queryClient.invalidateQueries({ queryKey: productKeys.variants });
            void queryClient.invalidateQueries({ queryKey: productKeys.detail(payload.product_id) });
        },
    });
}

export function useUpdateProductVariant(variantId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: ProductVariantPayload) => productsApi.updateProductVariant(variantId, payload),
        onSuccess: (_, payload) => {
            void queryClient.invalidateQueries({ queryKey: productKeys.variants });
            void queryClient.invalidateQueries({ queryKey: productKeys.detail(payload.product_id) });
        },
    });
}

export function useDeleteProductVariant() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({ variantId }: { productId: number; tenantId: number; variantId: number }) => productsApi.deleteProductVariant(variantId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: productKeys.variants });
        },
    });
}

export function useProductIdentifiers(filters: ProductIdentifierListFilters, enabled = true) {
    return useQuery({
        queryKey: productKeys.identifierList(filters),
        queryFn: () => productsApi.listProductIdentifiers(filters),
        enabled,
    });
}

export function useCreateProductIdentifier() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: ProductIdentifierPayload) => productsApi.createProductIdentifier(payload),
        onSuccess: (_, payload) => {
            void queryClient.invalidateQueries({ queryKey: productKeys.identifiers });
            void queryClient.invalidateQueries({ queryKey: productKeys.detail(payload.product_id) });
        },
    });
}

export function useUpdateProductIdentifier(identifierId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: ProductIdentifierPayload) => productsApi.updateProductIdentifier(identifierId, payload),
        onSuccess: (_, payload) => {
            void queryClient.invalidateQueries({ queryKey: productKeys.identifiers });
            void queryClient.invalidateQueries({ queryKey: productKeys.detail(payload.product_id) });
        },
    });
}

export function useDeleteProductIdentifier() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({ identifierId }: { productId: number; tenantId: number; identifierId: number }) => productsApi.deleteProductIdentifier(identifierId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: productKeys.identifiers });
        },
    });
}

export function useUomConversions(filters: { page?: number; per_page?: number; from_uom_id?: number; to_uom_id?: number; sort?: string }) {
    return useQuery({
        queryKey: productKeys.conversionList(filters),
        queryFn: () => productsApi.listUomConversions(filters),
    });
}

export function useCreateUomConversion() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: UomConversionPayload) => productsApi.createUomConversion(payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: productKeys.conversions });
        },
    });
}

export function useUpdateUomConversion(conversionId: number) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: UomConversionPayload) => productsApi.updateUomConversion(conversionId, payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: productKeys.conversions });
        },
    });
}

export function useDeleteUomConversion() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (conversionId: number) => productsApi.deleteUomConversion(conversionId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: productKeys.conversions });
        },
    });
}
