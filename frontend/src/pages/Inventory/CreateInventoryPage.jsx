import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useNavigate } from 'react-router-dom'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { PlusCircle, ArrowLeft } from 'lucide-react'

import { inventoryApi } from '../../api/inventoryApi'
import Button from '../../components/UI/Button'
import Input from '../../components/UI/Input'

const schema = z.object({
  product_id: z
    .string()
    .min(1, 'Product ID is required')
    .refine((v) => !isNaN(Number(v)) && Number(v) > 0, 'Must be a valid product ID'),
  quantity: z
    .string()
    .min(1, 'Quantity is required')
    .refine((v) => !isNaN(Number(v)) && Number(v) >= 0, 'Must be 0 or greater'),
  location: z.string().optional(),
  notes: z.string().optional(),
})

export default function CreateInventoryPage() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm({
    resolver: zodResolver(schema),
    defaultValues: { product_id: '', quantity: '', location: '', notes: '' },
  })

  const mutation = useMutation({
    mutationFn: (data) => inventoryApi.createInventory(data),
    onSuccess: () => {
      toast.success('Inventory record created!')
      queryClient.invalidateQueries({ queryKey: ['inventory'] })
      navigate('/inventory')
    },
    onError: (err) => {
      const data = err?.response?.data
      const msg =
        data?.message ??
        (data?.errors ? Object.values(data.errors).flat().join(' ') : null) ??
        'Failed to create inventory record'
      toast.error(msg)
    },
  })

  function onSubmit(values) {
    mutation.mutate({
      product_id: Number(values.product_id),
      quantity: Number(values.quantity),
      location: values.location || undefined,
      notes: values.notes || undefined,
    })
  }

  return (
    <div className="max-w-2xl mx-auto space-y-6">
      <div className="flex items-center gap-4">
        <Button variant="ghost" size="sm" onClick={() => navigate('/inventory')}>
          <ArrowLeft className="w-4 h-4" />
          Back
        </Button>
        <div>
          <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <PlusCircle className="w-6 h-6 text-yellow-600" />
            Create Inventory Record
          </h1>
          <p className="mt-1 text-sm text-gray-500">Add initial stock for a product</p>
        </div>
      </div>

      <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <form onSubmit={handleSubmit(onSubmit)} noValidate className="space-y-5">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <Input
              label="Product ID"
              name="product_id"
              type="number"
              min="1"
              placeholder="e.g. 42"
              error={errors.product_id?.message}
              helperText="Enter the numeric ID of the product"
              {...register('product_id')}
            />
            <Input
              label="Initial Quantity"
              name="quantity"
              type="number"
              min="0"
              placeholder="0"
              error={errors.quantity?.message}
              {...register('quantity')}
            />
          </div>

          <Input
            label="Location"
            name="location"
            type="text"
            placeholder="e.g. Warehouse A, Shelf B3"
            error={errors.location?.message}
            {...register('location')}
          />

          <Input
            label="Notes"
            name="notes"
            type="textarea"
            rows={3}
            placeholder="Optional notes about this stock record…"
            error={errors.notes?.message}
            {...register('notes')}
          />

          <div className="flex justify-end gap-3 pt-2">
            <Button variant="secondary" type="button" onClick={() => navigate('/inventory')}>
              Cancel
            </Button>
            <Button variant="primary" type="submit" loading={mutation.isPending}>
              <PlusCircle className="w-4 h-4" />
              Create Record
            </Button>
          </div>
        </form>
      </div>
    </div>
  )
}
