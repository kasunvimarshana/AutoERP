import React, { useState } from 'react';
import { Plus, Trash2 } from 'lucide-react';
import { useProducts } from '@/hooks/useProducts';
import type { OrderFormData, OrderItemFormData } from '@/types';
import LoadingSpinner from '@/components/common/LoadingSpinner';

interface OrderFormProps {
  onSubmit: (data: OrderFormData) => Promise<void>;
  isLoading?: boolean;
  onCancel: () => void;
}

const emptyItem = (): OrderItemFormData => ({
  product_id: 0,
  quantity: 1,
  unit_price: 0,
});

const OrderForm: React.FC<OrderFormProps> = ({ onSubmit, isLoading = false, onCancel }) => {
  const { data: productsData } = useProducts({ per_page: 100 });
  const products = productsData?.data ?? [];

  const [customerName, setCustomerName] = useState('');
  const [customerEmail, setCustomerEmail] = useState('');
  const [notes, setNotes] = useState('');
  const [items, setItems] = useState<OrderItemFormData[]>([emptyItem()]);
  const [errors, setErrors] = useState<Record<string, string>>({});

  const inputClass =
    'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 transition-colors';
  const labelClass = 'block text-sm font-medium text-gray-700 mb-1';
  const errorClass = 'mt-1 text-xs text-red-600';

  const updateItem = (index: number, field: keyof OrderItemFormData, value: number) => {
    setItems((prev) => {
      const updated = [...prev];
      updated[index] = { ...updated[index], [field]: value };
      if (field === 'product_id') {
        const product = products.find((p) => p.id === value);
        if (product) updated[index].unit_price = product.price;
      }
      return updated;
    });
  };

  const removeItem = (index: number) => {
    setItems((prev) => prev.filter((_, i) => i !== index));
  };

  const total = items.reduce((sum, item) => sum + item.quantity * item.unit_price, 0);

  const validate = () => {
    const newErrors: Record<string, string> = {};
    if (!customerName.trim()) newErrors.customerName = 'Customer name is required';
    if (!customerEmail.trim()) newErrors.customerEmail = 'Customer email is required';
    if (!/\S+@\S+\.\S+/.test(customerEmail)) newErrors.customerEmail = 'Invalid email address';
    if (items.length === 0) newErrors.items = 'At least one item is required';
    items.forEach((item, i) => {
      if (!item.product_id) newErrors[`item_${i}_product`] = 'Select a product';
      if (item.quantity < 1) newErrors[`item_${i}_qty`] = 'Quantity must be at least 1';
    });
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!validate()) return;
    await onSubmit({
      customer_name: customerName,
      customer_email: customerEmail,
      notes: notes || undefined,
      items,
    });
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-5">
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label className={labelClass}>Customer Name *</label>
          <input
            value={customerName}
            onChange={(e) => setCustomerName(e.target.value)}
            placeholder="John Doe"
            className={inputClass}
          />
          {errors.customerName && <p className={errorClass}>{errors.customerName}</p>}
        </div>
        <div>
          <label className={labelClass}>Customer Email *</label>
          <input
            type="email"
            value={customerEmail}
            onChange={(e) => setCustomerEmail(e.target.value)}
            placeholder="john@example.com"
            className={inputClass}
          />
          {errors.customerEmail && <p className={errorClass}>{errors.customerEmail}</p>}
        </div>
      </div>

      <div>
        <div className="flex items-center justify-between mb-2">
          <label className={labelClass.replace(' mb-1', '')}>Order Items *</label>
          <button
            type="button"
            onClick={() => setItems((prev) => [...prev, emptyItem()])}
            className="flex items-center gap-1 text-xs font-medium text-primary-600 hover:text-primary-700"
          >
            <Plus size={14} /> Add Item
          </button>
        </div>

        <div className="space-y-2">
          {items.map((item, index) => (
            <div key={index} className="flex gap-2 items-start bg-gray-50 p-3 rounded-lg">
              <div className="flex-1">
                <select
                  value={item.product_id}
                  onChange={(e) => updateItem(index, 'product_id', Number(e.target.value))}
                  className={inputClass}
                >
                  <option value={0}>Select product</option>
                  {products.map((p) => (
                    <option key={p.id} value={p.id}>
                      {p.name} (SKU: {p.sku}) – ${p.price}
                    </option>
                  ))}
                </select>
                {errors[`item_${index}_product`] && (
                  <p className={errorClass}>{errors[`item_${index}_product`]}</p>
                )}
              </div>
              <div className="w-24">
                <input
                  type="number"
                  value={item.quantity}
                  onChange={(e) => updateItem(index, 'quantity', Number(e.target.value))}
                  min={1}
                  placeholder="Qty"
                  className={inputClass}
                />
                {errors[`item_${index}_qty`] && (
                  <p className={errorClass}>{errors[`item_${index}_qty`]}</p>
                )}
              </div>
              <div className="w-28">
                <input
                  type="number"
                  value={item.unit_price}
                  onChange={(e) => updateItem(index, 'unit_price', Number(e.target.value))}
                  min={0}
                  step={0.01}
                  placeholder="Price"
                  className={inputClass}
                />
              </div>
              <div className="w-24 pt-2 text-sm font-medium text-gray-700 text-right">
                ${(item.quantity * item.unit_price).toFixed(2)}
              </div>
              {items.length > 1 && (
                <button
                  type="button"
                  onClick={() => removeItem(index)}
                  className="p-2 text-gray-400 hover:text-red-500 transition-colors"
                >
                  <Trash2 size={16} />
                </button>
              )}
            </div>
          ))}
        </div>
        {errors.items && <p className={errorClass}>{errors.items}</p>}

        <div className="flex justify-end mt-3 text-base font-semibold text-gray-800">
          Total: ${total.toFixed(2)}
        </div>
      </div>

      <div>
        <label className={labelClass}>Notes</label>
        <textarea
          value={notes}
          onChange={(e) => setNotes(e.target.value)}
          rows={2}
          placeholder="Optional order notes"
          className={inputClass}
        />
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
          Create Order
        </button>
      </div>
    </form>
  );
};

export default OrderForm;
