import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { useOrders } from '../hooks/useOrders';
import { useAuth } from '../hooks/useAuth';
import Table from '../components/common/Table';
import SearchBar from '../components/common/SearchBar';
import Pagination from '../components/common/Pagination';
import LoadingSpinner from '../components/common/LoadingSpinner';
import StatusBadge from '../components/common/StatusBadge';
import ConfirmDialog from '../components/common/ConfirmDialog';
import { toast } from 'react-toastify';
import { Eye, X } from 'lucide-react';
import { formatCurrency, formatDate } from '../utils/formatters';

const STATUS_TABS = ['all', 'pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];

export default function OrdersPage() {
  const { hasRole, user } = useAuth();
  const isAdmin = hasRole('admin') || hasRole('manager');

  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [cancelTarget, setCancelTarget] = useState(null);

  const { data, isLoading, isError, cancelOrder, isCancelling } = useOrders({
    page,
    search,
    status: statusFilter !== 'all' ? statusFilter : undefined,
    per_page: 15,
  });

  const handleCancel = async () => {
    try {
      await cancelOrder(cancelTarget.id);
      toast.success('Order cancelled');
      setCancelTarget(null);
    } catch {
      toast.error('Failed to cancel order');
    }
  };

  const canCancel = (order) => {
    if (isAdmin) return ['pending', 'confirmed'].includes(order.status);
    return order.customer_id === user?.sub && order.status === 'pending';
  };

  const columns = [
    { header: 'Order #', accessor: 'order_number', cell: (row) => (
      <span className="font-mono font-medium text-gray-900">{row.order_number}</span>
    )},
    { header: 'Customer', accessor: 'customer_name', cell: (row) => (
      <div>
        <div className="font-medium text-gray-900">{row.customer_name}</div>
        <div className="text-xs text-gray-500">{row.customer_email}</div>
      </div>
    )},
    { header: 'Status', accessor: 'status', cell: (row) => <StatusBadge status={row.status} /> },
    { header: 'Items', accessor: 'items_count', cell: (row) => (
      <span className="text-gray-700">{row.items_count ?? (row.order_items?.length ?? '—')}</span>
    )},
    { header: 'Total', accessor: 'total_amount', cell: (row) => (
      <span className="font-medium text-gray-900">{formatCurrency(row.total_amount)}</span>
    )},
    { header: 'Placed At', accessor: 'placed_at', cell: (row) => (
      <span className="text-gray-500 text-sm">{formatDate(row.placed_at || row.created_at)}</span>
    )},
    { header: 'Actions', accessor: 'actions', cell: (row) => (
      <div className="flex items-center gap-2">
        <Link to={`/orders/${row.id}`} className="text-blue-600 hover:text-blue-800">
          <Eye size={16} />
        </Link>
        {canCancel(row) && (
          <button onClick={() => setCancelTarget(row)}
            className="text-red-600 hover:text-red-800" title="Cancel Order">
            <X size={16} />
          </button>
        )}
      </div>
    )},
  ];

  if (isLoading) return <LoadingSpinner />;
  if (isError) return <div className="text-red-500 p-4">Failed to load orders.</div>;

  const orders = data?.data || [];
  const meta = data?.meta || {};

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Orders</h1>
      </div>

      {/* Status Tabs */}
      <div className="flex gap-1 mb-4 bg-gray-100 rounded-lg p-1 w-fit">
        {STATUS_TABS.map((tab) => (
          <button key={tab} onClick={() => { setStatusFilter(tab); setPage(1); }}
            className={`px-3 py-1.5 rounded-md text-sm font-medium capitalize transition-colors ${
              statusFilter === tab ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'
            }`}>
            {tab}
          </button>
        ))}
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div className="p-4 border-b border-gray-100">
          <SearchBar value={search} onChange={(v) => { setSearch(v); setPage(1); }}
            placeholder="Search by order number or customer..." />
        </div>
        <Table columns={columns} data={orders} />
        <div className="p-4 border-t border-gray-100">
          <Pagination currentPage={meta.current_page || 1} totalPages={meta.last_page || 1}
            onPageChange={setPage} />
        </div>
      </div>

      <ConfirmDialog isOpen={!!cancelTarget} onClose={() => setCancelTarget(null)}
        onConfirm={handleCancel}
        title="Cancel Order"
        message={`Are you sure you want to cancel order ${cancelTarget?.order_number}? This action cannot be undone.`}
        confirmLabel={isCancelling ? 'Cancelling...' : 'Cancel Order'}
        variant="danger" />
    </div>
  );
}
