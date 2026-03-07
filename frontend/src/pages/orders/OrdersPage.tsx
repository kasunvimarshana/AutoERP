import React, { useState } from 'react';
import { Plus, Search, RefreshCw } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import { useOrders, useCreateOrder } from '@/hooks/useOrders';
import DataTable from '@/components/common/DataTable';
import Pagination from '@/components/common/Pagination';
import Modal from '@/components/common/Modal';
import OrderForm from '@/components/orders/OrderForm';
import StatusBadge, { orderStatusVariant, paymentStatusVariant } from '@/components/common/StatusBadge';
import RoleGuard from '@/components/auth/RoleGuard';
import type { Order, TableColumn, OrderFormData } from '@/types';

const OrdersPage: React.FC = () => {
  const navigate = useNavigate();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [sortBy, setSortBy] = useState('created_at');
  const [sortDir, setSortDir] = useState<'asc' | 'desc'>('desc');
  const [createOpen, setCreateOpen] = useState(false);

  const searchTimeout = React.useRef<ReturnType<typeof setTimeout>>();

  const { data, isLoading, refetch } = useOrders({
    page,
    per_page: 15,
    search: debouncedSearch,
    sort_by: sortBy,
    sort_direction: sortDir,
  });

  const createMutation = useCreateOrder();

  const handleSearch = (value: string) => {
    setSearch(value);
    clearTimeout(searchTimeout.current);
    searchTimeout.current = setTimeout(() => {
      setDebouncedSearch(value);
      setPage(1);
    }, 400);
  };

  const handleSort = (key: string) => {
    if (sortBy === key) setSortDir((d) => (d === 'asc' ? 'desc' : 'asc'));
    else { setSortBy(key); setSortDir('asc'); }
  };

  const columns: TableColumn<Order>[] = [
    {
      key: 'order_number',
      label: 'Order #',
      sortable: true,
      className: 'font-mono text-xs text-primary-600',
    },
    { key: 'customer_name', label: 'Customer', sortable: true },
    { key: 'customer_email', label: 'Email', className: 'text-gray-500' },
    {
      key: 'status',
      label: 'Status',
      render: (v) => (
        <StatusBadge label={v as string} variant={orderStatusVariant(v as string)} dot />
      ),
    },
    {
      key: 'payment_status',
      label: 'Payment',
      render: (v) => (
        <StatusBadge label={v as string} variant={paymentStatusVariant(v as string)} />
      ),
    },
    {
      key: 'total',
      label: 'Total',
      sortable: true,
      className: 'text-right font-medium',
      render: (v) => `$${Number(v).toFixed(2)}`,
    },
    {
      key: 'created_at',
      label: 'Date',
      sortable: true,
      render: (v) => new Date(v as string).toLocaleDateString(),
    },
  ];

  return (
    <div className="space-y-5">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Orders</h1>
          <p className="text-sm text-gray-500 mt-0.5">
            {data?.meta.total ?? 0} orders total
          </p>
        </div>
        <RoleGuard permissions={['orders.create']}>
          <button
            onClick={() => setCreateOpen(true)}
            className="flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-xl text-sm font-medium hover:bg-primary-700 transition-colors shadow-sm"
          >
            <Plus size={16} />
            New Order
          </button>
        </RoleGuard>
      </div>

      <div className="flex items-center gap-3">
        <div className="relative flex-1 max-w-sm">
          <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <input
            value={search}
            onChange={(e) => handleSearch(e.target.value)}
            placeholder="Search orders…"
            className="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
          />
        </div>
        <button
          onClick={() => refetch()}
          className="p-2 rounded-xl border border-gray-200 text-gray-500 hover:bg-gray-50 transition-colors"
        >
          <RefreshCw size={16} />
        </button>
      </div>

      <DataTable
        columns={columns}
        data={data?.data ?? []}
        isLoading={isLoading}
        sortBy={sortBy}
        sortDirection={sortDir}
        onSort={handleSort}
        keyExtractor={(row) => row.id}
        emptyMessage="No orders found."
        onRowClick={(row) => navigate(`/orders/${row.id}`)}
      />

      {data?.meta && <Pagination meta={data.meta} onPageChange={setPage} />}

      <Modal
        open={createOpen}
        onClose={() => setCreateOpen(false)}
        title="New Order"
        size="xl"
      >
        <OrderForm
          onSubmit={async (formData: OrderFormData) => {
            await createMutation.mutateAsync(formData);
            setCreateOpen(false);
          }}
          isLoading={createMutation.isPending}
          onCancel={() => setCreateOpen(false)}
        />
      </Modal>
    </div>
  );
};

export default OrdersPage;
