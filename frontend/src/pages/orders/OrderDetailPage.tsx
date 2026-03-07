import React, { useState } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { ArrowLeft, XCircle, RefreshCw, AlertTriangle } from 'lucide-react';
import { useOrder, useOrderSaga, useUpdateOrderStatus, useCancelOrder } from '@/hooks/useOrders';
import SagaStatusTracker from '@/components/orders/SagaStatusTracker';
import StatusBadge, { orderStatusVariant, paymentStatusVariant } from '@/components/common/StatusBadge';
import ConfirmDialog from '@/components/common/ConfirmDialog';
import RoleGuard from '@/components/auth/RoleGuard';
import LoadingSpinner from '@/components/common/LoadingSpinner';

const OrderDetailPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const orderId = Number(id);

  const { data: order, isLoading, isError } = useOrder(orderId);
  const { data: saga, isLoading: sagaLoading } = useOrderSaga(orderId);
  const updateStatus = useUpdateOrderStatus();
  const cancelOrder = useCancelOrder();

  const [cancelOpen, setCancelOpen] = useState(false);

  if (isLoading)
    return (
      <div className="flex justify-center items-center h-64">
        <LoadingSpinner size="lg" />
      </div>
    );

  if (isError || !order)
    return (
      <div className="text-center py-20">
        <AlertTriangle className="mx-auto w-10 h-10 text-red-400 mb-3" />
        <p className="text-gray-600">Order not found.</p>
        <Link to="/orders" className="text-primary-600 text-sm mt-2 hover:underline">
          Back to orders
        </Link>
      </div>
    );

  const infoRow = (label: string, value: React.ReactNode) => (
    <div className="flex justify-between items-center py-2.5 border-b border-gray-50 last:border-0">
      <span className="text-sm text-gray-500">{label}</span>
      <span className="text-sm font-medium text-gray-800">{value}</span>
    </div>
  );

  return (
    <div className="space-y-5 max-w-5xl">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <button
            onClick={() => navigate(-1)}
            className="p-2 rounded-xl hover:bg-gray-100 transition-colors text-gray-500"
          >
            <ArrowLeft size={18} />
          </button>
          <div>
            <h1 className="text-xl font-bold text-gray-900 font-mono">{order.order_number}</h1>
            <p className="text-sm text-gray-400">
              {new Date(order.created_at).toLocaleString()}
            </p>
          </div>
        </div>
        <div className="flex items-center gap-2">
          <StatusBadge label={order.status} variant={orderStatusVariant(order.status)} dot />
          <RoleGuard permissions={['orders.update']}>
            {order.status === 'pending' && (
              <button
                onClick={() => updateStatus.mutate({ id: order.id, status: 'processing' })}
                disabled={updateStatus.isPending}
                className="flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 text-white rounded-xl text-sm hover:bg-blue-700 transition-colors"
              >
                <RefreshCw size={14} />
                Process
              </button>
            )}
            {['pending', 'processing'].includes(order.status) && (
              <button
                onClick={() => setCancelOpen(true)}
                className="flex items-center gap-1.5 px-3 py-1.5 border border-red-200 text-red-600 rounded-xl text-sm hover:bg-red-50 transition-colors"
              >
                <XCircle size={14} />
                Cancel
              </button>
            )}
          </RoleGuard>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
        {/* Order details */}
        <div className="lg:col-span-2 space-y-4">
          {/* Customer info */}
          <div className="bg-white rounded-2xl border border-gray-100 p-5">
            <h2 className="text-sm font-semibold text-gray-600 uppercase tracking-wide mb-3">
              Customer
            </h2>
            {infoRow('Name', order.customer_name)}
            {infoRow('Email', order.customer_email)}
            {order.notes && (
              <div className="mt-3">
                <p className="text-sm text-gray-500">Notes</p>
                <p className="text-sm text-gray-700 mt-1 italic">"{order.notes}"</p>
              </div>
            )}
          </div>

          {/* Order items */}
          <div className="bg-white rounded-2xl border border-gray-100 p-5">
            <h2 className="text-sm font-semibold text-gray-600 uppercase tracking-wide mb-3">
              Items
            </h2>
            <table className="min-w-full">
              <thead>
                <tr className="text-xs text-gray-500 uppercase border-b border-gray-100">
                  <th className="text-left pb-2">Product</th>
                  <th className="text-right pb-2">Qty</th>
                  <th className="text-right pb-2">Price</th>
                  <th className="text-right pb-2">Total</th>
                </tr>
              </thead>
              <tbody>
                {order.items.map((item) => (
                  <tr key={item.id} className="border-b border-gray-50 last:border-0">
                    <td className="py-2.5 text-sm">
                      <p className="font-medium text-gray-800">{item.product_name}</p>
                      <p className="text-xs text-gray-400 font-mono">{item.product_sku}</p>
                    </td>
                    <td className="py-2.5 text-sm text-right text-gray-600">{item.quantity}</td>
                    <td className="py-2.5 text-sm text-right text-gray-600">
                      ${item.unit_price.toFixed(2)}
                    </td>
                    <td className="py-2.5 text-sm text-right font-medium text-gray-800">
                      ${item.total_price.toFixed(2)}
                    </td>
                  </tr>
                ))}
              </tbody>
              <tfoot>
                <tr className="border-t border-gray-100">
                  <td colSpan={3} className="pt-3 text-right text-sm font-semibold text-gray-700 pr-4">
                    Subtotal
                  </td>
                  <td className="pt-3 text-right text-sm font-semibold text-gray-800">
                    ${order.subtotal.toFixed(2)}
                  </td>
                </tr>
                {order.tax > 0 && (
                  <tr>
                    <td colSpan={3} className="pt-1 text-right text-sm text-gray-500 pr-4">Tax</td>
                    <td className="pt-1 text-right text-sm text-gray-600">${order.tax.toFixed(2)}</td>
                  </tr>
                )}
                {order.shipping > 0 && (
                  <tr>
                    <td colSpan={3} className="pt-1 text-right text-sm text-gray-500 pr-4">Shipping</td>
                    <td className="pt-1 text-right text-sm text-gray-600">${order.shipping.toFixed(2)}</td>
                  </tr>
                )}
                <tr>
                  <td colSpan={3} className="pt-2 text-right text-base font-bold text-gray-900 pr-4">
                    Total
                  </td>
                  <td className="pt-2 text-right text-base font-bold text-gray-900">
                    ${order.total.toFixed(2)}
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        {/* Sidebar */}
        <div className="space-y-4">
          {/* Status */}
          <div className="bg-white rounded-2xl border border-gray-100 p-5">
            <h2 className="text-sm font-semibold text-gray-600 uppercase tracking-wide mb-3">
              Status
            </h2>
            {infoRow('Order', (
              <StatusBadge label={order.status} variant={orderStatusVariant(order.status)} dot />
            ))}
            {infoRow('Payment', (
              <StatusBadge label={order.payment_status} variant={paymentStatusVariant(order.payment_status)} />
            ))}
          </div>

          {/* Saga tracker */}
          <div className="bg-white rounded-2xl border border-gray-100 p-5">
            <h2 className="text-sm font-semibold text-gray-600 uppercase tracking-wide mb-4">
              Order Processing
            </h2>
            <SagaStatusTracker saga={saga} isLoading={sagaLoading} />
          </div>
        </div>
      </div>

      <ConfirmDialog
        open={cancelOpen}
        onClose={() => setCancelOpen(false)}
        onConfirm={async () => {
          await cancelOrder.mutateAsync({ id: order.id });
          setCancelOpen(false);
        }}
        title="Cancel Order"
        message="Are you sure you want to cancel this order? This will reverse any stock reservations."
        confirmLabel="Cancel Order"
        isLoading={cancelOrder.isPending}
      />
    </div>
  );
};

export default OrderDetailPage;
