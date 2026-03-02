import httpClient from '@/api/client'
import type { ApiResponse, ListParams, PaginatedResponse } from '@/types/api'

export interface Product {
  id: number
  tenant_id: number
  name: string
  sku: string
  type: 'physical' | 'consumable' | 'service' | 'digital' | 'bundle' | 'composite' | 'variant'
  uom: string
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface Uom {
  id: number
  tenant_id: number
  name: string
  code: string
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface UomConversion {
  id: number
  product_id: number
  from_uom: string
  to_uom: string
  factor: string
  created_at: string
  updated_at: string
}

export interface CreateProductPayload {
  name: string
  sku: string
  type: Product['type']
  uom: string
  is_active?: boolean
}

export type UpdateProductPayload = Partial<CreateProductPayload>

export interface CreateUomPayload {
  name: string
  code: string
  is_active?: boolean
}

export type UpdateUomPayload = Partial<CreateUomPayload>

export interface CreateUomConversionPayload {
  from_uom: string
  to_uom: string
  factor: string
}

const productApi = {
  // Products
  listProducts: (params?: ListParams) =>
    httpClient.get<PaginatedResponse<Product>>('/products', { params }),

  getProduct: (id: number) =>
    httpClient.get<ApiResponse<Product>>(`/products/${id}`),

  createProduct: (payload: CreateProductPayload) =>
    httpClient.post<ApiResponse<Product>>('/products', payload),

  updateProduct: (id: number, payload: UpdateProductPayload) =>
    httpClient.put<ApiResponse<Product>>(`/products/${id}`, payload),

  deleteProduct: (id: number) =>
    httpClient.delete<ApiResponse<null>>(`/products/${id}`),

  // Units of Measure
  listUoms: (params?: ListParams) =>
    httpClient.get<PaginatedResponse<Uom>>('/uoms', { params }),

  getUom: (id: number) =>
    httpClient.get<ApiResponse<Uom>>(`/uoms/${id}`),

  createUom: (payload: CreateUomPayload) =>
    httpClient.post<ApiResponse<Uom>>('/uoms', payload),

  updateUom: (id: number, payload: UpdateUomPayload) =>
    httpClient.put<ApiResponse<Uom>>(`/uoms/${id}`, payload),

  deleteUom: (id: number) =>
    httpClient.delete<ApiResponse<null>>(`/uoms/${id}`),

  // UOM Conversions
  listUomConversions: (productId: number) =>
    httpClient.get<ApiResponse<UomConversion[]>>(`/products/${productId}/uom-conversions`),

  createUomConversion: (productId: number, payload: CreateUomConversionPayload) =>
    httpClient.post<ApiResponse<UomConversion>>(`/products/${productId}/uom-conversions`, payload),

  getUomConversion: (id: number) =>
    httpClient.get<ApiResponse<UomConversion>>(`/uom-conversions/${id}`),

  deleteUomConversion: (id: number) =>
    httpClient.delete<ApiResponse<null>>(`/uom-conversions/${id}`),
}

export default productApi
