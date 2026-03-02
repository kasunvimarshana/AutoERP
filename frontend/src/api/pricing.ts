import httpClient from '@/api/client'
import type { ApiResponse, ListParams, PaginatedResponse } from '@/types/api'

export interface PriceList {
  id: number
  tenant_id: number
  name: string
  currency_code: string
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface DiscountRule {
  id: number
  tenant_id: number
  name: string
  discount_type: 'percentage' | 'flat'
  discount_value: string
  min_quantity: string | null
  customer_tier: string | null
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface ProductPrice {
  id: number
  tenant_id: number
  product_id: number
  price_list_id: number
  uom_id: number
  selling_price: string
  cost_price: string | null
  min_quantity: string | null
  valid_from: string | null
  valid_to: string | null
  created_at: string
  updated_at: string
}

export interface PriceCalculationPayload {
  product_id: number
  quantity: string
  price_list_id?: number
  customer_tier?: string
}

export interface PriceCalculationResult {
  unit_price: string
  discount: string
  final_price: string
  currency: string
}

export interface CreatePriceListPayload {
  name: string
  currency_code: string
  is_active?: boolean
}

export type UpdatePriceListPayload = Partial<CreatePriceListPayload>

export interface CreateDiscountRulePayload {
  name: string
  discount_type: 'percentage' | 'flat'
  discount_value: string
  min_quantity?: string
  customer_tier?: string
  is_active?: boolean
}

export type UpdateDiscountRulePayload = Partial<CreateDiscountRulePayload>

export interface CreateProductPricePayload {
  price_list_id: number
  uom_id: number
  selling_price: string
  cost_price?: string
  min_quantity?: string
  valid_from?: string
  valid_to?: string
}

const pricingApi = {
  // Price Lists
  listPriceLists: (params?: ListParams) =>
    httpClient.get<PaginatedResponse<PriceList>>('/pricing/lists', { params }),

  getPriceList: (id: number) =>
    httpClient.get<ApiResponse<PriceList>>(`/pricing/lists/${id}`),

  createPriceList: (payload: CreatePriceListPayload) =>
    httpClient.post<ApiResponse<PriceList>>('/pricing/lists', payload),

  updatePriceList: (id: number, payload: UpdatePriceListPayload) =>
    httpClient.put<ApiResponse<PriceList>>(`/pricing/lists/${id}`, payload),

  deletePriceList: (id: number) =>
    httpClient.delete<ApiResponse<null>>(`/pricing/lists/${id}`),

  // Discount Rules
  listDiscountRules: (params?: ListParams) =>
    httpClient.get<PaginatedResponse<DiscountRule>>('/pricing/discount-rules', { params }),

  getDiscountRule: (id: number) =>
    httpClient.get<ApiResponse<DiscountRule>>(`/pricing/discount-rules/${id}`),

  createDiscountRule: (payload: CreateDiscountRulePayload) =>
    httpClient.post<ApiResponse<DiscountRule>>('/pricing/discount-rules', payload),

  updateDiscountRule: (id: number, payload: UpdateDiscountRulePayload) =>
    httpClient.put<ApiResponse<DiscountRule>>(`/pricing/discount-rules/${id}`, payload),

  deleteDiscountRule: (id: number) =>
    httpClient.delete<ApiResponse<null>>(`/pricing/discount-rules/${id}`),

  // Product Prices
  listProductPrices: (productId: number, params?: ListParams) =>
    httpClient.get<PaginatedResponse<ProductPrice>>(`/products/${productId}/prices`, { params }),

  createProductPrice: (productId: number, payload: CreateProductPricePayload) =>
    httpClient.post<ApiResponse<ProductPrice>>(`/products/${productId}/prices`, payload),

  // Price Calculation
  calculatePrice: (payload: PriceCalculationPayload) =>
    httpClient.post<ApiResponse<PriceCalculationResult>>('/pricing/calculate', payload),
}

export default pricingApi
