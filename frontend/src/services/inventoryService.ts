import apiClient from './apiClient';

export interface Product {
  id:          string;
  tenant_id:   string;
  name:        string;
  description: string | null;
  sku:         string;
  price:       number;
  currency:    string;
  status:      'active' | 'inactive' | 'discontinued';
  inventory_item?: InventoryItem;
}

export interface InventoryItem {
  id:                  string;
  product_id:          string;
  quantity_available:  number;
  quantity_reserved:   number;
  reorder_threshold:   number;
  warehouse_location:  string | null;
}

export interface PaginatedResponse<T> {
  data:          T[];
  current_page:  number;
  last_page:     number;
  per_page:      number;
  total:         number;
}

export interface CreateProductPayload {
  name:        string;
  sku:         string;
  price:       number;
  description?: string;
  currency?:   string;
  status?:     'active' | 'inactive';
}

/**
 * Inventory and product API calls.
 */
export const inventoryService = {
  // Products
  async listProducts(page = 1): Promise<PaginatedResponse<Product>> {
    const r = await apiClient.get('/inventory/products', { params: { page } });
    return r.data;
  },

  async createProduct(payload: CreateProductPayload): Promise<Product> {
    const r = await apiClient.post('/inventory/products', payload);
    return r.data.data;
  },

  async deleteProduct(id: string): Promise<void> {
    await apiClient.delete(`/inventory/products/${id}`);
  },

  // Inventory
  async listInventory(page = 1): Promise<PaginatedResponse<InventoryItem>> {
    const r = await apiClient.get('/inventory/inventory', { params: { page } });
    return r.data;
  },
};
