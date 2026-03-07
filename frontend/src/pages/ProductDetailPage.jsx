import React from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import { useProducts } from '../hooks/useProducts';
import { useInventory } from '../hooks/useInventory';
import LoadingSpinner from '../components/common/LoadingSpinner';
import StatusBadge from '../components/common/StatusBadge';
import { ArrowLeft, Edit } from 'lucide-react';
import { formatCurrency, formatDate } from '../utils/formatters';

export default function ProductDetailPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { useProductById } = useProducts();
  const { useInventoryByProductId } = useInventory();

  const { data: product, isLoading, isError } = useProductById(id);
  const { data: inventory, isLoading: invLoading } = useInventoryByProductId(id);

  if (isLoading) return <LoadingSpinner />;
  if (isError || !product) return <div className="text-red-500 p-4">Product not found.</div>;

  const invItem = inventory?.data?.[0] || inventory;

  return (
    <div>
      <button onClick={() => navigate(-1)} className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-4">
        <ArrowLeft size={16} /> Back to Products
      </button>

      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900">{product.name}</h1>
        <Link to={`/products`}
          className="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
          <Edit size={16} /> Edit Product
        </Link>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h2 className="font-semibold text-gray-900 mb-4">Product Information</h2>
          <div className="space-y-3">
            <div className="flex justify-between text-sm">
              <span className="text-gray-500">SKU</span>
              <span className="font-mono font-medium">{product.sku}</span>
            </div>
            <div className="flex justify-between text-sm">
              <span className="text-gray-500">Category</span>
              <span className="capitalize">{product.category}</span>
            </div>
            <div className="flex justify-between text-sm">
              <span className="text-gray-500">Price</span>
              <span className="font-semibold">{formatCurrency(product.price)}</span>
            </div>
            <div className="flex justify-between text-sm">
              <span className="text-gray-500">Status</span>
              <StatusBadge status={product.status} />
            </div>
            <div className="flex justify-between text-sm">
              <span className="text-gray-500">Created</span>
              <span className="text-gray-600">{formatDate(product.created_at)}</span>
            </div>
          </div>
          {product.description && (
            <div className="mt-4 pt-4 border-t border-gray-100">
              <p className="text-xs text-gray-500 uppercase font-medium mb-1">Description</p>
              <p className="text-gray-700 text-sm">{product.description}</p>
            </div>
          )}
        </div>

        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h2 className="font-semibold text-gray-900 mb-4">Inventory</h2>
          {invLoading ? <LoadingSpinner /> : invItem ? (
            <div className="space-y-3">
              <div className="flex justify-between text-sm">
                <span className="text-gray-500">Quantity</span>
                <span className={`font-bold text-lg ${invItem.quantity === 0 ? 'text-red-600' : invItem.quantity <= invItem.reorder_level ? 'text-yellow-600' : 'text-green-600'}`}>
                  {invItem.quantity}
                </span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-gray-500">Reserved</span>
                <span>{invItem.reserved_quantity}</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-gray-500">Available</span>
                <span className="font-medium">{(invItem.quantity || 0) - (invItem.reserved_quantity || 0)}</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-gray-500">Reorder Level</span>
                <span>{invItem.reorder_level}</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-gray-500">Warehouse</span>
                <span>{invItem.warehouse_location || '—'}</span>
              </div>
              <div className="pt-2">
                <Link to={`/inventory/${invItem.id}`}
                  className="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                  View Inventory Details →
                </Link>
              </div>
            </div>
          ) : (
            <p className="text-gray-500 text-sm">No inventory record found for this product.</p>
          )}
        </div>
      </div>
    </div>
  );
}
