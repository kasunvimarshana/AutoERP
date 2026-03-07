import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useParams, useNavigate } from 'react-router-dom'
import { format } from 'date-fns'
import toast from 'react-hot-toast'
import { ArrowLeft, XCircle, ShoppingCart } from 'lucide-react'

import { ordersApi } from '../../api/ordersApi'
import Badge from '../../components/UI/Badge'
import Button from '../../components/UI/Button'
import LoadingSpinner from '../../components/UI/LoadingSpinner'

const STATUS_VARIANT = {
  pending: 'warning', processing: 'info', shipped: 'info',
  delivered: 'success', cancelled: 'danger',
}

export default function OrderDetailPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const { data, isLoading, isError } = useQuery({
    queryKey: ['orders', id],
    queryFn: () => ordersApi.getOrderById(id),
    enabled: !!id,
  })

  const order = data?.data?.data ?? data?.data ?? null

  const cancelMutation = useMutation({
    mutationFn: () => ordersApi.cancelOrder(id),
    onSuccess: () => {
      toast.success('Order cancelled')
      queryClient.invalidateQueries({ queryKey: ['orders'] })
      queryClient.invalidateQueries({ queryKey: ['orders', id] })
    },
    onError: (err) => toast.error(err?.response?.data?.message ?? 'Failed to cancel order'),
  })

  function handleCancel() {
    if (!window.confirm(`Cancel order #${id}?`)) return
    cancelMutation.mutate()
  }

  if (isLoading) return <div className="flex justify-center py-20"><LoadingSpinner size="lg" text="Loading order…" /></div>
  if (isError || !order) return (
    <div className="text-center py-20">
      <p className="text-red-600 font-medium">Failed to load order.</p>
      <Button variant="secondary" className="mt-4" onClick={() => navigate('/orders')}>Back to Orders</Button>
    </div>
  )

  const items = order.order_items ?? order.items ?? []
  const total = order.total ?? order.total_amount ?? order.grand_total ?? 0
  const canCancel = ['pending', 'processing'].includes(order.status)

  return (
    <div className="max-w-3xl mx-auto space-y-6">
      <div className="flex items-center gap-4">
        <Button variant="ghost" size="sm" onClick={() => navigate('/orders')}><ArrowLeft className="w-4 h-4" />Back</Button>
        <div>
          <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2"><ShoppingCart className="w-6 h-6 text-purple-600" />Order #{order.id}</h1>
          <p className="mt-1 text-sm text-gray-500">Order detail view</p>
        </div>
      </div>

      {/* Header card */}
      <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div className="space-y-2">
            <div className="flex items-center gap-3">
              <span className="text-sm text-gray-500">Status:</span>
              <Badge variant={STATUS_VARIANT[order.status] ?? 'default'}>{order.status}</Badge>
            </div>
            <p className="text-sm text-gray-500">Created: <span className="text-gray-800">{order.created_at ? format(new Date(order.created_at), 'MMM d, yyyy HH:mm') : '—'}</span></p>
            <p className="text-sm text-gray-500">Updated: <span className="text-gray-800">{order.updated_at ? format(new Date(order.updated_at), 'MMM d, yyyy HH:mm') : '—'}</span></p>
          </div>
          {canCancel && (
            <Button variant="danger" size="sm" loading={cancelMutation.isPending} onClick={handleCancel}>
              <XCircle className="w-4 h-4" />Cancel Order
            </Button>
          )}
        </div>
      </div>

      {/* Customer info */}
      <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <h2 className="text-base font-semibold text-gray-800 mb-3">Customer Information</h2>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
          <div><p className="text-gray-500">Name</p><p className="font-medium text-gray-900">{order.customer_name ?? order.customer?.name ?? '—'}</p></div>
          <div><p className="text-gray-500">Email</p><p className="font-medium text-gray-900">{order.customer_email ?? order.customer?.email ?? '—'}</p></div>
        </div>
      </div>

      {/* Line items */}
      <div className="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div className="px-6 py-4 border-b border-gray-100">
          <h2 className="text-base font-semibold text-gray-800">Line Items</h2>
        </div>
        {items.length === 0 ? (
          <p className="text-center text-gray-400 text-sm py-8">No line items</p>
        ) : (
          <table className="min-w-full divide-y divide-gray-100 text-sm">
            <thead className="bg-gray-50">
              <tr>{['Product', 'Qty', 'Unit Price', 'Line Total'].map(h => <th key={h} className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{h}</th>)}</tr>
            </thead>
            <tbody className="divide-y divide-gray-50">
              {items.map((item, i) => {
                const lineTotal = Number(item.quantity ?? 0) * Number(item.unit_price ?? item.price ?? 0)
                return (
                  <tr key={item.id ?? i} className="hover:bg-gray-50">
                    <td className="px-4 py-3 text-gray-800">{item.product?.name ?? item.product_name ?? `Product #${item.product_id}`}</td>
                    <td className="px-4 py-3 text-gray-700">{item.quantity}</td>
                    <td className="px-4 py-3 text-gray-700">${Number(item.unit_price ?? item.price ?? 0).toFixed(2)}</td>
                    <td className="px-4 py-3 font-medium text-gray-900">${lineTotal.toFixed(2)}</td>
                  </tr>
                )
              })}
            </tbody>
          </table>
        )}
        <div className="px-6 py-4 border-t border-gray-100 flex justify-end">
          <p className="text-base font-bold text-gray-900">Total: <span className="text-blue-600">${Number(total).toFixed(2)}</span></p>
        </div>
      </div>

      {order.notes && (
        <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
          <h2 className="text-base font-semibold text-gray-800 mb-2">Notes</h2>
          <p className="text-sm text-gray-700 whitespace-pre-wrap">{order.notes}</p>
        </div>
      )}
    </div>
  )
}
