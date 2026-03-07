import React, { useState, useEffect } from 'react';
import { ordersApi } from '../api/orders';
import type { CreateOrderPayload } from '../api/orders';
import type { Order } from '../types';
import DataTable from '../components/DataTable';
import Pagination from '../components/Pagination';

const ORDER_STATUSES = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];

const statusColors: Record<string, string> = {
  pending: '#f59e0b',
  confirmed: '#3b82f6',
  processing: '#8b5cf6',
  shipped: '#06b6d4',
  delivered: '#10b981',
  cancelled: '#ef4444',
  refunded: '#6b7280',
};

const OrdersPage: React.FC = () => {
  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [currentPage, setCurrentPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [statusFilter, setStatusFilter] = useState('');
  const [showNewOrder, setShowNewOrder] = useState(false);
  const [newOrderItems, setNewOrderItems] = useState([{ product_id: 0, quantity: 1 }]);
  const [error, setError] = useState('');

  const fetchOrders = async (page = 1) => {
    setLoading(true);
    try {
      const res = await ordersApi.list({ per_page: 10, page, status: statusFilter || undefined });
      const data = res.data.data;
      if (data && 'data' in data) {
        setOrders((data as { data: Order[] }).data);
        setLastPage((data as { last_page: number }).last_page);
      } else {
        setOrders(data as Order[]);
      }
    } catch { setError('Failed to load orders'); }
    finally { setLoading(false); }
  };

  useEffect(() => { fetchOrders(currentPage); }, [currentPage, statusFilter]);

  const handleCreateOrder = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const payload: CreateOrderPayload = {
        items: newOrderItems.filter(i => i.product_id > 0 && i.quantity > 0),
      };
      await ordersApi.create(payload);
      setShowNewOrder(false);
      setNewOrderItems([{ product_id: 0, quantity: 1 }]);
      fetchOrders(currentPage);
    } catch (err: unknown) {
      setError((err as { response?: { data?: { message?: string } } })?.response?.data?.message || 'Failed to create order');
    }
  };

  const handleCancel = async (order: Order) => {
    if (!confirm(`Cancel order ${order.order_number}?`)) return;
    try {
      await ordersApi.cancel(order.id);
      fetchOrders(currentPage);
    } catch { setError('Cancel failed'); }
  };

  const columns = [
    { key: 'id', label: 'ID' },
    { key: 'order_number', label: 'Order #' },
    {
      key: 'status', label: 'Status',
      render: (val: unknown) => (
        <span style={{ padding: '0.125rem 0.5rem', borderRadius: '9999px', fontSize: '0.75rem', fontWeight: 600, background: statusColors[String(val)] + '22', color: statusColors[String(val)] }}>
          {String(val)}
        </span>
      ),
    },
    { key: 'total_amount', label: 'Total', render: (val: unknown) => `$${Number(val).toFixed(2)}` },
    { key: 'user', label: 'Customer', render: (_: unknown, row: Order) => row.user?.name || row.user_id.toString() },
    { key: 'created_at', label: 'Date', render: (val: unknown) => val ? new Date(String(val)).toLocaleDateString() : '' },
  ];

  const inputStyle: React.CSSProperties = {
    padding: '0.5rem 0.75rem', border: '1px solid #d1d5db', borderRadius: '0.375rem', fontSize: '0.875rem',
  };

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.5rem' }}>
        <h1 style={{ fontSize: '1.5rem', fontWeight: 700, color: '#1e293b', margin: 0 }}>Orders</h1>
        <button onClick={() => setShowNewOrder(true)}
          style={{ padding: '0.5rem 1rem', background: '#3b82f6', color: 'white', border: 'none', borderRadius: '0.375rem', cursor: 'pointer', fontWeight: 500 }}>
          + New Order
        </button>
      </div>

      {error && <div style={{ background: '#fef2f2', color: '#dc2626', padding: '0.75rem', borderRadius: '0.375rem', marginBottom: '1rem' }}>{error}</div>}

      <div style={{ marginBottom: '1rem' }}>
        <select value={statusFilter} onChange={(e) => { setStatusFilter(e.target.value); setCurrentPage(1); }} style={inputStyle}>
          <option value="">All statuses</option>
          {ORDER_STATUSES.map(s => <option key={s} value={s}>{s}</option>)}
        </select>
      </div>

      <DataTable
        columns={columns as Parameters<typeof DataTable>[0]['columns']}
        data={orders as unknown as Record<string, unknown>[]}
        loading={loading}
        onDelete={(row) => handleCancel(row as unknown as Order)}
      />

      <Pagination currentPage={currentPage} lastPage={lastPage} onPageChange={setCurrentPage} />

      {showNewOrder && (
        <div style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.5)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 50 }}>
          <div style={{ background: 'white', borderRadius: '0.75rem', padding: '2rem', width: '100%', maxWidth: '500px', maxHeight: '80vh', overflowY: 'auto' }}>
            <h2 style={{ marginBottom: '1.5rem', fontSize: '1.25rem', fontWeight: 600 }}>Create Order (Saga)</h2>
            <form onSubmit={handleCreateOrder}>
              <p style={{ fontSize: '0.875rem', color: '#6b7280', marginBottom: '1rem' }}>
                Orders use the Saga pattern with inventory reservation and compensating transactions.
              </p>
              {newOrderItems.map((item, idx) => (
                <div key={idx} style={{ display: 'flex', gap: '0.5rem', marginBottom: '0.5rem' }}>
                  <input type="number" placeholder="Product ID" value={item.product_id || ''}
                    onChange={(e) => {
                      const items = [...newOrderItems];
                      items[idx].product_id = parseInt(e.target.value) || 0;
                      setNewOrderItems(items);
                    }}
                    style={{ ...inputStyle, flex: 2 }} />
                  <input type="number" min="1" placeholder="Qty" value={item.quantity}
                    onChange={(e) => {
                      const items = [...newOrderItems];
                      items[idx].quantity = parseInt(e.target.value) || 1;
                      setNewOrderItems(items);
                    }}
                    style={{ ...inputStyle, flex: 1 }} />
                  {idx > 0 && (
                    <button type="button" onClick={() => setNewOrderItems(newOrderItems.filter((_, i) => i !== idx))}
                      style={{ padding: '0.5rem', background: '#ef4444', color: 'white', border: 'none', borderRadius: '0.375rem', cursor: 'pointer' }}>×</button>
                  )}
                </div>
              ))}
              <button type="button" onClick={() => setNewOrderItems([...newOrderItems, { product_id: 0, quantity: 1 }])}
                style={{ ...inputStyle, width: '100%', background: '#f8fafc', cursor: 'pointer', marginBottom: '1rem', color: '#374151' }}>
                + Add Item
              </button>
              <div style={{ display: 'flex', gap: '0.75rem', justifyContent: 'flex-end' }}>
                <button type="button" onClick={() => setShowNewOrder(false)} style={{ padding: '0.5rem 1rem', border: '1px solid #d1d5db', borderRadius: '0.375rem', cursor: 'pointer', background: 'white' }}>Cancel</button>
                <button type="submit" style={{ padding: '0.5rem 1rem', background: '#3b82f6', color: 'white', border: 'none', borderRadius: '0.375rem', cursor: 'pointer', fontWeight: 500 }}>Place Order</button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default OrdersPage;
