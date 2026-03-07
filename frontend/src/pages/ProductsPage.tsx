import React, { useState, useEffect } from 'react';
import { productsApi } from '../api/products';
import type { Product } from '../types';
import DataTable from '../components/DataTable';
import Pagination from '../components/Pagination';

const ProductsPage: React.FC = () => {
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);
  const [currentPage, setCurrentPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [search, setSearch] = useState('');
  const [showForm, setShowForm] = useState(false);
  const [editingProduct, setEditingProduct] = useState<Product | null>(null);
  const [formData, setFormData] = useState<Partial<Product>>({ name: '', sku: '', price: 0, category: '', description: '', is_active: true });
  const [error, setError] = useState('');

  const fetchProducts = async (page = 1) => {
    setLoading(true);
    try {
      const res = await productsApi.list({ per_page: 10, page, search: search || undefined });
      const data = res.data.data;
      if (data && 'data' in data) {
        setProducts((data as { data: Product[] }).data);
        setLastPage((data as { last_page: number }).last_page);
      } else {
        setProducts(data as Product[]);
      }
    } catch { setError('Failed to load products'); }
    finally { setLoading(false); }
  };

  useEffect(() => { fetchProducts(currentPage); }, [currentPage, search]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      if (editingProduct) {
        await productsApi.update(editingProduct.id, formData);
      } else {
        await productsApi.create(formData);
      }
      setShowForm(false);
      fetchProducts(currentPage);
    } catch (err: unknown) {
      setError((err as { response?: { data?: { message?: string } } })?.response?.data?.message || 'Operation failed');
    }
  };

  const handleDelete = async (product: Product) => {
    if (!confirm(`Delete ${product.name}?`)) return;
    try { await productsApi.delete(product.id); fetchProducts(currentPage); }
    catch { setError('Delete failed'); }
  };

  const columns = [
    { key: 'id', label: 'ID' },
    { key: 'name', label: 'Name' },
    { key: 'sku', label: 'SKU' },
    { key: 'category', label: 'Category' },
    { key: 'price', label: 'Price', render: (val: unknown) => `$${Number(val).toFixed(2)}` },
    {
      key: 'is_active', label: 'Status',
      render: (val: unknown) => (
        <span style={{ padding: '0.125rem 0.5rem', borderRadius: '9999px', fontSize: '0.75rem', background: val ? '#dcfce7' : '#fee2e2', color: val ? '#16a34a' : '#dc2626' }}>
          {val ? 'Active' : 'Inactive'}
        </span>
      ),
    },
  ];

  const inputStyle: React.CSSProperties = {
    width: '100%', padding: '0.5rem 0.75rem', border: '1px solid #d1d5db',
    borderRadius: '0.375rem', fontSize: '0.875rem', marginBottom: '0.75rem', boxSizing: 'border-box',
  };

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.5rem' }}>
        <h1 style={{ fontSize: '1.5rem', fontWeight: 700, color: '#1e293b', margin: 0 }}>Products</h1>
        <button onClick={() => { setShowForm(true); setEditingProduct(null); setFormData({ name: '', sku: '', price: 0, category: '', description: '', is_active: true }); }}
          style={{ padding: '0.5rem 1rem', background: '#3b82f6', color: 'white', border: 'none', borderRadius: '0.375rem', cursor: 'pointer', fontWeight: 500 }}>
          + New Product
        </button>
      </div>

      {error && <div style={{ background: '#fef2f2', color: '#dc2626', padding: '0.75rem', borderRadius: '0.375rem', marginBottom: '1rem' }}>{error}</div>}

      <input type="text" placeholder="Search products..." value={search} onChange={(e) => { setSearch(e.target.value); setCurrentPage(1); }}
        style={{ ...inputStyle, marginBottom: '1rem', maxWidth: '300px' }} />

      <DataTable columns={columns as Parameters<typeof DataTable>[0]['columns']} data={products as unknown as Record<string, unknown>[]} loading={loading}
        onEdit={(row) => { setEditingProduct(row as unknown as Product); setFormData(row as unknown as Product); setShowForm(true); }}
        onDelete={(row) => handleDelete(row as unknown as Product)} />

      <Pagination currentPage={currentPage} lastPage={lastPage} onPageChange={setCurrentPage} />

      {showForm && (
        <div style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.5)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 50 }}>
          <div style={{ background: 'white', borderRadius: '0.75rem', padding: '2rem', width: '100%', maxWidth: '450px' }}>
            <h2 style={{ marginBottom: '1.5rem', fontSize: '1.25rem', fontWeight: 600 }}>{editingProduct ? 'Edit Product' : 'Create Product'}</h2>
            <form onSubmit={handleSubmit}>
              <input style={inputStyle} placeholder="Name" value={formData.name || ''} onChange={(e) => setFormData({ ...formData, name: e.target.value })} required />
              <input style={inputStyle} placeholder="SKU" value={formData.sku || ''} onChange={(e) => setFormData({ ...formData, sku: e.target.value })} required />
              <input style={inputStyle} type="number" step="0.01" placeholder="Price" value={formData.price || ''} onChange={(e) => setFormData({ ...formData, price: parseFloat(e.target.value) })} required />
              <input style={inputStyle} placeholder="Category" value={formData.category || ''} onChange={(e) => setFormData({ ...formData, category: e.target.value })} />
              <textarea style={{ ...inputStyle, resize: 'vertical' }} placeholder="Description" value={formData.description || ''} onChange={(e) => setFormData({ ...formData, description: e.target.value })} rows={3} />
              <div style={{ display: 'flex', gap: '0.75rem', justifyContent: 'flex-end' }}>
                <button type="button" onClick={() => setShowForm(false)} style={{ padding: '0.5rem 1rem', border: '1px solid #d1d5db', borderRadius: '0.375rem', cursor: 'pointer', background: 'white' }}>Cancel</button>
                <button type="submit" style={{ padding: '0.5rem 1rem', background: '#3b82f6', color: 'white', border: 'none', borderRadius: '0.375rem', cursor: 'pointer', fontWeight: 500 }}>Save</button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default ProductsPage;
