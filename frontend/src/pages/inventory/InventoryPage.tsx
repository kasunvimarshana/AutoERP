import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Search, SlidersHorizontal, History } from 'lucide-react';
import { inventoryApi } from '../../api/inventory';
import { usePagination } from '../../hooks/usePagination';
import { Inventory } from '../../types';
import Card from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Input from '../../components/ui/Input';
import Table, { Column } from '../../components/ui/Table';
import Pagination from '../../components/ui/Pagination';
import Badge from '../../components/ui/Badge';
import AdjustInventoryModal from './AdjustInventoryModal';
import TransactionHistoryModal from './TransactionHistoryModal';

const InventoryPage: React.FC = () => {
  const { page, perPage, setPage } = usePagination(15);
  const [search, setSearch] = useState('');
  const [lowStock, setLowStock] = useState(false);
  const [adjustItem, setAdjustItem] = useState<Inventory | null>(null);
  const [historyItem, setHistoryItem] = useState<Inventory | null>(null);

  const { data, isLoading } = useQuery({
    queryKey: ['inventory', { page, perPage, search, lowStock }],
    queryFn: () => inventoryApi.list({ page, per_page: perPage, search, low_stock: lowStock || undefined }),
  });

  const columns: Column<Inventory>[] = [
    { key: 'product', header: 'Product', render: (row) => <div><p className="font-medium text-gray-900">{row.product?.name || `#${row.product_id}`}</p><p className="text-xs text-gray-500">{row.product?.sku}</p></div> },
    { key: 'warehouse_location', header: 'Location', render: (row) => <span className="text-sm text-gray-600">{row.warehouse_location}</span> },
    { key: 'quantity', header: 'Qty', render: (row) => (
      <div className="flex items-center gap-2">
        <span className="font-semibold text-gray-900">{row.quantity}</span>
        {row.quantity <= row.reorder_level && <Badge variant={row.quantity === 0 ? 'danger' : 'warning'}>{row.quantity === 0 ? 'Out' : 'Low'}</Badge>}
      </div>
    )},
    { key: 'reserved_quantity', header: 'Reserved', render: (row) => <span className="text-sm text-gray-600">{row.reserved_quantity}</span> },
    { key: 'reorder_level', header: 'Reorder At', render: (row) => <span className="text-sm text-gray-600">{row.reorder_level}</span> },
    { key: 'actions', header: 'Actions', className: 'text-right', render: (row) => (
      <div className="flex items-center justify-end gap-2">
        <Button variant="ghost" size="sm" leftIcon={<SlidersHorizontal className="h-3.5 w-3.5" />} onClick={() => setAdjustItem(row)}>Adjust</Button>
        <Button variant="ghost" size="sm" leftIcon={<History className="h-3.5 w-3.5" />} onClick={() => setHistoryItem(row)}>History</Button>
      </div>
    )},
  ];

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div><h1 className="text-2xl font-bold text-gray-900">Inventory</h1><p className="text-sm text-gray-500 mt-1">Track stock levels across your warehouse</p></div>
        <Button variant={lowStock ? 'primary' : 'outline'} onClick={() => { setLowStock(!lowStock); setPage(1); }}>
          {lowStock ? 'Show All' : 'Low Stock Only'}
        </Button>
      </div>
      <Card noPadding>
        <div className="p-4 border-b border-gray-100">
          <Input placeholder="Search by product or location..." leftAddon={<Search className="h-4 w-4" />} value={search} onChange={(e) => { setSearch(e.target.value); setPage(1); }} className="max-w-sm" />
        </div>
        <Table columns={columns} data={data?.data || []} isLoading={isLoading} rowKey={(row) => row.id} emptyMessage="No inventory records found." />
        {data && data.last_page > 1 && <Pagination currentPage={data.current_page} lastPage={data.last_page} total={data.total} perPage={data.per_page} onPageChange={setPage} />}
      </Card>
      {adjustItem && <AdjustInventoryModal isOpen={!!adjustItem} onClose={() => setAdjustItem(null)} inventory={adjustItem} />}
      {historyItem && <TransactionHistoryModal isOpen={!!historyItem} onClose={() => setHistoryItem(null)} inventory={historyItem} />}
    </div>
  );
};
export default InventoryPage;
