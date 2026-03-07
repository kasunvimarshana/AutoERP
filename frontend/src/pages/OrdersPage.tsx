import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { orderService, CreateOrderPayload } from '../services/orderService';
import { Plus, X } from 'lucide-react';

/**
 * Orders management page.
 * Lists orders and shows Saga transaction log entries.
 * Creating an order triggers a distributed Saga transaction.
 */
export default function OrdersPage() {
  const queryClient = useQueryClient();
  const [showForm, setShowForm] = useState(false);
  const [selectedOrderId, setSelectedOrderId] = useState<string | null>(null);
  const [form, setForm] = useState<CreateOrderPayload>({
    items: [{ product_id: '', quantity: 1, unit_price: 0 }],
    total_amount: 0,
    currency: 'USD',
    payment_method: 'credit_card',
  });

  const { data, isLoading } = useQuery({
    queryKey: ['orders'],
    queryFn:  () => orderService.listOrders(),
  });

  const { data: selectedOrder } = useQuery({
    queryKey: ['order', selectedOrderId],
    queryFn:  () => orderService.getOrder(selectedOrderId!),
    enabled:  !!selectedOrderId,
  });

  const createMutation = useMutation({
    mutationFn: (payload: CreateOrderPayload) => orderService.createOrder(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['orders'] });
      setShowForm(false);
    },
  });

  const cancelMutation = useMutation({
    mutationFn: (id: string) => orderService.cancelOrder(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['orders'] }),
  });

  const statusColors: Record<string, { bg: string; text: string }> = {
    confirmed: { bg: '#dcfce7', text: '#16a34a' },
    pending:   { bg: '#fef9c3', text: '#ca8a04' },
    cancelled: { bg: '#fee2e2', text: '#dc2626' },
    failed:    { bg: '#fef2f2', text: '#b91c1c' },
  };

  const stepStatusColors: Record<string, string> = {
    completed:            '#16a34a',
    failed:               '#dc2626',
    compensated:          '#f59e0b',
    compensation_failed:  '#b91c1c',
  };

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 24 }}>
        <h2 style={{ fontSize: 22, fontWeight: 700 }}>Orders</h2>
        <button
          onClick={() => setShowForm(!showForm)}
          style={{
            display: 'flex', alignItems: 'center', gap: 8,
            padding: '9px 18px', background: '#3b82f6', color: '#fff',
            border: 'none', borderRadius: 8, cursor: 'pointer', fontSize: 14, fontWeight: 600,
          }}
        >
          <Plus size={16} /> New Order (Saga)
        </button>
      </div>

      {/* Create Order Form */}
      {showForm && (
        <div style={{ background: '#fff', borderRadius: 10, padding: 24, marginBottom: 24, boxShadow: '0 1px 4px rgba(0,0,0,0.06)' }}>
          <h3 style={{ fontSize: 16, fontWeight: 600, marginBottom: 4 }}>Create Order</h3>
          <p style={{ color: '#64748b', fontSize: 13, marginBottom: 16 }}>
            This will trigger a distributed Saga transaction: Reserve Inventory → Process Payment → Confirm Order → Send Notification
          </p>

          {form.items.map((item, i) => (
            <div key={i} style={{ display: 'grid', gridTemplateColumns: '2fr 1fr 1fr auto', gap: 12, marginBottom: 12 }}>
              <input
                placeholder="Product UUID"
                value={item.product_id}
                onChange={e => {
                  const items = [...form.items];
                  items[i] = { ...items[i], product_id: e.target.value };
                  setForm({ ...form, items });
                }}
                style={{ padding: '8px 12px', border: '1px solid #cbd5e1', borderRadius: 6, fontSize: 14 }}
              />
              <input
                type="number" placeholder="Qty"
                value={item.quantity}
                onChange={e => {
                  const items = [...form.items];
                  items[i] = { ...items[i], quantity: parseInt(e.target.value) };
                  setForm({ ...form, items });
                }}
                style={{ padding: '8px 12px', border: '1px solid #cbd5e1', borderRadius: 6, fontSize: 14 }}
              />
              <input
                type="number" placeholder="Unit price"
                value={item.unit_price}
                onChange={e => {
                  const items = [...form.items];
                  items[i] = { ...items[i], unit_price: parseFloat(e.target.value) };
                  setForm({ ...form, items });
                }}
                style={{ padding: '8px 12px', border: '1px solid #cbd5e1', borderRadius: 6, fontSize: 14 }}
              />
              <button onClick={() => setForm({ ...form, items: form.items.filter((_, j) => j !== i) })}
                style={{ background: 'none', border: 'none', cursor: 'pointer', color: '#ef4444' }}>
                <X size={16} />
              </button>
            </div>
          ))}

          <div style={{ display: 'flex', gap: 12, marginBottom: 16 }}>
            <button
              onClick={() => setForm({ ...form, items: [...form.items, { product_id: '', quantity: 1, unit_price: 0 }] })}
              style={{ padding: '7px 14px', background: '#f1f5f9', border: 'none', borderRadius: 6, cursor: 'pointer', fontSize: 13 }}
            >
              + Add Item
            </button>
          </div>

          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12, marginBottom: 16 }}>
            <div>
              <label style={{ fontSize: 13, fontWeight: 600, display: 'block', marginBottom: 4 }}>Total Amount</label>
              <input
                type="number" value={form.total_amount}
                onChange={e => setForm({ ...form, total_amount: parseFloat(e.target.value) })}
                style={{ padding: '8px 12px', border: '1px solid #cbd5e1', borderRadius: 6, fontSize: 14, width: '100%' }}
              />
            </div>
            <div>
              <label style={{ fontSize: 13, fontWeight: 600, display: 'block', marginBottom: 4 }}>Payment Method</label>
              <select
                value={form.payment_method}
                onChange={e => setForm({ ...form, payment_method: e.target.value })}
                style={{ padding: '8px 12px', border: '1px solid #cbd5e1', borderRadius: 6, fontSize: 14, width: '100%' }}
              >
                <option value="credit_card">Credit Card</option>
                <option value="debit_card">Debit Card</option>
                <option value="paypal">PayPal</option>
                <option value="bank_transfer">Bank Transfer</option>
              </select>
            </div>
          </div>

          {createMutation.isError && (
            <div style={{ background: '#fef2f2', color: '#b91c1c', padding: 12, borderRadius: 6, marginBottom: 12, fontSize: 13 }}>
              Saga transaction failed. All changes have been rolled back.
            </div>
          )}

          <div style={{ display: 'flex', gap: 10 }}>
            <button
              onClick={() => createMutation.mutate(form)}
              disabled={createMutation.isPending}
              style={{ padding: '9px 20px', background: '#3b82f6', color: '#fff', border: 'none', borderRadius: 6, cursor: 'pointer', fontWeight: 600 }}
            >
              {createMutation.isPending ? 'Processing Saga…' : 'Place Order'}
            </button>
            <button onClick={() => setShowForm(false)}
              style={{ padding: '9px 20px', background: '#f1f5f9', border: 'none', borderRadius: 6, cursor: 'pointer' }}>
              Cancel
            </button>
          </div>
        </div>
      )}

      <div style={{ display: 'grid', gridTemplateColumns: selectedOrderId ? '1fr 1fr' : '1fr', gap: 20 }}>
        {/* Orders list */}
        <div style={{ background: '#fff', borderRadius: 10, boxShadow: '0 1px 4px rgba(0,0,0,0.06)', overflow: 'hidden' }}>
          <table style={{ width: '100%', borderCollapse: 'collapse' }}>
            <thead>
              <tr style={{ background: '#f8fafc', borderBottom: '1px solid #e2e8f0' }}>
                {['Order ID', 'Amount', 'Status', 'Date', ''].map(h => (
                  <th key={h} style={{ padding: '12px 16px', textAlign: 'left', fontSize: 13, fontWeight: 600, color: '#64748b' }}>{h}</th>
                ))}
              </tr>
            </thead>
            <tbody>
              {isLoading ? (
                <tr><td colSpan={5} style={{ padding: 24, textAlign: 'center', color: '#94a3b8' }}>Loading…</td></tr>
              ) : data?.data?.map(order => {
                const sc = statusColors[order.status] ?? { bg: '#f1f5f9', text: '#64748b' };
                return (
                  <tr
                    key={order.id}
                    style={{ borderBottom: '1px solid #f1f5f9', cursor: 'pointer', background: selectedOrderId === order.id ? '#eff6ff' : undefined }}
                    onClick={() => setSelectedOrderId(order.id === selectedOrderId ? null : order.id)}
                  >
                    <td style={{ padding: '12px 16px', fontFamily: 'monospace', fontSize: 13 }}>#{order.id.slice(0, 8)}</td>
                    <td style={{ padding: '12px 16px', fontWeight: 600 }}>${order.total_amount}</td>
                    <td style={{ padding: '12px 16px' }}>
                      <span style={{ background: sc.bg, color: sc.text, padding: '3px 10px', borderRadius: 12, fontSize: 12, fontWeight: 600 }}>{order.status}</span>
                    </td>
                    <td style={{ padding: '12px 16px', color: '#64748b', fontSize: 13 }}>
                      {order.confirmed_at ? new Date(order.confirmed_at).toLocaleDateString() : '–'}
                    </td>
                    <td style={{ padding: '12px 16px' }}>
                      {order.status === 'confirmed' && (
                        <button
                          onClick={e => { e.stopPropagation(); cancelMutation.mutate(order.id); }}
                          style={{ padding: '4px 10px', background: '#fee2e2', color: '#dc2626', border: 'none', borderRadius: 4, cursor: 'pointer', fontSize: 12 }}
                        >
                          Cancel
                        </button>
                      )}
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>

        {/* Saga Log Panel */}
        {selectedOrder && (
          <div style={{ background: '#fff', borderRadius: 10, padding: 24, boxShadow: '0 1px 4px rgba(0,0,0,0.06)' }}>
            <h3 style={{ fontSize: 16, fontWeight: 600, marginBottom: 16 }}>
              Saga Transaction Log <span style={{ color: '#94a3b8', fontFamily: 'monospace', fontSize: 13 }}>#{selectedOrder.id.slice(0, 8)}</span>
            </h3>
            {selectedOrder.saga_logs?.map(log => (
              <div key={log.id} style={{
                display: 'flex', alignItems: 'flex-start', gap: 12,
                padding: '10px 0', borderBottom: '1px solid #f1f5f9',
              }}>
                <div style={{
                  width: 10, height: 10, borderRadius: '50%',
                  background: stepStatusColors[log.status] ?? '#94a3b8',
                  marginTop: 4, flexShrink: 0,
                }} />
                <div>
                  <p style={{ fontWeight: 600, fontSize: 14 }}>{log.step_name}</p>
                  <p style={{ fontSize: 12, color: stepStatusColors[log.status] ?? '#64748b', fontWeight: 600 }}>{log.status}</p>
                  {log.error_message && (
                    <p style={{ fontSize: 12, color: '#dc2626', marginTop: 2 }}>{log.error_message}</p>
                  )}
                  <p style={{ fontSize: 11, color: '#94a3b8' }}>{new Date(log.created_at).toLocaleTimeString()}</p>
                </div>
              </div>
            )) ?? <p style={{ color: '#94a3b8' }}>No saga log entries.</p>}
          </div>
        )}
      </div>
    </div>
  );
}
