import { useState } from 'react';
import { Link } from 'react-router-dom';
import { Plus, Pencil, Trash2, Eye } from 'lucide-react';
import { useForm } from 'react-hook-form';
import { yupResolver } from '@hookform/resolvers/yup';
import * as yup from 'yup';
import { useProducts, useCategories, useCreateProduct, useUpdateProduct, useDeleteProduct } from '../hooks/useProducts';
import { useAuth } from '../hooks/useAuth';
import LoadingSpinner from '../components/common/LoadingSpinner';
import StatusBadge from '../components/common/StatusBadge';
import SearchBar from '../components/common/SearchBar';
import Pagination from '../components/common/Pagination';
import Modal from '../components/common/Modal';
import ConfirmDialog from '../components/common/ConfirmDialog';
import FormField from '../components/common/FormField';

const schema = yup.object({
  name: yup.string().required('Name is required'),
  sku: yup.string().required('SKU is required'),
  price: yup.number().positive().required('Price is required'),
  category: yup.string().required('Category is required'),
  status: yup.string().required(),
  stock_quantity: yup.number().min(0).required(),
  description: yup.string(),
});

function ProductForm({ defaultValues, onSubmit, loading }) {
  const { register, handleSubmit, formState: { errors } } = useForm({ resolver: yupResolver(schema), defaultValues });
  const { data: cats } = useCategories();
  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
      <div className="grid grid-cols-2 gap-4">
        <FormField label="Name" required error={errors.name?.message}><input {...register('name')} className="input" /></FormField>
        <FormField label="SKU" required error={errors.sku?.message}><input {...register('sku')} className="input" /></FormField>
        <FormField label="Price" required error={errors.price?.message}><input type="number" step="0.01" {...register('price')} className="input" /></FormField>
        <FormField label="Stock Quantity" required error={errors.stock_quantity?.message}><input type="number" {...register('stock_quantity')} className="input" /></FormField>
        <FormField label="Category" required error={errors.category?.message}>
          <select {...register('category')} className="input">
            <option value="">Select…</option>
            {(cats?.data || cats || []).map(c => <option key={c.id || c} value={c.id || c}>{c.name || c}</option>)}
          </select>
        </FormField>
        <FormField label="Status" required error={errors.status?.message}>
          <select {...register('status')} className="input">
            {['active','inactive','discontinued'].map(s => <option key={s} value={s}>{s}</option>)}
          </select>
        </FormField>
      </div>
      <FormField label="Description" error={errors.description?.message}><textarea {...register('description')} rows={3} className="input" /></FormField>
      <div className="flex justify-end gap-2 pt-2">
        <button type="submit" disabled={loading} className="btn-primary">{loading ? 'Saving…' : 'Save Product'}</button>
      </div>
    </form>
  );
}

export default function ProductsPage() {
  const { isManager } = useAuth();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [category, setCategory] = useState('');
  const [status, setStatus] = useState('');
  const [modal, setModal] = useState(null);
  const [deleteTarget, setDeleteTarget] = useState(null);

  const { data, isLoading } = useProducts({ page, search, category, status, per_page: 10 });
  const { data: cats } = useCategories();
  const create = useCreateProduct();
  const update = useUpdateProduct();
  const remove = useDeleteProduct();

  const items = data?.data || data?.items || [];
  const total = data?.total || 0;

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <SearchBar value={search} onChange={setSearch} placeholder="Search products…" />
        <div className="flex items-center gap-2">
          <select value={category} onChange={e => setCategory(e.target.value)} className="input text-sm">
            <option value="">All Categories</option>
            {(cats?.data || cats || []).map(c => <option key={c.id||c} value={c.id||c}>{c.name||c}</option>)}
          </select>
          <select value={status} onChange={e => setStatus(e.target.value)} className="input text-sm">
            <option value="">All Status</option>
            {['active','inactive','discontinued'].map(s => <option key={s} value={s}>{s}</option>)}
          </select>
          {isManager() && <button onClick={() => setModal({ type: 'add' })} className="btn-primary flex items-center gap-1"><Plus size={16} /> Add Product</button>}
        </div>
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        {isLoading ? <div className="p-12 flex justify-center"><LoadingSpinner /></div> : (
          <table className="w-full text-sm">
            <thead className="bg-gray-50 border-b border-gray-100">
              <tr>{['SKU','Name','Category','Price','Status','Actions'].map(h => <th key={h} className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">{h}</th>)}</tr>
            </thead>
            <tbody className="divide-y divide-gray-50">
              {items.length === 0 ? <tr><td colSpan={6} className="text-center py-10 text-gray-400">No products found</td></tr> : items.map(p => (
                <tr key={p.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3 font-mono text-xs text-gray-500">{p.sku}</td>
                  <td className="px-4 py-3 font-medium text-gray-800">{p.name}</td>
                  <td className="px-4 py-3 text-gray-500">{p.category}</td>
                  <td className="px-4 py-3 font-medium">${Number(p.price||0).toFixed(2)}</td>
                  <td className="px-4 py-3"><StatusBadge status={p.status} /></td>
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-1">
                      <Link to={`/products/${p.id}`} className="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded"><Eye size={15} /></Link>
                      {isManager() && <>
                        <button onClick={() => setModal({ type: 'edit', product: p })} className="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded"><Pencil size={15} /></button>
                        <button onClick={() => setDeleteTarget(p)} className="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded"><Trash2 size={15} /></button>
                      </>}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>
      <Pagination page={page} total={total} perPage={10} onChange={setPage} />

      <Modal isOpen={!!modal} onClose={() => setModal(null)} title={modal?.type === 'edit' ? 'Edit Product' : 'Add Product'} size="lg">
        {modal && <ProductForm
          defaultValues={modal.product || { status: 'active', stock_quantity: 0 }}
          loading={create.isPending || update.isPending}
          onSubmit={data => {
            const action = modal.type === 'edit' ? update.mutateAsync({ id: modal.product.id, data }) : create.mutateAsync(data);
            action.then(() => setModal(null));
          }}
        />}
      </Modal>

      <ConfirmDialog isOpen={!!deleteTarget} onClose={() => setDeleteTarget(null)} onConfirm={() => remove.mutate(deleteTarget?.id)} title="Delete Product" message={`Delete "${deleteTarget?.name}"? This cannot be undone.`} confirmLabel="Delete" variant="danger" />
    </div>
  );
}
