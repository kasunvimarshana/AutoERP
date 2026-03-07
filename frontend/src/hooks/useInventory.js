import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'react-toastify';
import * as api from '../api/inventory';

export const useInventory    = (params) =>
  useQuery({ queryKey: ['inventory', params], queryFn: () => api.getInventory(params) });

export const useInventoryItem = (id) =>
  useQuery({ queryKey: ['inventory', id], queryFn: () => api.getInventoryItem(id), enabled: !!id });

export const useTransactions = (id, params) =>
  useQuery({ queryKey: ['transactions', id, params], queryFn: () => api.getTransactions(id, params), enabled: !!id });

export const useLowStock = () =>
  useQuery({ queryKey: ['low-stock'], queryFn: api.getLowStock });

export const useAdjustStock = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }) => api.adjustStock(id, data),
    onSuccess: () => { qc.invalidateQueries(['inventory']); qc.invalidateQueries(['low-stock']); toast.success('Stock adjusted.'); },
  });
};
