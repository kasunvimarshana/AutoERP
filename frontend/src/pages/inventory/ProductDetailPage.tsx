import React, { useState } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { ArrowLeft, Pencil, Trash2, TrendingUp, AlertTriangle } from 'lucide-react';
import { useProduct, useUpdateProduct, useDeleteProduct, useAdjustStock } from '@/hooks/useProducts';
import Modal from '@/components/common/Modal';
import ConfirmDialog from '@/components/common/ConfirmDialog';
import ProductForm from '@/components/inventory/ProductForm';
import StockBadge from '@/components/inventory/StockBadge';
import RoleGuard from '@/components/auth/RoleGuard';
import LoadingSpinner from '@/components/common/LoadingSpinner';
import type { ProductFormData } from '@/types';

const ProductDetailPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const { data: product, isLoading, isError } = useProduct(Number(id));
  const updateMutation = useUpdateProduct();
  const deleteMutation = useDeleteProduct();
  const adjustStockMutation = useAdjustStock();

  const [editOpen, setEditOpen] = useState(false);
  const [deleteOpen, setDeleteOpen] = useState(false);
  const [stockOpen, setStockOpen] = useState(false);
  const [stockAdjust, setStockAdjust] = useState({ quantity: 0, type: 'add' as 'add'|'remove'|'set', reason: '' });

  if (isLoading)
    return (
      <div className="flex justify-center items-center h-64">
        <LoadingSpinner size="lg" />
      </div>
    );

  if (isError || !product)
    return (
      <div className="text-center py-20">
        <AlertTriangle className="mx-auto w-10 h-10 text-red-400 mb-3" />
        <p className="text-gray-600">Product not found.</p>
        <Link to="/inventory" className="text-primary-600 text-sm mt-2 hover:underline">
          Back to inventory
        </Link>
      </div>
    );

  const infoRow = (label: string, value: React.ReactNode) => (
    <div className="flex justify-between items-center py-2.5 border-b border-gray-50 last:border-0">
      <span className="text-sm text-gray-500">{label}</span>
      <span className="text-sm font-medium text-gray-800">{value}</span>
    </div>
  );

  return (
    <div className="space-y-5 max-w-4xl">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <button
            onClick={() => navigate(-1)}
            className="p-2 rounded-xl hover:bg-gray-100 transition-colors text-gray-500"
          >
            <ArrowLeft size={18} />
          </button>
          <div>
            <h1 className="text-xl font-bold text-gray-900">{product.name}</h1>
            <p className="text-sm text-gray-400 font-mono">{product.sku}</p>
          </div>
        </div>
        <div className="flex items-center gap-2">
          <RoleGuard permissions={['products.update']}>
            <button
              onClick={() => setStockOpen(true)}
              className="flex items-center gap-1.5 px-3 py-2 border border-gray-200 rounded-xl text-sm text-gray-700 hover:bg-gray-50 transition-colors"
            >
              <TrendingUp size={15} />
              Adjust Stock
            </button>
            <button
              onClick={() => setEditOpen(true)}
              className="flex items-center gap-1.5 px-3 py-2 bg-primary-600 text-white rounded-xl text-sm hover:bg-primary-700 transition-colors"
            >
              <Pencil size={15} />
              Edit
            </button>
          </RoleGuard>
          <RoleGuard permissions={['products.delete']}>
            <button
              onClick={() => setDeleteOpen(true)}
              className="p-2 rounded-xl text-red-500 hover:bg-red-50 transition-colors"
            >
              <Trash2 size={17} />
            </button>
          </RoleGuard>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
        {/* Main info */}
        <div className="md:col-span-2 space-y-4">
          <div className="bg-white rounded-2xl border border-gray-100 p-5">
            <h2 className="text-sm font-semibold text-gray-600 uppercase tracking-wide mb-3">
              Product Details
            </h2>
            {infoRow('Category', product.category ?? '—')}
            {infoRow('Unit', product.unit)}
            {infoRow('Weight', product.weight ? `${product.weight} kg` : '—')}
            {infoRow('Status', product.is_active ? (
              <span className="text-green-600 font-medium">Active</span>
            ) : (
              <span className="text-gray-400">Inactive</span>
            ))}
            {product.description && (
              <div className="mt-3">
                <p className="text-sm text-gray-500">Description</p>
                <p className="text-sm text-gray-700 mt-1">{product.description}</p>
              </div>
            )}
          </div>

          <div className="bg-white rounded-2xl border border-gray-100 p-5">
            <h2 className="text-sm font-semibold text-gray-600 uppercase tracking-wide mb-3">
              Pricing
            </h2>
            {infoRow('Selling Price', `$${product.price.toFixed(2)}`)}
            {infoRow('Cost Price', product.cost ? `$${product.cost.toFixed(2)}` : '—')}
            {product.cost && (
              infoRow('Margin', `${(((product.price - product.cost) / product.price) * 100).toFixed(1)}%`)
            )}
          </div>
        </div>

        {/* Stock sidebar */}
        <div className="space-y-4">
          <div className="bg-white rounded-2xl border border-gray-100 p-5">
            <h2 className="text-sm font-semibold text-gray-600 uppercase tracking-wide mb-3">
              Stock
            </h2>
            <div className="text-center py-3 mb-3">
              <p className="text-4xl font-bold text-gray-900">{product.available_quantity}</p>
              <p className="text-sm text-gray-500 mt-1">Available</p>
              <div className="mt-2 flex justify-center">
                <StockBadge product={product} showQuantity={false} />
              </div>
            </div>
            {infoRow('Total Quantity', product.quantity)}
            {infoRow('Reserved', product.reserved_quantity)}
            {infoRow('Reorder Point', product.reorder_point)}
            {infoRow('Reorder Qty', product.reorder_quantity)}
          </div>

          <div className="bg-white rounded-2xl border border-gray-100 p-5 text-xs text-gray-400 space-y-1">
            <p>Created: {new Date(product.created_at).toLocaleDateString()}</p>
            <p>Updated: {new Date(product.updated_at).toLocaleDateString()}</p>
          </div>
        </div>
      </div>

      {/* Edit Modal */}
      <Modal open={editOpen} onClose={() => setEditOpen(false)} title="Edit Product" size="lg">
        <ProductForm
          initialData={product}
          onSubmit={async (formData: ProductFormData) => {
            await updateMutation.mutateAsync({ id: product.id, data: formData });
            setEditOpen(false);
          }}
          isLoading={updateMutation.isPending}
          onCancel={() => setEditOpen(false)}
        />
      </Modal>

      {/* Stock Adjust Modal */}
      <Modal open={stockOpen} onClose={() => setStockOpen(false)} title="Adjust Stock" size="sm">
        <div className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Adjustment Type</label>
            <select
              value={stockAdjust.type}
              onChange={(e) => setStockAdjust((s) => ({ ...s, type: e.target.value as 'add'|'remove'|'set' }))}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
            >
              <option value="add">Add stock</option>
              <option value="remove">Remove stock</option>
              <option value="set">Set quantity</option>
            </select>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
            <input
              type="number"
              value={stockAdjust.quantity}
              onChange={(e) => setStockAdjust((s) => ({ ...s, quantity: Number(e.target.value) }))}
              min={0}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Reason</label>
            <input
              value={stockAdjust.reason}
              onChange={(e) => setStockAdjust((s) => ({ ...s, reason: e.target.value }))}
              placeholder="e.g. Stock received from supplier"
              className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
            />
          </div>
          <div className="flex justify-end gap-2 pt-2">
            <button
              onClick={() => setStockOpen(false)}
              className="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50"
            >
              Cancel
            </button>
            <button
              onClick={async () => {
                await adjustStockMutation.mutateAsync({
                  product_id: product.id,
                  ...stockAdjust,
                });
                setStockOpen(false);
              }}
              disabled={adjustStockMutation.isPending}
              className="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
            >
              {adjustStockMutation.isPending ? 'Saving…' : 'Apply'}
            </button>
          </div>
        </div>
      </Modal>

      {/* Delete Confirm */}
      <ConfirmDialog
        open={deleteOpen}
        onClose={() => setDeleteOpen(false)}
        onConfirm={async () => {
          await deleteMutation.mutateAsync(product.id);
          navigate('/inventory');
        }}
        title="Delete Product"
        message={`Delete "${product.name}"? This cannot be undone.`}
        confirmLabel="Delete"
        isLoading={deleteMutation.isPending}
      />
    </div>
  );
};

export default ProductDetailPage;
