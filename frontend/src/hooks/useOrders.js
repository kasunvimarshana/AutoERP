import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'react-toastify';
import * as api from '../api/orders';

export const useOrders = (params) =>
  useQuery({ queryKey: ['orders', params], queryFn: () => api.getOrders(params) });

export const useOrder = (id) =>
  useQuery({ queryKey: ['orders', id], queryFn: () => api.getOrder(id), enabled: !!id });

export const useCreateOrder = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: api.createOrder,
    onSuccess: () => { qc.invalidateQueries(['orders']); toast.success('Order created.'); },
  });
};

export const useUpdateOrder = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }) => api.updateOrder(id, data),
    onSuccess: () => { qc.invalidateQueries(['orders']); toast.success('Order updated.'); },
  });
};

export const useCancelOrder = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: api.cancelOrder,
    onSuccess: () => { qc.invalidateQueries(['orders']); toast.success('Order cancelled.'); },
  });
};
