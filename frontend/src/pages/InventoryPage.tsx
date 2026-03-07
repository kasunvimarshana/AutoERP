import React, { useState, useEffect } from 'react';
import { inventoryApi } from '../api/inventory';
import type { Inventory } from '../types';
import DataTable from '../components/DataTable';
import Pagination from '../components/Pagination';

const InventoryPage: React.FC = () => {
  const [items, setItems] = useState<Inventory[]>([]);
  const [loading, setLoading] = useState(true);
  const [currentPage, setCurrentPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [productNameFilter, setProductNameFilter] = useState('');
  const [lowStock, setLowStock] = useState(false);
  const [error, setError] = useState('');
  const [adjustId, setAdjustId] = useState<number | null>(null);
  const [adjustDelta, setAdjustDelta] = useState(0);
  const [adjustReason, setAdjustReason] = useState('');

  const fetchInventory = async (page = 1) => {
    setLoading(true);
    try {
      const res = await inventoryApi.list({
        per_page: 10, page,
        product_name: productNameFilter || undefined,
        low_stock: lowStock || undefined,
      });
      const data = res.data.data;
      if (data && 'data' in data) {
        setItems((data as { data: Inventory[] }).data);
        setLastPage((data as { last_page: number }).last_page);
      } else {
        setItems(data as Inventory[]);
      }
    } catch { setError('Failed to load inventory'); }
    finally { setLoading(false); }
  };

  useEffect(() => { fetchInventory(currentPage); }, [currentPage, productNameFilter, lowStock]);

  const handleAdjust = async () => {
    if (adjustId === null) return;
    try {
      await inventoryApi.adjustStock(adjustId, adjustDelta, adjustReason);
      setAdjustId(null);
      setAdjustDelta(0);
      setAdjustReason('');
      fetchInventory(currentPage);
    } catch { setError('Adjust stock failed'); }
  };

  const columns = [
    { key: 'id', label: 'ID' },
    { key: 'product', label: 'Product', render: (_: unknown, row: Inventory) => row.product?.name || row.product_id.toString() },
    { key: 'warehouse_location', label: 'Location' },
    { key: 'quantity', label: 'Qty' },
    { key: 'reserved_quantity', label: 'Reserved' },
    {
      key: 'available', label: 'Available',
      render: (_: unknown, row: Inventory) => {
        const avail = row.quantity - row.reserved_quantity;
        return <span style={{ color: avail <= row.reorder_level ? '#dc2626' : '#16a34a', fontWeight: 600 }}>{avail}</span>;
      },
    },
    { key: 'reorder_level', label: 'Reorder At' },
  ];

  const inputStyle: React.CSSProperties = {
    padding: '0.5rem 0.75rem', border: '1px solid #d1d5db', borderRadius: '0.375rem', fontSize: '0.875rem',
  };

  return (
    <div>
      <h1 style={{ fontSize: '1.5rem', fontWeight: 700, color: '#1e293b', marginBottom: '1.5rem' }}>Inventory</h1>

      {error && <div style={{ background: '#fef2f2', color: '#dc2626', padding: '0.75rem', borderRadius: '0.375rem', marginBottom: '1rem' }}>{error}</div>}

      <div style={{ display: 'flex', gap: '1rem', marginBottom: '1rem', flexWrap: 'wrap', alignItems: 'center' }}>
        <input type="text" placeholder="Filter by product name..." value={productNameFilter}
          onChange={(e) => { setProductNameFilter(e.target.value); setCurrentPage(1); }} style={inputStyle} />
        <label style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', fontSize: '0.875rem', cursor: 'pointer' }}>
          <input type="checkbox" checked={lowStock} onChange={(e) => { setLowStock(e.target.checked); setCurrentPage(1); }} />
          Low stock only
        </label>
      </div>

      <DataTable
        columns={columns as Parameters<typeof DataTable>[0]['columns']}
        data={items as unknown as Record<string, unknown>[]}
        loading={loading}
        onEdit={(row) => { setAdjustId((row as unknown as Inventory).id); setAdjustDelta(0); setAdjustReason(''); }}
      />

      <Pagination currentPage={currentPage} lastPage={lastPage} onPageChange={setCurrentPage} />

      {adjustId !== null && (
        <div style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.5)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 50 }}>
          <div style={{ background: 'white', borderRadius: '0.75rem', padding: '2rem', width: '100%', maxWidth: '380px' }}>
            <h2 style={{ marginBottom: '1rem', fontSize: '1.125rem', fontWeight: 600 }}>Adjust Stock</h2>
            <label style={{ display: 'block', marginBottom: '0.5rem', fontSize: '0.875rem', color: '#374151' }}>Delta (+ to add, - to remove)</label>
            <input type="number" value={adjustDelta} onChange={(e) => setAdjustDelta(parseInt(e.target.value))}
              style={{ ...inputStyle, width: '100%', marginBottom: '0.75rem', boxSizing: 'border-box' }} />
            <input type="text" placeholder="Reason" value={adjustReason} onChange={(e) => setAdjustReason(e.target.value)}
              style={{ ...inputStyle, width: '100%', marginBottom: '1rem', boxSizing: 'border-box' }} />
            <div style={{ display: 'flex', gap: '0.75rem', justifyContent: 'flex-end' }}>
              <button onClick={() => setAdjustId(null)} style={{ padding: '0.5rem 1rem', border: '1px solid #d1d5db', borderRadius: '0.375rem', cursor: 'pointer', background: 'white' }}>Cancel</button>
              <button onClick={handleAdjust} style={{ padding: '0.5rem 1rem', background: '#10b981', color: 'white', border: 'none', borderRadius: '0.375rem', cursor: 'pointer', fontWeight: 500 }}>Apply</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default InventoryPage;
