import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useNavigate } from 'react-router-dom'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { PlusCircle, ArrowLeft } from 'lucide-react'

import { productsApi } from '../../api/productsApi'
import Button from '../../components/UI/Button'
import Input from '../../components/UI/Input'

const schema = z.object({
  name: z.string().min(1, 'Product name is required'),
  sku: z.string().min(1, 'SKU is required'),
  description: z.string().optional(),
  price: z
    .string()
    .min(1, 'Price is required')
    .refine((v) => !isNaN(Number(v)) && Number(v) >= 0, 'Must be a valid price'),
  cost_price: z
    .string()
    .optional()
    .refine((v) => !v || (!isNaN(Number(v)) && Number(v) >= 0), 'Must be a valid price'),
  category: z.string().optional(),
  unit: z.string().optional(),
  is_active: z.boolean().default(true),
})

export default function CreateProductPage() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm({
    resolver: zodResolver(schema),
    defaultValues: {
      name: '',
      sku: '',
      description: '',
      price: '',
      cost_price: '',
      category: '',
      unit: '',
      is_active: true,
    },
  })

  const mutation = useMutation({
    mutationFn: (data) => productsApi.createProduct(data),
    onSuccess: () => {
      toast.success('Product created successfully!')
      queryClient.invalidateQueries({ queryKey: ['products'] })
      navigate('/products')
    },
    onError: (err) => {
      const data = err?.response?.data
      const msg =
        data?.message ??
        (data?.errors ? Object.values(data.errors).flat().join(' ') : null) ??
        'Failed to create product'
      toast.error(msg)
    },
  })

  function onSubmit(values) {
    mutation.mutate({
      ...values,
      price: Number(values.price),
      cost_price: values.cost_price ? Number(values.cost_price) : undefined,
    })
  }

  return (
    <div className="max-w-2xl mx-auto space-y-6">
      <div className="flex items-center gap-4">
        <Button variant="ghost" size="sm" onClick={() => navigate('/products')}>
          <ArrowLeft className="w-4 h-4" />
          Back
        </Button>
        <div>
          <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <PlusCircle className="w-6 h-6 text-green-600" />
            Create Product
          </h1>
          <p className="mt-1 text-sm text-gray-500">Add a new product to your catalog</p>
        </div>
      </div>

      <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <form onSubmit={handleSubmit(onSubmit)} noValidate className="space-y-5">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <Input
              label="Product Name"
              name="name"
              type="text"
              placeholder="Wireless Mouse"
              error={errors.name?.message}
              {...register('name')}
            />
            <Input
              label="SKU"
              name="sku"
              type="text"
              placeholder="WM-001"
              error={errors.sku?.message}
              {...register('sku')}
            />
          </div>

          <Input
            label="Description"
            name="description"
            type="textarea"
            rows={3}
            placeholder="Brief product description…"
            error={errors.description?.message}
            {...register('description')}
          />

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <Input
              label="Price ($)"
              name="price"
              type="number"
              step="0.01"
              min="0"
              placeholder="0.00"
              error={errors.price?.message}
              {...register('price')}
            />
            <Input
              label="Cost Price ($)"
              name="cost_price"
              type="number"
              step="0.01"
              min="0"
              placeholder="0.00"
              error={errors.cost_price?.message}
              {...register('cost_price')}
            />
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <Input
              label="Category"
              name="category"
              type="text"
              placeholder="Electronics"
              error={errors.category?.message}
              {...register('category')}
            />
            <Input
              label="Unit"
              name="unit"
              type="text"
              placeholder="pcs, kg, box…"
              error={errors.unit?.message}
              {...register('unit')}
            />
          </div>

          <label className="flex items-center gap-3 cursor-pointer select-none">
            <input
              type="checkbox"
              className="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
              {...register('is_active')}
            />
            <span className="text-sm font-medium text-gray-700">Active (visible in catalog)</span>
          </label>

          <div className="flex justify-end gap-3 pt-2">
            <Button variant="secondary" type="button" onClick={() => navigate('/products')}>
              Cancel
            </Button>
            <Button variant="primary" type="submit" loading={mutation.isPending}>
              <PlusCircle className="w-4 h-4" />
              Create Product
            </Button>
          </div>
        </form>
      </div>
    </div>
  )
}
