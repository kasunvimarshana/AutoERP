import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useNavigate, useSearchParams } from 'react-router-dom'
import { format } from 'date-fns'
import toast from 'react-hot-toast'
import { PlusCircle, Eye, XCircle, ShoppingCart } from 'lucide-react'

import { ordersApi } from '../../api/ordersApi'
import SearchInput from '../../components/UI/SearchInput'
import Select from '../../components/UI/Select'
import Table from '../../components/UI/Table'
import Badge from '../../components/UI/Badge'
import Button from '../../components/UI/Button'
import Pagination from '../../components/UI/Pagination'

const STATUS_OPTIONS = [
  { value: '', label: 'All Statuses' },
  { value: 'pending', label: 'Pending' },
  { value: 'processing', label: 'Processing' },
  { value: 'shipped', label: 'Shipped' },
  { value: 'delivered', label: 'Delivered' },
  { value: 'cancelled', label: 'Cancelled' },
]

const STATUS_VARIANT = {
  pending: 'warning',
  processing: 'info',
  shipped: 'info',
  delivered: 'success',
  cancelled: 'danger',
}

const CANCELLABLE = new Set(['pending', 'processing'])

export default function OrdersPage() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [searchParams, setSearchParams] = useSearchParams()

  const status = searchParams.get('status') ?? ''
  const search = searchParams.get('search') ?? ''
  const page = Number(searchParams.get('page') ?? 1)

  const [cancellingId, setCancellingId] = useState(null)

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
    queryKey: ['orders', { status, search, page, per_page: 15 }],
    queryFn: () => ordersApi.getOrders({ status, search, page, per_page: 15 }),
    keepPreviousData: true,
  })

  const orders = data?.data?.data ?? data?.data ?? []
  const meta = data?.data?.meta ?? {}
  const totalPages = meta.last_page ?? meta.total_pages ?? 1
  const totalItems = meta.total ?? orders.length

  const cancelMutation = useMutation({
    mutationFn: (id) => ordersApi.cancelOrder(id),
    onSuccess: () => {
      toast.success('Order cancelled')
      queryClient.invalidateQueries({ queryKey: ['orders'] })
      setCancellingId(null)
    },
    onError: (err) => {
      toast.error(err?.response?.data?.message ?? 'Failed to cancel order')
      setCancellingId(null)
    },
  })

  function handleCancel(order) {
    if (!window.confirm(`Cancel order #${order.id}? This action cannot be undone.`)) return
    setCancellingId(order.id)
    cancelMutation.mutate(order.id)
  }

  const columns = [
    {
      key: 'id',
      label: 'Order #',
      sortable: true,
      render: (val) => <span className="font-mono font-semibold">#{val}</span>,
    },
    {
      key: 'customer_name',
      label: 'Customer Name',
      render: (val, row) => val ?? row.customer?.name ?? '—',
    },
    {
      key: 'status',
      label: 'Status',
      render: (val) => (
        <Badge variant={STATUS_VARIANT[val] ?? 'default'}>{val ?? '—'}</Badge>
      ),
    },
    {
      key: 'items_count',
      label: 'Items',
      render: (val, row) =>
        val ?? row.order_items?.length ?? row.items?.length ?? '—',
    },
    {
      key: 'total',
      label: 'Total',
      sortable: true,
      render: (val, row) => {
        const amount = val ?? row.total_amount ?? row.grand_total ?? 0
        return `$${Number(amount).toFixed(2)}`
      },
    },
    {
      key: 'created_at',
      label: 'Created At',
      sortable: true,
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
            onClick={() => navigate(`/orders/${row.id}`)}
            title="View order"
          >
            <Eye className="w-4 h-4" />
          </Button>
          {CANCELLABLE.has(row.status) && (
            <Button
              variant="danger"
              size="sm"
              loading={cancellingId === row.id}
              onClick={() => handleCancel(row)}
              title="Cancel order"
            >
              <XCircle className="w-4 h-4" />
            </Button>
          )}
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
            <ShoppingCart className="w-6 h-6 text-purple-600" />
            Orders
          </h1>
          <p className="mt-1 text-sm text-gray-500">Manage customer orders</p>
        </div>
        <Button variant="primary" onClick={() => navigate('/orders/create')}>
          <PlusCircle className="w-4 h-4" />
          Create Order
        </Button>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <div className="flex flex-wrap gap-3">
          <SearchInput
            value={search}
            onChange={(v) => updateParam('search', v)}
            placeholder="Search orders…"
            className="flex-1 min-w-[200px]"
          />
          <Select
            name="status"
            value={status}
            options={STATUS_OPTIONS}
            onChange={(e) => updateParam('status', e.target.value)}
            className="w-44"
          />
        </div>
      </div>

      {/* Table */}
      <div className="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <Table
          columns={columns}
          data={orders}
          loading={isLoading}
          emptyMessage="No orders found. Try adjusting your filters."
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
    </div>
  )
}
