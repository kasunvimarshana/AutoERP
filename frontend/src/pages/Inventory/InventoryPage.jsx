import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useNavigate, useSearchParams } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { format } from 'date-fns'
import toast from 'react-hot-toast'
import { PlusCircle, Pencil, Trash2, Warehouse, SlidersHorizontal } from 'lucide-react'

import { inventoryApi } from '../../api/inventoryApi'
import SearchInput from '../../components/UI/SearchInput'
import Table from '../../components/UI/Table'
import Badge from '../../components/UI/Badge'
import Button from '../../components/UI/Button'
import Input from '../../components/UI/Input'
import Pagination from '../../components/UI/Pagination'
import Modal from '../../components/UI/Modal'

const adjustSchema = z.object({
  adjustment: z
    .string()
    .min(1, 'Adjustment is required')
    .refine((v) => !isNaN(Number(v)), 'Must be a number'),
  reason: z.string().min(1, 'Reason is required'),
})

export default function InventoryPage() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [searchParams, setSearchParams] = useSearchParams()

  const search = searchParams.get('search') ?? ''
  const page = Number(searchParams.get('page') ?? 1)

  const [deletingId, setDeletingId] = useState(null)
  const [adjustItem, setAdjustItem] = useState(null)

  function updateParam(key, value) {
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev)
      if (value) {
        next.set(key, value)
      } else {
        next.delete(key)
      }
      if (key !== 'page') next.set('page', '1')
      return next
    })
  }

  const { data, isLoading } = useQuery({
    queryKey: ['inventory', { search, page, per_page: 15 }],
    queryFn: () => inventoryApi.getInventoryItems({ search, page, per_page: 15 }),
    keepPreviousData: true,
  })

  const items = data?.data?.data ?? data?.data ?? []
  const meta = data?.data?.meta ?? {}
  const totalPages = meta.last_page ?? meta.total_pages ?? 1
  const totalItems = meta.total ?? items.length

  const deleteMutation = useMutation({
    mutationFn: (id) => inventoryApi.deleteInventory(id),
    onSuccess: () => {
      toast.success('Inventory record deleted')
      queryClient.invalidateQueries({ queryKey: ['inventory'] })
      setDeletingId(null)
    },
    onError: (err) => {
      toast.error(err?.response?.data?.message ?? 'Failed to delete record')
      setDeletingId(null)
    },
  })

  const adjustMutation = useMutation({
    mutationFn: ({ id, data }) => inventoryApi.adjustInventory(id, data),
    onSuccess: () => {
      toast.success('Stock adjusted successfully!')
      queryClient.invalidateQueries({ queryKey: ['inventory'] })
      setAdjustItem(null)
    },
    onError: (err) => {
      toast.error(err?.response?.data?.message ?? 'Failed to adjust stock')
    },
  })

  const {
    register: adjustReg,
    handleSubmit: adjustSubmit,
    reset: adjustReset,
    formState: { errors: adjustErrors },
  } = useForm({
    resolver: zodResolver(adjustSchema),
    defaultValues: { adjustment: '', reason: '' },
  })

  function handleDelete(item) {
    if (!window.confirm(`Delete inventory record #${item.id}? This cannot be undone.`)) return
    setDeletingId(item.id)
    deleteMutation.mutate(item.id)
  }

  function openAdjust(item) {
    setAdjustItem(item)
    adjustReset({ adjustment: '', reason: '' })
  }

  function onAdjustSubmit(values) {
    adjustMutation.mutate({
      id: adjustItem.id,
      data: { adjustment: Number(values.adjustment), reason: values.reason },
    })
  }

  const columns = [
    { key: 'id', label: 'ID', sortable: true },
    {
      key: 'product',
      label: 'Product Name',
      render: (val, row) =>
        val?.name ?? row.product_name ?? `Product #${row.product_id}` ?? '—',
    },
    {
      key: 'sku',
      label: 'SKU',
      render: (val, row) => val ?? row.product?.sku ?? '—',
    },
    {
      key: 'quantity',
      label: 'Quantity',
      render: (val) => (
        <span
          className={`font-semibold ${
            Number(val) <= 10 ? 'text-red-600' : 'text-gray-900'
          }`}
        >
          {val ?? 0}
        </span>
      ),
    },
    { key: 'location', label: 'Location', render: (val) => val ?? '—' },
    {
      key: 'updated_at',
      label: 'Last Updated',
      render: (val) => (val ? format(new Date(val), 'MMM d, yyyy') : '—'),
    },
    {
      key: 'actions',
      label: 'Actions',
      render: (_, row) => (
        <div className="flex items-center gap-2">
          <Button
            variant="ghost"
            size="sm"
            onClick={() => openAdjust(row)}
            title="Adjust stock"
          >
            <SlidersHorizontal className="w-4 h-4" />
          </Button>
          <Button
            variant="ghost"
            size="sm"
            onClick={() => navigate(`/inventory/${row.id}/edit`)}
            title="Edit record"
          >
            <Pencil className="w-4 h-4" />
          </Button>
          <Button
            variant="danger"
            size="sm"
            loading={deletingId === row.id}
            onClick={() => handleDelete(row)}
            title="Delete record"
          >
            <Trash2 className="w-4 h-4" />
          </Button>
        </div>
      ),
    },
  ]

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <Warehouse className="w-6 h-6 text-yellow-600" />
            Inventory
          </h1>
          <p className="mt-1 text-sm text-gray-500">Track and manage stock levels</p>
        </div>
        <Button variant="primary" onClick={() => navigate('/inventory/create')}>
          <PlusCircle className="w-4 h-4" />
          Create Inventory
        </Button>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <SearchInput
          value={search}
          onChange={(v) => updateParam('search', v)}
          placeholder="Search by product or location…"
          className="max-w-sm"
        />
      </div>

      {/* Table */}
      <div className="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <Table
          columns={columns}
          data={items}
          loading={isLoading}
          emptyMessage="No inventory records found."
          // Highlight low stock rows
          rowClassName={(row) =>
            Number(row.quantity) <= 10 ? 'bg-red-50 hover:bg-red-100' : ''
          }
        />
        {!isLoading && (
          <div className="border-t border-gray-100 px-4">
            <Pagination
              currentPage={page}
              totalPages={totalPages}
              totalItems={totalItems}
              perPage={15}
              onPageChange={(p) => updateParam('page', String(p))}
            />
          </div>
        )}
      </div>

      {/* Adjust Stock Modal */}
      <Modal
        isOpen={!!adjustItem}
        onClose={() => setAdjustItem(null)}
        title={`Adjust Stock — ${
          adjustItem?.product?.name ??
          adjustItem?.product_name ??
          `Record #${adjustItem?.id}`
        }`}
        size="sm"
      >
        <form onSubmit={adjustSubmit(onAdjustSubmit)} noValidate className="space-y-4">
          {adjustItem && (
            <p className="text-sm text-gray-500">
              Current quantity:{' '}
              <span className="font-semibold text-gray-800">{adjustItem.quantity}</span>
            </p>
          )}

          <Input
            label="Adjustment"
            name="adjustment"
            type="number"
            placeholder="e.g. 10 or -5"
            helperText="Use positive number to add stock, negative to remove"
            error={adjustErrors.adjustment?.message}
            {...adjustReg('adjustment')}
          />

          <Input
            label="Reason"
            name="reason"
            type="text"
            placeholder="e.g. Received shipment, damaged goods…"
            error={adjustErrors.reason?.message}
            {...adjustReg('reason')}
          />

          <div className="flex justify-end gap-3 pt-1">
            <Button variant="secondary" type="button" onClick={() => setAdjustItem(null)}>
              Cancel
            </Button>
            <Button variant="primary" type="submit" loading={adjustMutation.isPending}>
              <SlidersHorizontal className="w-4 h-4" />
              Adjust Stock
            </Button>
          </div>
        </form>
      </Modal>
    </div>
  )
}
