import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'react-toastify';
import * as api from '../api/products';

export const useProducts = (params) =>
  useQuery({ queryKey: ['products', params], queryFn: () => api.getProducts(params) });

export const useProduct = (id) =>
  useQuery({ queryKey: ['products', id], queryFn: () => api.getProduct(id), enabled: !!id });

export const useCategories = () =>
  useQuery({ queryKey: ['categories'], queryFn: api.getCategories });

export const useCreateProduct = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: api.createProduct,
    onSuccess: () => { qc.invalidateQueries(['products']); toast.success('Product created.'); },
  });
};

export const useUpdateProduct = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }) => api.updateProduct(id, data),
    onSuccess: () => { qc.invalidateQueries(['products']); toast.success('Product updated.'); },
  });
};

export const useDeleteProduct = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: api.deleteProduct,
    onSuccess: () => { qc.invalidateQueries(['products']); toast.success('Product deleted.'); },
  });
};
