import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Plus, Search, Eye } from 'lucide-react';
import { Link } from 'react-router-dom';
import { ordersApi } from '../../api/orders';
import { usePagination } from '../../hooks/usePagination';
import { Order } from '../../types';
import Card from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Input from '../../components/ui/Input';
import Select from '../../components/ui/Select';
import Table, { Column } from '../../components/ui/Table';
import Pagination from '../../components/ui/Pagination';
import Badge from '../../components/ui/Badge';
import OrderModal from './OrderModal';
import { formatCurrency, formatDate, getOrderStatusVariant, formatStatus } from '../../utils/formatters';

const STATUS_OPTIONS = [
  { value: 'pending', label: 'Pending' }, { value: 'confirmed', label: 'Confirmed' },
  { value: 'processing', label: 'Processing' }, { value: 'shipped', label: 'Shipped' },
  { value: 'delivered', label: 'Delivered' }, { value: 'cancelled', label: 'Cancelled' },
];

const OrdersPage: React.FC = () => {
  const { page, perPage, setPage } = usePagination(15);
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');
  const [isModalOpen, setIsModalOpen] = useState(false);

  const { data, isLoading } = useQuery({
    queryKey: ['orders', { page, perPage, search, status }],
    queryFn: () => ordersApi.list({ page, per_page: perPage, search, status: status || undefined }),
  });

  const columns: Column<Order>[] = [
    { key: 'order_number', header: 'Order #', render: (row) => <span className="font-mono text-sm font-medium text-blue-600">{row.order_number}</span> },
    { key: 'customer_name', header: 'Customer', render: (row) => <div><p className="font-medium text-gray-900">{row.customer_name}</p><p className="text-xs text-gray-500">{row.customer_email}</p></div> },
    { key: 'status', header: 'Status', render: (row) => <Badge variant={getOrderStatusVariant(row.status)}>{formatStatus(row.status)}</Badge> },
    { key: 'total_amount', header: 'Total', render: (row) => <span className="font-semibold">{formatCurrency(row.total_amount)}</span> },
    { key: 'created_at', header: 'Date', render: (row) => <span className="text-sm text-gray-600">{formatDate(row.created_at)}</span> },
    { key: 'actions', header: '', className: 'text-right', render: (row) => (
      <Link to={`/orders/${row.id}`}><Button variant="ghost" size="sm" leftIcon={<Eye className="h-3.5 w-3.5" />}>View</Button></Link>
    )},
  ];

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div><h1 className="text-2xl font-bold text-gray-900">Orders</h1><p className="text-sm text-gray-500 mt-1">Manage customer orders</p></div>
        <Button leftIcon={<Plus className="h-4 w-4" />} onClick={() => setIsModalOpen(true)}>New Order</Button>
      </div>
      <Card noPadding>
        <div className="p-4 border-b border-gray-100 flex gap-3 flex-wrap">
          <Input placeholder="Search orders..." leftAddon={<Search className="h-4 w-4" />} value={search} onChange={(e) => { setSearch(e.target.value); setPage(1); }} className="max-w-xs" />
          <Select options={STATUS_OPTIONS} placeholder="All Statuses" value={status} onChange={(e) => { setStatus(e.target.value); setPage(1); }} className="w-44" />
        </div>
        <Table columns={columns} data={data?.data || []} isLoading={isLoading} rowKey={(row) => row.id} emptyMessage="No orders found." />
        {data && data.last_page > 1 && <Pagination currentPage={data.current_page} lastPage={data.last_page} total={data.total} perPage={data.per_page} onPageChange={setPage} />}
      </Card>
      <OrderModal isOpen={isModalOpen} onClose={() => setIsModalOpen(false)} />
    </div>
  );
};
export default OrdersPage;
