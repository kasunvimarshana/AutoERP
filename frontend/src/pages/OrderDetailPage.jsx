import React from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useOrders } from '../hooks/useOrders';
import { useAuth } from '../hooks/useAuth';
import LoadingSpinner from '../components/common/LoadingSpinner';
import StatusBadge from '../components/common/StatusBadge';
import ConfirmDialog from '../components/common/ConfirmDialog';
import Table from '../components/common/Table';
import { ArrowLeft } from 'lucide-react';
import { formatCurrency, formatDate } from '../utils/formatters';
import { toast } from 'react-toastify';

const STATUS_FLOW = {
  pending: ['confirm', 'cancel'],
  confirmed: ['ship', 'cancel'],
  processing: ['ship'],
  shipped: ['deliver'],
  delivered: [],
  cancelled: [],
};

export default function OrderDetailPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { hasRole } = useAuth();
  const isAdmin = hasRole('admin') || hasRole('manager');

  const { useOrderById, updateOrderStatus, cancelOrder, isCancelling } = useOrders();
  const { data: order, isLoading, isError } = useOrderById(id);
  const [confirmAction, setConfirmAction] = React.useState(null);

  if (isLoading) return <LoadingSpinner />;
  if (isError || !order) return <div className="text-red-500 p-4">Order not found.</div>;

  const actions = isAdmin ? (STATUS_FLOW[order.status] || []) : [];

  const handleAction = async (action) => {
    try {
      if (action === 'cancel') {
        await cancelOrder(order.id);
        toast.success('Order cancelled');
      } else {
        await updateOrderStatus({ id: order.id, action });
        toast.success(`Order ${action}ed successfully`);
      }
      setConfirmAction(null);
    } catch {
      toast.error(`Failed to ${action} order`);
    }
  };

  const itemColumns = [
    { header: 'Product', accessor: 'product_name', cell: (r) => (
      <div><div className="font-medium">{r.product_name}</div>
        <div className="text-xs text-gray-500">{r.product_sku}</div></div>
    )},
    { header: 'Quantity', accessor: 'quantity' },
    { header: 'Unit Price', accessor: 'unit_price', cell: (r) => formatCurrency(r.unit_price) },
    { header: 'Total', accessor: 'total_price', cell: (r) => (
      <span className="font-medium">{formatCurrency(r.total_price)}</span>
    )},
    { header: 'Status', accessor: 'status', cell: (r) => <StatusBadge status={r.status} /> },
  ];

  return (
    <div>
      <button onClick={() => navigate(-1)} className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-4">
        <ArrowLeft size={16} /> Back to Orders
      </button>

      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{order.order_number}</h1>
          <p className="text-gray-500 text-sm">Placed {formatDate(order.placed_at || order.created_at)}</p>
        </div>
        <div className="flex items-center gap-3">
          <StatusBadge status={order.status} />
          {actions.map((action) => (
            <button key={action} onClick={() => setConfirmAction(action)}
              className={`px-4 py-2 rounded-lg text-sm font-medium capitalize ${
                action === 'cancel' ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-indigo-600 text-white hover:bg-indigo-700'
              }`}>
              {action}
            </button>
          ))}
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h2 className="font-semibold text-gray-900 mb-4">Customer</h2>
          <p className="text-gray-700 font-medium">{order.customer_name}</p>
          <p className="text-gray-500 text-sm">{order.customer_email}</p>
        </div>
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h2 className="font-semibold text-gray-900 mb-4">Order Summary</h2>
          <div className="space-y-1 text-sm">
            <div className="flex justify-between"><span className="text-gray-500">Subtotal</span>
              <span>{formatCurrency(order.total_amount)}</span></div>
            <div className="flex justify-between"><span className="text-gray-500">Tax</span>
              <span>{formatCurrency(order.tax_amount)}</span></div>
            <div className="flex justify-between font-semibold text-base pt-1 border-t">
              <span>Total</span><span>{formatCurrency((order.total_amount || 0) + (order.tax_amount || 0))}</span>
            </div>
          </div>
        </div>
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div className="p-4 border-b border-gray-100">
          <h2 className="text-lg font-semibold text-gray-900">Order Items</h2>
        </div>
        <Table columns={itemColumns} data={order.order_items || []} />
      </div>

      <ConfirmDialog isOpen={!!confirmAction} onClose={() => setConfirmAction(null)}
        onConfirm={() => handleAction(confirmAction)}
        title={`${confirmAction?.charAt(0)?.toUpperCase()}${confirmAction?.slice(1)} Order`}
        message={`Are you sure you want to ${confirmAction} this order?`}
        confirmLabel={`Yes, ${confirmAction}`}
        variant={confirmAction === 'cancel' ? 'danger' : 'info'} />
    </div>
  );
}
