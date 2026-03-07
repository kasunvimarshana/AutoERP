import { useState } from 'react'
import { useForm, useFieldArray } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useNavigate } from 'react-router-dom'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { PlusCircle, Trash2, ArrowLeft, ShoppingCart } from 'lucide-react'

import { ordersApi } from '../../api/ordersApi'
import Button from '../../components/UI/Button'
import Input from '../../components/UI/Input'

const lineItemSchema = z.object({
  product_id: z.string().min(1, 'Required').refine((v) => !isNaN(Number(v)) && Number(v) > 0, 'Invalid ID'),
  quantity: z.string().min(1, 'Required').refine((v) => !isNaN(Number(v)) && Number(v) > 0, 'Must be > 0'),
  unit_price: z.string().min(1, 'Required').refine((v) => !isNaN(Number(v)) && Number(v) >= 0, 'Invalid price'),
})

const schema = z.object({
  customer_name: z.string().min(1, 'Customer name is required'),
  customer_email: z.string().min(1, 'Email is required').email('Invalid email'),
  notes: z.string().optional(),
  items: z.array(lineItemSchema).min(1, 'Add at least one item'),
})

export default function CreateOrderPage() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const { register, handleSubmit, control, watch, formState: { errors } } = useForm({
    resolver: zodResolver(schema),
    defaultValues: {
      customer_name: '', customer_email: '', notes: '',
      items: [{ product_id: '', quantity: '', unit_price: '' }],
    },
  })

  const { fields, append, remove } = useFieldArray({ control, name: 'items' })
  const watchedItems = watch('items')

  const orderTotal = watchedItems.reduce((sum, item) => {
    const qty = Number(item.quantity) || 0
    const price = Number(item.unit_price) || 0
    return sum + qty * price
  }, 0)

  const mutation = useMutation({
    mutationFn: (data) => ordersApi.createOrder(data),
    onSuccess: () => {
      toast.success('Order created successfully!')
      queryClient.invalidateQueries({ queryKey: ['orders'] })
      navigate('/orders')
    },
    onError: (err) => {
      toast.error(err?.response?.data?.message ?? 'Failed to create order')
    },
  })

  function onSubmit(values) {
    mutation.mutate({
      customer_name: values.customer_name,
      customer_email: values.customer_email,
      notes: values.notes || undefined,
      items: values.items.map((i) => ({
        product_id: Number(i.product_id),
        quantity: Number(i.quantity),
        unit_price: Number(i.unit_price),
      })),
    })
  }

  return (
    <div className="max-w-3xl mx-auto space-y-6">
      <div className="flex items-center gap-4">
        <Button variant="ghost" size="sm" onClick={() => navigate('/orders')}><ArrowLeft className="w-4 h-4" />Back</Button>
        <div>
          <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2"><ShoppingCart className="w-6 h-6 text-purple-600" />Create Order</h1>
          <p className="mt-1 text-sm text-gray-500">Place a new customer order</p>
        </div>
      </div>

      <form onSubmit={handleSubmit(onSubmit)} noValidate className="space-y-6">
        {/* Customer Info */}
        <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-5">
          <h2 className="text-base font-semibold text-gray-800">Customer Information</h2>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <Input label="Customer Name" name="customer_name" type="text" placeholder="Jane Smith" error={errors.customer_name?.message} {...register('customer_name')} />
            <Input label="Customer Email" name="customer_email" type="email" placeholder="jane@example.com" error={errors.customer_email?.message} {...register('customer_email')} />
          </div>
          <Input label="Notes" name="notes" type="textarea" rows={2} placeholder="Order notes (optional)…" error={errors.notes?.message} {...register('notes')} />
        </div>

        {/* Line Items */}
        <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-4">
          <div className="flex items-center justify-between">
            <h2 className="text-base font-semibold text-gray-800">Line Items</h2>
            <Button variant="secondary" size="sm" type="button" onClick={() => append({ product_id: '', quantity: '', unit_price: '' })}>
              <PlusCircle className="w-4 h-4" />Add Item
            </Button>
          </div>
          {errors.items?.root && <p className="text-xs text-red-600">{errors.items.root.message}</p>}
          {errors.items?.message && <p className="text-xs text-red-600">{errors.items.message}</p>}

          <div className="space-y-3">
            {fields.map((field, idx) => (
              <div key={field.id} className="flex gap-3 items-start p-3 bg-gray-50 rounded-lg">
                <Input label={idx === 0 ? 'Product ID' : ''} name={`items.${idx}.product_id`} type="number" min="1" placeholder="ID" error={errors.items?.[idx]?.product_id?.message} className="flex-1" {...register(`items.${idx}.product_id`)} />
                <Input label={idx === 0 ? 'Qty' : ''} name={`items.${idx}.quantity`} type="number" min="1" placeholder="1" error={errors.items?.[idx]?.quantity?.message} className="w-24" {...register(`items.${idx}.quantity`)} />
                <Input label={idx === 0 ? 'Unit Price' : ''} name={`items.${idx}.unit_price`} type="number" step="0.01" min="0" placeholder="0.00" error={errors.items?.[idx]?.unit_price?.message} className="w-32" {...register(`items.${idx}.unit_price`)} />
                <div className={idx === 0 ? 'mt-6' : 'mt-0'}>
                  <Button variant="danger" size="sm" type="button" disabled={fields.length === 1} onClick={() => remove(idx)}><Trash2 className="w-4 h-4" /></Button>
                </div>
              </div>
            ))}
          </div>

          <div className="flex justify-end pt-2 border-t border-gray-200">
            <p className="text-base font-bold text-gray-900">Order Total: <span className="text-blue-600">${orderTotal.toFixed(2)}</span></p>
          </div>
        </div>

        <div className="flex justify-end gap-3">
          <Button variant="secondary" type="button" onClick={() => navigate('/orders')}>Cancel</Button>
          <Button variant="primary" type="submit" loading={mutation.isPending}><ShoppingCart className="w-4 h-4" />Create Order</Button>
        </div>
      </form>
    </div>
  )
}
