import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { productsApi } from '@/api/products';
import toast from 'react-hot-toast';
import type { FilterParams, ProductFormData, StockAdjustment } from '@/types';

export const PRODUCTS_KEY = 'products';

export const useProducts = (params?: FilterParams) =>
  useQuery({
    queryKey: [PRODUCTS_KEY, params],
    queryFn: () => productsApi.list(params),
  });

export const useProduct = (id: number) =>
  useQuery({
    queryKey: [PRODUCTS_KEY, id],
    queryFn: () => productsApi.get(id),
    enabled: !!id,
  });

export const useLowStockProducts = () =>
  useQuery({
    queryKey: [PRODUCTS_KEY, 'low-stock'],
    queryFn: () => productsApi.getLowStock(),
  });

export const useCreateProduct = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: ProductFormData) => productsApi.create(data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: [PRODUCTS_KEY] });
      toast.success('Product created successfully');
    },
    onError: () => toast.error('Failed to create product'),
  });
};

export const useUpdateProduct = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: Partial<ProductFormData> }) =>
      productsApi.update(id, data),
    onSuccess: (updated) => {
      qc.invalidateQueries({ queryKey: [PRODUCTS_KEY] });
      qc.setQueryData([PRODUCTS_KEY, updated.id], updated);
      toast.success('Product updated successfully');
    },
    onError: () => toast.error('Failed to update product'),
  });
};

export const useDeleteProduct = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => productsApi.delete(id),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: [PRODUCTS_KEY] });
      toast.success('Product deleted');
    },
    onError: () => toast.error('Failed to delete product'),
  });
};

export const useAdjustStock = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (adjustment: StockAdjustment) => productsApi.adjustStock(adjustment),
    onSuccess: (updated) => {
      qc.setQueryData([PRODUCTS_KEY, updated.id], updated);
      qc.invalidateQueries({ queryKey: [PRODUCTS_KEY] });
      toast.success('Stock adjusted successfully');
    },
    onError: () => toast.error('Failed to adjust stock'),
  });
};
