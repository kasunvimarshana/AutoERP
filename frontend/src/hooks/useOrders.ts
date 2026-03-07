import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { ordersApi } from '@/api/orders';
import toast from 'react-hot-toast';
import type { FilterParams, OrderFormData } from '@/types';

export const ORDERS_KEY = 'orders';

export const useOrders = (params?: FilterParams) =>
  useQuery({
    queryKey: [ORDERS_KEY, params],
    queryFn: () => ordersApi.list(params),
  });

export const useOrder = (id: number) =>
  useQuery({
    queryKey: [ORDERS_KEY, id],
    queryFn: () => ordersApi.get(id),
    enabled: !!id,
  });

export const useOrderSaga = (orderId: number) =>
  useQuery({
    queryKey: [ORDERS_KEY, orderId, 'saga'],
    queryFn: () => ordersApi.getSaga(orderId),
    enabled: !!orderId,
    refetchInterval: (query) => {
      const data = query.state.data;
      if (!data) return false;
      return ['running', 'compensating', 'pending'].includes(data.status) ? 2000 : false;
    },
  });

export const useCreateOrder = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: OrderFormData) => ordersApi.create(data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: [ORDERS_KEY] });
      toast.success('Order created successfully');
    },
    onError: () => toast.error('Failed to create order'),
  });
};

export const useUpdateOrderStatus = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, status }: { id: number; status: string }) =>
      ordersApi.updateStatus(id, status),
    onSuccess: (updated) => {
      qc.invalidateQueries({ queryKey: [ORDERS_KEY] });
      qc.setQueryData([ORDERS_KEY, updated.id], updated);
      toast.success('Order status updated');
    },
    onError: () => toast.error('Failed to update order status'),
  });
};

export const useCancelOrder = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, reason }: { id: number; reason?: string }) =>
      ordersApi.cancel(id, reason),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: [ORDERS_KEY] });
      toast.success('Order cancelled');
    },
    onError: () => toast.error('Failed to cancel order'),
  });
};
