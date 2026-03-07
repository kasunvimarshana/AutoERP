import React from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import { useInventory } from '../hooks/useInventory';
import LoadingSpinner from '../components/common/LoadingSpinner';
import StatusBadge from '../components/common/StatusBadge';
import Table from '../components/common/Table';
import { ArrowLeft } from 'lucide-react';
import { formatCurrency, formatDate } from '../utils/formatters';

export default function InventoryDetailPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { useInventoryItem, useInventoryTransactions } = useInventory();
  const { data: item, isLoading, isError } = useInventoryItem(id);
  const { data: txData, isLoading: txLoading } = useInventoryTransactions({ inventory_id: id });

  if (isLoading) return <LoadingSpinner />;
  if (isError || !item) return <div className="text-red-500 p-4">Inventory item not found.</div>;

  const transactions = txData?.data || [];

  const txColumns = [
    { header: 'Type', accessor: 'type', cell: (r) => (
      <span className="capitalize font-medium">{r.type}</span>
    )},
    { header: 'Quantity', accessor: 'quantity', cell: (r) => (
      <span className={r.type === 'sale' ? 'text-red-600' : 'text-green-600'}>
        {r.type === 'sale' ? `-${r.quantity}` : `+${r.quantity}`}
      </span>
    )},
    { header: 'Before', accessor: 'previous_quantity' },
    { header: 'After', accessor: 'new_quantity' },
    { header: 'Reference', accessor: 'reference_id', cell: (r) => (
      <span className="text-gray-500 text-sm">{r.reference_type ? `${r.reference_type}#${r.reference_id}` : '—'}</span>
    )},
    { header: 'Notes', accessor: 'notes', cell: (r) => <span className="text-gray-500 text-sm">{r.notes || '—'}</span> },
    { header: 'Date', accessor: 'created_at', cell: (r) => (
      <span className="text-gray-500 text-sm">{formatDate(r.created_at)}</span>
    )},
  ];

  return (
    <div>
      <button onClick={() => navigate(-1)} className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-4">
        <ArrowLeft size={16} /> Back
      </button>
      <h1 className="text-2xl font-bold text-gray-900 mb-6">Inventory Detail</h1>

      <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
          <div><p className="text-xs text-gray-500 uppercase font-medium">Product ID</p>
            <p className="text-gray-900 font-semibold">{item.product_id}</p></div>
          <div><p className="text-xs text-gray-500 uppercase font-medium">Warehouse</p>
            <p className="text-gray-900">{item.warehouse_location || '—'}</p></div>
          <div><p className="text-xs text-gray-500 uppercase font-medium">Quantity</p>
            <p className="text-2xl font-bold text-gray-900">{item.quantity}</p></div>
          <div><p className="text-xs text-gray-500 uppercase font-medium">Reserved</p>
            <p className="text-2xl font-bold text-gray-500">{item.reserved_quantity}</p></div>
          <div><p className="text-xs text-gray-500 uppercase font-medium">Available</p>
            <p className="text-2xl font-bold text-green-600">{(item.quantity || 0) - (item.reserved_quantity || 0)}</p></div>
          <div><p className="text-xs text-gray-500 uppercase font-medium">Reorder Level</p>
            <p className="text-gray-900">{item.reorder_level}</p></div>
          <div><p className="text-xs text-gray-500 uppercase font-medium">Unit Cost</p>
            <p className="text-gray-900">{formatCurrency(item.unit_cost)}</p></div>
          <div><p className="text-xs text-gray-500 uppercase font-medium">Status</p>
            <StatusBadge status={item.status} /></div>
        </div>
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div className="p-4 border-b border-gray-100">
          <h2 className="text-lg font-semibold text-gray-900">Transaction History</h2>
        </div>
        {txLoading ? <LoadingSpinner /> : <Table columns={txColumns} data={transactions} />}
      </div>
    </div>
  );
}
