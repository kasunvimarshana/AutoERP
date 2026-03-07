import React from 'react';
import { useForm } from '@/hooks/useForm';
import type { Product, ProductFormData } from '@/types';
import LoadingSpinner from '@/components/common/LoadingSpinner';

interface ProductFormProps {
  initialData?: Product;
  onSubmit: (data: ProductFormData) => Promise<void>;
  isLoading?: boolean;
  onCancel: () => void;
}

const defaultValues: ProductFormData = {
  sku: '',
  name: '',
  description: '',
  category: '',
  price: 0,
  cost: 0,
  quantity: 0,
  reorder_point: 10,
  reorder_quantity: 50,
  unit: 'pcs',
  weight: 0,
  is_active: true,
};

const ProductForm: React.FC<ProductFormProps> = ({
  initialData,
  onSubmit,
  isLoading = false,
  onCancel,
}) => {
  const { values, errors, handleChange, handleSubmit } = useForm(
    initialData
      ? {
          sku: initialData.sku,
          name: initialData.name,
          description: initialData.description ?? '',
          category: initialData.category ?? '',
          price: initialData.price,
          cost: initialData.cost ?? 0,
          quantity: initialData.quantity,
          reorder_point: initialData.reorder_point,
          reorder_quantity: initialData.reorder_quantity,
          unit: initialData.unit,
          weight: initialData.weight ?? 0,
          is_active: initialData.is_active,
        }
      : defaultValues,
    {
      sku: (v: unknown) => (!v ? 'SKU is required' : null),
      name: (v: unknown) => (!v ? 'Name is required' : null),
      price: (v: unknown) => (Number(v) < 0 ? 'Price must be non-negative' : null),
      quantity: (v: unknown) => (Number(v) < 0 ? 'Quantity must be non-negative' : null),
    },
  );

  const onFormSubmit = handleSubmit(async (data) => {
    await onSubmit({
      sku: data.sku as string,
      name: data.name as string,
      description: data.description as string | undefined,
      category: data.category as string | undefined,
      price: Number(data.price),
      cost: Number(data.cost),
      quantity: Number(data.quantity),
      reorder_point: Number(data.reorder_point),
      reorder_quantity: Number(data.reorder_quantity),
      unit: data.unit as string,
      weight: Number(data.weight),
      is_active: data.is_active as boolean,
    });
  });

  const inputClass =
    'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors';
  const labelClass = 'block text-sm font-medium text-gray-700 mb-1';
  const errorClass = 'mt-1 text-xs text-red-600';

  return (
    <form onSubmit={onFormSubmit} className="space-y-4">
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label className={labelClass}>SKU *</label>
          <input
            name="sku"
            value={String(values.sku)}
            onChange={handleChange}
            placeholder="e.g. PROD-001"
            className={inputClass}
            disabled={!!initialData}
          />
          {errors.sku && <p className={errorClass}>{errors.sku}</p>}
        </div>
        <div>
          <label className={labelClass}>Name *</label>
          <input
            name="name"
            value={String(values.name)}
            onChange={handleChange}
            placeholder="Product name"
            className={inputClass}
          />
          {errors.name && <p className={errorClass}>{errors.name}</p>}
        </div>
      </div>

      <div>
        <label className={labelClass}>Description</label>
        <textarea
          name="description"
          value={String(values.description)}
          onChange={handleChange}
          rows={3}
          placeholder="Product description"
          className={inputClass}
        />
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label className={labelClass}>Category</label>
          <input
            name="category"
            value={String(values.category)}
            onChange={handleChange}
            placeholder="e.g. Electronics"
            className={inputClass}
          />
        </div>
        <div>
          <label className={labelClass}>Unit</label>
          <select name="unit" value={String(values.unit)} onChange={handleChange} className={inputClass}>
            {['pcs', 'kg', 'g', 'l', 'ml', 'm', 'cm', 'box', 'pack'].map((u) => (
              <option key={u} value={u}>{u}</option>
            ))}
          </select>
        </div>
      </div>

      <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div>
          <label className={labelClass}>Price ($) *</label>
          <input
            type="number"
            name="price"
            value={String(values.price)}
            onChange={handleChange}
            min="0"
            step="0.01"
            className={inputClass}
          />
          {errors.price && <p className={errorClass}>{errors.price}</p>}
        </div>
        <div>
          <label className={labelClass}>Cost ($)</label>
          <input
            type="number"
            name="cost"
            value={String(values.cost)}
            onChange={handleChange}
            min="0"
            step="0.01"
            className={inputClass}
          />
        </div>
        <div>
          <label className={labelClass}>Quantity *</label>
          <input
            type="number"
            name="quantity"
            value={String(values.quantity)}
            onChange={handleChange}
            min="0"
            className={inputClass}
          />
          {errors.quantity && <p className={errorClass}>{errors.quantity}</p>}
        </div>
        <div>
          <label className={labelClass}>Weight (kg)</label>
          <input
            type="number"
            name="weight"
            value={String(values.weight)}
            onChange={handleChange}
            min="0"
            step="0.01"
            className={inputClass}
          />
        </div>
      </div>

      <div className="grid grid-cols-2 gap-4">
        <div>
          <label className={labelClass}>Reorder Point</label>
          <input
            type="number"
            name="reorder_point"
            value={String(values.reorder_point)}
            onChange={handleChange}
            min="0"
            className={inputClass}
          />
        </div>
        <div>
          <label className={labelClass}>Reorder Quantity</label>
          <input
            type="number"
            name="reorder_quantity"
            value={String(values.reorder_quantity)}
            onChange={handleChange}
            min="1"
            className={inputClass}
          />
        </div>
      </div>

      <div className="flex items-center gap-2">
        <input
          type="checkbox"
          id="is_active"
          name="is_active"
          checked={Boolean(values.is_active)}
          onChange={handleChange}
          className="w-4 h-4 text-primary-600 rounded border-gray-300 focus:ring-primary-500"
        />
        <label htmlFor="is_active" className="text-sm text-gray-700">Active product</label>
      </div>

      <div className="flex justify-end gap-3 pt-2">
        <button
          type="button"
          onClick={onCancel}
          disabled={isLoading}
          className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50"
        >
          Cancel
        </button>
        <button
          type="submit"
          disabled={isLoading}
          className="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50"
        >
          {isLoading && <LoadingSpinner size="sm" />}
          {initialData ? 'Update Product' : 'Create Product'}
        </button>
      </div>
    </form>
  );
};

export default ProductForm;
