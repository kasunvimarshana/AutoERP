import React from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { ArrowLeft } from 'lucide-react';
import { ordersApi } from '../../api/orders';
import Card from '../../components/ui/Card';
import Badge from '../../components/ui/Badge';
import Button from '../../components/ui/Button';
import Select from '../../components/ui/Select';
import Spinner from '../../components/ui/Spinner';
import { formatCurrency, formatDateTime, getOrderStatusVariant, formatStatus } from '../../utils/formatters';
import toast from 'react-hot-toast';

const STATUS_OPTIONS = [
  { value: 'pending', label: 'Pending' }, { value: 'confirmed', label: 'Confirmed' },
  { value: 'processing', label: 'Processing' }, { value: 'shipped', label: 'Shipped' },
  { value: 'delivered', label: 'Delivered' }, { value: 'cancelled', label: 'Cancelled' },
];

const OrderDetailPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [newStatus, setNewStatus] = React.useState('');

  const { data: order, isLoading } = useQuery({
    queryKey: ['orders', id],
    queryFn: () => ordersApi.get(Number(id)),
    enabled: !!id,
  });

  const statusMutation = useMutation({
    mutationFn: (status: string) => ordersApi.updateStatus(Number(id), status),
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['orders'] }); toast.success('Status updated'); setNewStatus(''); },
    onError: (e: any) => toast.error(e.response?.data?.message || 'Update failed'),
  });

  const cancelMutation = useMutation({
    mutationFn: () => ordersApi.cancel(Number(id)),
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['orders'] }); toast.success('Order cancelled'); },
    onError: (e: any) => toast.error(e.response?.data?.message || 'Cancel failed'),
  });

  if (isLoading) return <div className="flex justify-center py-20"><Spinner size="lg" /></div>;
  if (!order) return <div className="text-center py-20 text-gray-500">Order not found</div>;

  return (
    <div className="space-y-6 max-w-4xl mx-auto">
      <div className="flex items-center gap-4">
        <Button variant="ghost" size="sm" leftIcon={<ArrowLeft className="h-4 w-4" />} onClick={() => navigate('/orders')}>Back</Button>
        <div><h1 className="text-2xl font-bold text-gray-900">Order {order.order_number}</h1><p className="text-sm text-gray-500">{formatDateTime(order.created_at)}</p></div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card title="Customer" className="md:col-span-2">
          <p className="text-sm font-semibold text-gray-900">{order.customer_name}</p>
          <p className="text-sm text-gray-500">{order.customer_email}</p>
        </Card>
        <Card title="Status">
          <Badge variant={getOrderStatusVariant(order.status)} className="mb-3">{formatStatus(order.status)}</Badge>
          <div className="flex gap-2 mt-2">
            <Select options={STATUS_OPTIONS} placeholder="Change status" value={newStatus} onChange={(e) => setNewStatus(e.target.value)} className="flex-1" />
            <Button size="sm" disabled={!newStatus} isLoading={statusMutation.isPending} onClick={() => statusMutation.mutate(newStatus)}>Apply</Button>
          </div>
          {order.status !== 'cancelled' && (
            <Button variant="danger" size="sm" className="w-full mt-2" isLoading={cancelMutation.isPending} onClick={() => cancelMutation.mutate()}>Cancel Order</Button>
          )}
        </Card>
      </div>

      <Card title="Order Items" noPadding>
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50"><tr>
            <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Product</th>
            <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Qty</th>
            <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Unit Price</th>
            <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Total</th>
          </tr></thead>
          <tbody className="bg-white divide-y divide-gray-100">
            {order.items?.map((item) => (
              <tr key={item.id} className="hover:bg-gray-50">
                <td className="px-4 py-3 text-sm font-medium text-gray-900">{item.product?.name || `Product #${item.product_id}`}</td>
                <td className="px-4 py-3 text-sm text-gray-600">{item.quantity}</td>
                <td className="px-4 py-3 text-sm text-gray-600">{formatCurrency(item.unit_price)}</td>
                <td className="px-4 py-3 text-sm font-semibold text-gray-900">{formatCurrency(item.total_price)}</td>
              </tr>
            ))}
          </tbody>
          <tfoot className="bg-gray-50"><tr>
            <td colSpan={3} className="px-4 py-3 text-sm font-semibold text-gray-700 text-right">Total</td>
            <td className="px-4 py-3 text-base font-bold text-gray-900">{formatCurrency(order.total_amount)}</td>
          </tr></tfoot>
        </table>
      </Card>
    </div>
  );
};
export default OrderDetailPage;
