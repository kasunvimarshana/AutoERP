import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { useInventory } from '../hooks/useInventory';
import { useAuth } from '../hooks/useAuth';
import Table from '../components/common/Table';
import SearchBar from '../components/common/SearchBar';
import Pagination from '../components/common/Pagination';
import LoadingSpinner from '../components/common/LoadingSpinner';
import StatusBadge from '../components/common/StatusBadge';
import Modal from '../components/common/Modal';
import FormField from '../components/common/FormField';
import { useForm } from 'react-hook-form';
import { yupResolver } from '@hookform/resolvers/yup';
import * as yup from 'yup';
import { toast } from 'react-toastify';
import { AlertTriangle, Eye } from 'lucide-react';

const adjustSchema = yup.object({
  type: yup.string().oneOf(['receipt', 'adjustment', 'sale']).required('Type is required'),
  quantity: yup.number().integer().min(1, 'Quantity must be at least 1').required('Quantity is required'),
  notes: yup.string().nullable(),
});

export default function InventoryPage() {
  const { hasRole } = useAuth();
  const canEdit = hasRole('admin') || hasRole('manager');

  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [lowStockOnly, setLowStockOnly] = useState(false);
  const [adjustItem, setAdjustItem] = useState(null);

  const { data, isLoading, isError, adjustInventory, isAdjusting } = useInventory({
    page,
    search,
    low_stock: lowStockOnly || undefined,
    per_page: 15,
  });

  const { register, handleSubmit, reset, formState: { errors } } = useForm({
    resolver: yupResolver(adjustSchema),
    defaultValues: { type: 'receipt', quantity: 1, notes: '' },
  });

  const onAdjust = async (formData) => {
    try {
      await adjustInventory({ id: adjustItem.id, ...formData });
      toast.success('Stock adjusted successfully');
      setAdjustItem(null);
      reset();
    } catch {
      toast.error('Failed to adjust stock');
    }
  };

  const getStockColor = (item) => {
    if (item.quantity === 0) return 'text-red-600 font-bold';
    if (item.quantity <= item.reorder_level) return 'text-yellow-600 font-semibold';
    return 'text-green-600';
  };

  const columns = [
    { header: 'Product', accessor: 'product_id', cell: (row) => (
      <div>
        <div className="font-medium text-gray-900">{row.product_name || `Product #${row.product_id}`}</div>
        <div className="text-xs text-gray-500">{row.product_sku}</div>
      </div>
    )},
    { header: 'Warehouse', accessor: 'warehouse_location', cell: (row) => (
      <span className="text-gray-700">{row.warehouse_location || '—'}</span>
    )},
    { header: 'Quantity', accessor: 'quantity', cell: (row) => (
      <span className={getStockColor(row)}>{row.quantity}</span>
    )},
    { header: 'Reserved', accessor: 'reserved_quantity', cell: (row) => (
      <span className="text-gray-500">{row.reserved_quantity}</span>
    )},
    { header: 'Available', accessor: 'available', cell: (row) => {
      const avail = (row.quantity || 0) - (row.reserved_quantity || 0);
      return <span className={avail <= 0 ? 'text-red-600 font-bold' : 'text-gray-700'}>{avail}</span>;
    }},
    { header: 'Reorder Level', accessor: 'reorder_level', cell: (row) => (
      <span className="text-gray-500">{row.reorder_level}</span>
    )},
    { header: 'Status', accessor: 'status', cell: (row) => (
      <StatusBadge status={row.status} />
    )},
    { header: 'Actions', accessor: 'actions', cell: (row) => (
      <div className="flex items-center gap-2">
        <Link to={`/inventory/${row.id}`} className="text-blue-600 hover:text-blue-800">
          <Eye size={16} />
        </Link>
        {canEdit && (
          <button onClick={() => { setAdjustItem(row); reset(); }}
            className="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
            Adjust
          </button>
        )}
        {row.quantity <= row.reorder_level && row.quantity > 0 && (
          <AlertTriangle size={16} className="text-yellow-500" title="Low Stock" />
        )}
        {row.quantity === 0 && (
          <AlertTriangle size={16} className="text-red-500" title="Out of Stock" />
        )}
      </div>
    )},
  ];

  if (isLoading) return <LoadingSpinner />;
  if (isError) return <div className="text-red-500 p-4">Failed to load inventory.</div>;

  const items = data?.data || [];
  const meta = data?.meta || {};

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Inventory</h1>
        <div className="flex items-center gap-3">
          <label className="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
            <input type="checkbox" checked={lowStockOnly}
              onChange={(e) => { setLowStockOnly(e.target.checked); setPage(1); }}
              className="rounded border-gray-300 text-indigo-600" />
            Low Stock Only
          </label>
        </div>
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div className="p-4 border-b border-gray-100">
          <SearchBar value={search} onChange={(v) => { setSearch(v); setPage(1); }}
            placeholder="Search by product name or SKU..." />
        </div>
        <Table columns={columns} data={items} />
        <div className="p-4 border-t border-gray-100">
          <Pagination currentPage={meta.current_page || 1} totalPages={meta.last_page || 1}
            onPageChange={setPage} />
        </div>
      </div>

      {/* Adjust Stock Modal */}
      <Modal isOpen={!!adjustItem} onClose={() => { setAdjustItem(null); reset(); }}
        title={`Adjust Stock — ${adjustItem?.product_name || `Item #${adjustItem?.id}`}`} size="md">
        <form onSubmit={handleSubmit(onAdjust)} className="space-y-4">
          <FormField label="Adjustment Type" error={errors.type?.message} required>
            <select {...register('type')}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
              <option value="receipt">Receipt (Add Stock)</option>
              <option value="adjustment">Adjustment (Correction)</option>
              <option value="sale">Sale (Remove Stock)</option>
            </select>
          </FormField>
          <FormField label="Quantity" error={errors.quantity?.message} required>
            <input type="number" min="1" {...register('quantity')}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
          </FormField>
          <FormField label="Notes" error={errors.notes?.message}>
            <textarea {...register('notes')} rows={3}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
              placeholder="Optional notes..." />
          </FormField>
          <div className="flex justify-end gap-3 pt-2">
            <button type="button" onClick={() => { setAdjustItem(null); reset(); }}
              className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
              Cancel
            </button>
            <button type="submit" disabled={isAdjusting}
              className="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50">
              {isAdjusting ? 'Adjusting...' : 'Adjust Stock'}
            </button>
          </div>
        </form>
      </Modal>
    </div>
  );
}
