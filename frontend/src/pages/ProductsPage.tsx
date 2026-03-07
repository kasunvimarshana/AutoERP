import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { inventoryService, CreateProductPayload } from '../services/inventoryService';
import { Plus, Trash2 } from 'lucide-react';

/**
 * Product catalogue management page.
 * Lists products and allows creation and deletion.
 */
export default function ProductsPage() {
  const queryClient = useQueryClient();
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState<CreateProductPayload>({
    name: '', sku: '', price: 0, description: '', currency: 'USD',
  });

  const { data, isLoading } = useQuery({
    queryKey: ['products'],
    queryFn:  () => inventoryService.listProducts(),
  });

  const createMutation = useMutation({
    mutationFn: (payload: CreateProductPayload) => inventoryService.createProduct(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['products'] });
      setShowForm(false);
      setForm({ name: '', sku: '', price: 0, description: '', currency: 'USD' });
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (id: string) => inventoryService.deleteProduct(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['products'] }),
  });

  const inputStyle: React.CSSProperties = {
    padding: '8px 12px', border: '1px solid #cbd5e1', borderRadius: 6,
    fontSize: 14, outline: 'none', width: '100%',
  };

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 24 }}>
        <h2 style={{ fontSize: 22, fontWeight: 700 }}>Products</h2>
        <button
          onClick={() => setShowForm(!showForm)}
          style={{
            display: 'flex', alignItems: 'center', gap: 8,
            padding: '9px 18px', background: '#3b82f6', color: '#fff',
            border: 'none', borderRadius: 8, cursor: 'pointer', fontSize: 14, fontWeight: 600,
          }}
        >
          <Plus size={16} /> Add Product
        </button>
      </div>

      {/* Create form */}
      {showForm && (
        <div style={{ background: '#fff', borderRadius: 10, padding: 24, marginBottom: 24, boxShadow: '0 1px 4px rgba(0,0,0,0.06)' }}>
          <h3 style={{ marginBottom: 16, fontSize: 16, fontWeight: 600 }}>New Product</h3>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
            <div>
              <label style={{ fontSize: 13, fontWeight: 600, display: 'block', marginBottom: 4 }}>Name *</label>
              <input style={inputStyle} value={form.name} onChange={e => setForm({ ...form, name: e.target.value })} />
            </div>
            <div>
              <label style={{ fontSize: 13, fontWeight: 600, display: 'block', marginBottom: 4 }}>SKU *</label>
              <input style={inputStyle} value={form.sku} onChange={e => setForm({ ...form, sku: e.target.value })} />
            </div>
            <div>
              <label style={{ fontSize: 13, fontWeight: 600, display: 'block', marginBottom: 4 }}>Price *</label>
              <input type="number" style={inputStyle} value={form.price} onChange={e => setForm({ ...form, price: parseFloat(e.target.value) })} />
            </div>
            <div>
              <label style={{ fontSize: 13, fontWeight: 600, display: 'block', marginBottom: 4 }}>Currency</label>
              <input style={inputStyle} value={form.currency} onChange={e => setForm({ ...form, currency: e.target.value })} placeholder="USD" />
            </div>
          </div>
          <div style={{ marginTop: 16, display: 'flex', gap: 10 }}>
            <button
              onClick={() => createMutation.mutate(form)}
              disabled={createMutation.isPending}
              style={{ padding: '9px 20px', background: '#3b82f6', color: '#fff', border: 'none', borderRadius: 6, cursor: 'pointer', fontWeight: 600 }}
            >
              {createMutation.isPending ? 'Saving…' : 'Save'}
            </button>
            <button
              onClick={() => setShowForm(false)}
              style={{ padding: '9px 20px', background: '#f1f5f9', border: 'none', borderRadius: 6, cursor: 'pointer' }}
            >
              Cancel
            </button>
          </div>
        </div>
      )}

      {/* Products table */}
      <div style={{ background: '#fff', borderRadius: 10, boxShadow: '0 1px 4px rgba(0,0,0,0.06)', overflow: 'hidden' }}>
        <table style={{ width: '100%', borderCollapse: 'collapse' }}>
          <thead>
            <tr style={{ background: '#f8fafc', borderBottom: '1px solid #e2e8f0' }}>
              {['Name', 'SKU', 'Price', 'Status', 'Stock', ''].map(h => (
                <th key={h} style={{ padding: '12px 16px', textAlign: 'left', fontSize: 13, fontWeight: 600, color: '#64748b' }}>{h}</th>
              ))}
            </tr>
          </thead>
          <tbody>
            {isLoading ? (
              <tr><td colSpan={6} style={{ padding: 24, textAlign: 'center', color: '#94a3b8' }}>Loading…</td></tr>
            ) : data?.data?.map(product => (
              <tr key={product.id} style={{ borderBottom: '1px solid #f1f5f9' }}>
                <td style={{ padding: '12px 16px', fontWeight: 500 }}>{product.name}</td>
                <td style={{ padding: '12px 16px', color: '#64748b', fontFamily: 'monospace', fontSize: 13 }}>{product.sku}</td>
                <td style={{ padding: '12px 16px' }}>${product.price.toFixed(2)}</td>
                <td style={{ padding: '12px 16px' }}>
                  <span style={{
                    background: product.status === 'active' ? '#dcfce7' : '#f1f5f9',
                    color: product.status === 'active' ? '#16a34a' : '#64748b',
                    padding: '3px 10px', borderRadius: 12, fontSize: 12, fontWeight: 600,
                  }}>{product.status}</span>
                </td>
                <td style={{ padding: '12px 16px' }}>
                  {product.inventory_item?.quantity_available ?? '–'}
                </td>
                <td style={{ padding: '12px 16px' }}>
                  <button
                    onClick={() => deleteMutation.mutate(product.id)}
                    style={{ background: 'none', border: 'none', cursor: 'pointer', color: '#ef4444' }}
                  >
                    <Trash2 size={16} />
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
