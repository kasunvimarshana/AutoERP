import { apiClient } from './client'
import type {
  Product,
  ProductQueryParams,
  ProductListResponse,
  StockLevel,
  StockMovement,
  StockTransactionRequest,
  StockAdjustmentRequest,
  Warehouse,
  WarehouseQueryParams,
  WarehouseFormData,
  WarehouseStockSummary,
  Category,
  CategoryQueryParams,
  CategoryFormData,
  PaginatedResponse,
} from '@/types/inventory'

export const inventoryApi = {
  // Product endpoints
  async getProducts(params: ProductQueryParams = {}): Promise<PaginatedResponse<Product>> {
    const response = await apiClient.get('/inventory/products', { params })
    return response.data
  },

  async getProduct(id: string): Promise<Product> {
    const response = await apiClient.get(`/inventory/products/${id}`)
    return response.data
  },

  async createProduct(data: Partial<Product>): Promise<Product> {
    const response = await apiClient.post('/inventory/products', data)
    return response.data
  },

  async updateProduct(id: string, data: Partial<Product>): Promise<Product> {
    const response = await apiClient.put(`/inventory/products/${id}`, data)
    return response.data
  },

  async deleteProduct(id: string): Promise<void> {
    await apiClient.delete(`/inventory/products/${id}`)
  },

  async getProductBySKU(sku: string): Promise<Product> {
    const response = await apiClient.get('/inventory/products/sku', { params: { sku } })
    return response.data
  },

  async getLowStockProducts(params: ProductQueryParams = {}): Promise<PaginatedResponse<Product>> {
    const response = await apiClient.get('/inventory/products/low-stock', { params })
    return response.data
  },

  async bulkImportProducts(data: { products: Partial<Product>[] }): Promise<{ success: number; errors: any[] }> {
    const response = await apiClient.post('/inventory/products/bulk-import', data)
    return response.data
  },

  // Stock endpoints
  async getStockLevel(productId: string, params: { warehouse_id?: number } = {}): Promise<StockLevel> {
    const response = await apiClient.get('/inventory/stock/level', { params: { product_id: productId, ...params } })
    return response.data
  },

  async getStockMovements(productId: string, params: { limit?: number; warehouse_id?: number } = {}): Promise<StockMovement[]> {
    const response = await apiClient.get(`/inventory/stock/${productId}/movements`, { params })
    return response.data
  },

  async getTotalStock(productId: string, params: { warehouse_id?: number } = {}): Promise<{ total: number }> {
    const response = await apiClient.get(`/inventory/stock/${productId}/total`, { params })
    return response.data
  },

  async getStockValuation(params: { warehouse_id?: number; cost_method?: string } = {}): Promise<{ value: number }> {
    const response = await apiClient.get('/inventory/stock/valuation', { params })
    return response.data
  },

  async recordTransaction(data: StockTransactionRequest): Promise<void> {
    await apiClient.post('/inventory/stock/transaction', data)
  },

  async adjustStock(data: StockAdjustmentRequest): Promise<void> {
    await apiClient.post('/inventory/stock/adjust', data)
  },

  async reserveStock(data: StockAdjustmentRequest): Promise<void> {
    await apiClient.post('/inventory/stock/reserve', data)
  },

  async allocateStock(data: StockAdjustmentRequest): Promise<void> {
    await apiClient.post('/inventory/stock/allocate', data)
  },

  async releaseStock(data: StockAdjustmentRequest): Promise<void> {
    await apiClient.post('/inventory/stock/release', data)
  },

  // Warehouse endpoints
  async getWarehouses(params: WarehouseQueryParams = {}): Promise<PaginatedResponse<Warehouse>> {
    const response = await apiClient.get('/inventory/warehouses', { params })
    return response.data
  },

  async getWarehouse(id: string): Promise<Warehouse> {
    const response = await apiClient.get(`/inventory/warehouses/${id}`)
    return response.data
  },

  async createWarehouse(data: WarehouseFormData): Promise<Warehouse> {
    const response = await apiClient.post('/inventory/warehouses', data)
    return response.data
  },

  async updateWarehouse(id: string, data: Partial<WarehouseFormData>): Promise<Warehouse> {
    const response = await apiClient.put(`/inventory/warehouses/${id}`, data)
    return response.data
  },

  async deleteWarehouse(id: string): Promise<void> {
    await apiClient.delete(`/inventory/warehouses/${id}`)
  },

  async getWarehouseStockSummary(id: string): Promise<WarehouseStockSummary> {
    const response = await apiClient.get(`/inventory/warehouses/${id}/stock-summary`)
    return response.data
  },

  // Category endpoints
  async getCategories(params: CategoryQueryParams = {}): Promise<PaginatedResponse<Category>> {
    const response = await apiClient.get('/inventory/categories', { params })
    return response.data
  },

  async getCategoryTree(): Promise<Category[]> {
    const response = await apiClient.get('/inventory/categories/tree')
    return response.data
  },

  async getCategory(id: string): Promise<Category> {
    const response = await apiClient.get(`/inventory/categories/${id}`)
    return response.data
  },

  async createCategory(data: CategoryFormData): Promise<Category> {
    const response = await apiClient.post('/inventory/categories', data)
    return response.data
  },

  async updateCategory(id: string, data: Partial<CategoryFormData>): Promise<Category> {
    const response = await apiClient.put(`/inventory/categories/${id}`, data)
    return response.data
  },

  async deleteCategory(id: string): Promise<void> {
    await apiClient.delete(`/inventory/categories/${id}`)
  },

  async getCategoryChildren(id: string): Promise<Category[]> {
    const response = await apiClient.get(`/inventory/categories/${id}/children`)
    return response.data
  },

  async activateCategory(id: string): Promise<void> {
    await apiClient.post(`/inventory/categories/${id}/activate`)
  },

  async deactivateCategory(id: string): Promise<void> {
    await apiClient.post(`/inventory/categories/${id}/deactivate`)
  },
}
