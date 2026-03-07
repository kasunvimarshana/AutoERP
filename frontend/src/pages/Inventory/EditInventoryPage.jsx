import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useNavigate, useParams } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { Pencil, ArrowLeft } from 'lucide-react'

import { inventoryApi } from '../../api/inventoryApi'
import Button from '../../components/UI/Button'
import Input from '../../components/UI/Input'
import LoadingSpinner from '../../components/UI/LoadingSpinner'

const schema = z.object({
  quantity: z
    .string()
    .min(1, 'Quantity is required')
    .refine((v) => !isNaN(Number(v)) && Number(v) >= 0, 'Must be 0 or greater'),
  location: z.string().optional(),
  notes: z.string().optional(),
})

export default function EditInventoryPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const { data, isLoading, isError } = useQuery({
    queryKey: ['inventory', id],
    queryFn: () => inventoryApi.getInventoryById(id),
    enabled: !!id,
  })

  const item = data?.data?.data ?? data?.data ?? null

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm({
    resolver: zodResolver(schema),
    defaultValues: { quantity: '', location: '', notes: '' },
  })

  useEffect(() => {
    if (item) {
      reset({
        quantity: item.quantity != null ? String(item.quantity) : '',
        location: item.location ?? '',
        notes: item.notes ?? '',
      })
    }
  }, [item, reset])

  const mutation = useMutation({
    mutationFn: (data) => inventoryApi.updateInventory(id, data),
    onSuccess: () => {
      toast.success('Inventory record updated!')
      queryClient.invalidateQueries({ queryKey: ['inventory'] })
      navigate('/inventory')
    },
    onError: (err) => {
      const responseData = err?.response?.data
      const msg =
        responseData?.message ??
        (responseData?.errors
          ? Object.values(responseData.errors).flat().join(' ')
          : null) ??
        'Failed to update record'
      toast.error(msg)
    },
  })

  function onSubmit(values) {
    mutation.mutate({
      quantity: Number(values.quantity),
      location: values.location || undefined,
      notes: values.notes || undefined,
    })
  }

  if (isLoading) {
    return (
      <div className="flex justify-center py-20">
        <LoadingSpinner size="lg" text="Loading inventory record…" />
      </div>
    )
  }

  if (isError || !item) {
    return (
      <div className="text-center py-20">
        <p className="text-red-600 font-medium">Failed to load inventory record.</p>
        <Button variant="secondary" className="mt-4" onClick={() => navigate('/inventory')}>
          Back to Inventory
        </Button>
      </div>
    )
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
            <Pencil className="w-6 h-6 text-yellow-600" />
            Edit Inventory Record
          </h1>
          <p className="mt-1 text-sm text-gray-500">
            {item.product?.name ?? `Product #${item.product_id}`} — Record #{id}
          </p>
        </div>
      </div>

      <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        {/* Read-only product info */}
        <div className="mb-5 p-3 bg-gray-50 rounded-lg border border-gray-200 text-sm">
          <p className="font-medium text-gray-700">
            Product:{' '}
            <span className="text-gray-900">
              {item.product?.name ?? `#${item.product_id}`}
            </span>
          </p>
          {item.product?.sku && (
            <p className="mt-0.5 text-gray-500">SKU: {item.product.sku}</p>
          )}
        </div>

        <form onSubmit={handleSubmit(onSubmit)} noValidate className="space-y-5">
          <Input
            label="Quantity"
            name="quantity"
            type="number"
            min="0"
            placeholder="0"
            error={errors.quantity?.message}
            className="sm:w-1/2"
            {...register('quantity')}
          />

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
            placeholder="Optional notes…"
            error={errors.notes?.message}
            {...register('notes')}
          />

          <div className="flex justify-end gap-3 pt-2">
            <Button variant="secondary" type="button" onClick={() => navigate('/inventory')}>
              Cancel
            </Button>
            <Button variant="primary" type="submit" loading={mutation.isPending}>
              <Pencil className="w-4 h-4" />
              Save Changes
            </Button>
          </div>
        </form>
      </div>
    </div>
  )
}
