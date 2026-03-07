export interface Product {
  id: number;
  tenant_id: number;
  sku: string;
  name: string;
  description: string | null;
  category: string | null;
  price: number;
  cost: number | null;
  quantity: number;
  reserved_quantity: number;
  available_quantity: number;
  reorder_point: number;
  reorder_quantity: number;
  unit: string;
  weight: number | null;
  dimensions: ProductDimensions | null;
  images: string[];
  is_active: boolean;
  metadata: Record<string, unknown> | null;
  created_at: string;
  updated_at: string;
}

export interface ProductDimensions {
  length: number;
  width: number;
  height: number;
  unit: string;
}

export interface ProductFormData {
  sku: string;
  name: string;
  description?: string;
  category?: string;
  price: number;
  cost?: number;
  quantity: number;
  reorder_point: number;
  reorder_quantity: number;
  unit: string;
  weight?: number;
  is_active: boolean;
}

export interface StockAdjustment {
  product_id: number;
  quantity: number;
  type: 'add' | 'remove' | 'set';
  reason: string;
}

export type StockStatus = 'in_stock' | 'low_stock' | 'out_of_stock';

export function getStockStatus(product: Product): StockStatus {
  if (product.available_quantity === 0) return 'out_of_stock';
  if (product.available_quantity <= product.reorder_point) return 'low_stock';
  return 'in_stock';
}
