import { useQueries } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { format } from 'date-fns'
import {
  Users,
  Package,
  Warehouse,
  ShoppingCart,
  AlertTriangle,
  TrendingUp,
} from 'lucide-react'

import { usersApi } from '../../api/usersApi'
import { productsApi } from '../../api/productsApi'
import { inventoryApi } from '../../api/inventoryApi'
import { ordersApi } from '../../api/ordersApi'
import StatsCard from '../../components/UI/StatsCard'
import Badge from '../../components/UI/Badge'
import LoadingSpinner from '../../components/UI/LoadingSpinner'

const ORDER_STATUS_VARIANT = {
  pending: 'warning',
  processing: 'info',
  shipped: 'info',
  delivered: 'success',
  cancelled: 'danger',
}

function getTotal(result) {
  if (!result?.data) return '—'
  const meta = result.data?.data?.meta ?? result.data?.meta
  if (meta?.total != null) return meta.total.toLocaleString()
  const list = result.data?.data?.data ?? result.data?.data ?? []
  return Array.isArray(list) ? list.length.toLocaleString() : '—'
}

function getList(result) {
  if (!result?.data) return []
  return result.data?.data?.data ?? result.data?.data ?? []
}

export default function DashboardPage() {
  const [usersQ, productsQ, inventoryQ, ordersQ, recentOrdersQ, lowStockQ] = useQueries({
    queries: [
      { queryKey: ['users', { per_page: 1 }], queryFn: () => usersApi.getUsers({ per_page: 1 }) },
      {
        queryKey: ['products', { per_page: 1 }],
        queryFn: () => productsApi.getProducts({ per_page: 1 }),
      },
      {
        queryKey: ['inventory', { per_page: 1 }],
        queryFn: () => inventoryApi.getInventoryItems({ per_page: 1 }),
      },
      { queryKey: ['orders', { per_page: 1 }], queryFn: () => ordersApi.getOrders({ per_page: 1 }) },
      {
        queryKey: ['orders', { per_page: 5, sort: 'created_at', direction: 'desc' }],
        queryFn: () =>
          ordersApi.getOrders({ per_page: 5, sort: 'created_at', direction: 'desc' }),
      },
      {
        queryKey: ['inventory', { low_stock: true, per_page: 5 }],
        queryFn: () => inventoryApi.getInventoryItems({ low_stock: true, per_page: 5 }),
      },
    ],
  })

  const recentOrders = getList(recentOrdersQ)
  const lowStockItems = getList(lowStockQ)

  return (
    <div className="space-y-8">
      {/* Page header */}
      <div>
        <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
          <TrendingUp className="w-6 h-6 text-blue-600" />
          Dashboard
        </h1>
        <p className="mt-1 text-sm text-gray-500">Overview of your inventory system</p>
      </div>

      {/* Stats grid */}
      <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
        <StatsCard
          title="Total Users"
          value={usersQ.isLoading ? '…' : getTotal(usersQ)}
          icon={Users}
          color="blue"
        />
        <StatsCard
          title="Total Products"
          value={productsQ.isLoading ? '…' : getTotal(productsQ)}
          icon={Package}
          color="green"
        />
        <StatsCard
          title="Inventory Items"
          value={inventoryQ.isLoading ? '…' : getTotal(inventoryQ)}
          icon={Warehouse}
          color="yellow"
        />
        <StatsCard
          title="Total Orders"
          value={ordersQ.isLoading ? '…' : getTotal(ordersQ)}
          icon={ShoppingCart}
          color="purple"
        />
      </div>

      <div className="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {/* Recent Orders */}
        <div className="xl:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
          <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 className="text-base font-semibold text-gray-800">Recent Orders</h2>
            <Link
              to="/orders"
              className="text-sm text-blue-600 hover:text-blue-700 hover:underline font-medium"
            >
              View all
            </Link>
          </div>

          {recentOrdersQ.isLoading ? (
            <div className="flex justify-center py-10">
              <LoadingSpinner text="Loading orders…" />
            </div>
          ) : recentOrders.length === 0 ? (
            <p className="text-center text-sm text-gray-400 py-10">No recent orders</p>
          ) : (
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-100 text-sm">
                <thead className="bg-gray-50">
                  <tr>
                    {['Order #', 'Status', 'Total', 'Created'].map((h) => (
                      <th
                        key={h}
                        className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider"
                      >
                        {h}
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-50">
                  {recentOrders.map((order) => (
                    <tr key={order.id} className="hover:bg-gray-50 transition-colors">
                      <td className="px-4 py-3 font-medium text-gray-900">
                        <Link
                          to={`/orders/${order.id}`}
                          className="hover:text-blue-600 hover:underline"
                        >
                          #{order.id}
                        </Link>
                      </td>
                      <td className="px-4 py-3">
                        <Badge variant={ORDER_STATUS_VARIANT[order.status] ?? 'default'}>
                          {order.status}
                        </Badge>
                      </td>
                      <td className="px-4 py-3 text-gray-700">
                        ${Number(order.total ?? order.total_amount ?? 0).toFixed(2)}
                      </td>
                      <td className="px-4 py-3 text-gray-500">
                        {order.created_at
                          ? format(new Date(order.created_at), 'MMM d, yyyy')
                          : '—'}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>

        {/* Low Stock Alerts */}
        <div className="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
          <div className="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
            <AlertTriangle className="w-4 h-4 text-red-500" />
            <h2 className="text-base font-semibold text-gray-800">Low Stock Alerts</h2>
          </div>

          {lowStockQ.isLoading ? (
            <div className="flex justify-center py-10">
              <LoadingSpinner text="Loading…" />
            </div>
          ) : lowStockItems.length === 0 ? (
            <p className="text-center text-sm text-gray-400 py-10">No low-stock items 🎉</p>
          ) : (
            <ul className="divide-y divide-gray-100">
              {lowStockItems.map((item) => (
                <li
                  key={item.id}
                  className="px-5 py-3 flex items-center justify-between hover:bg-red-50 transition-colors"
                >
                  <div className="min-w-0">
                    <p className="text-sm font-medium text-gray-800 truncate">
                      {item.product?.name ?? item.product_name ?? `Product #${item.product_id}`}
                    </p>
                    <p className="text-xs text-gray-400 truncate">
                      {item.product?.sku ?? item.sku ?? item.location ?? '—'}
                    </p>
                  </div>
                  <Badge variant="danger" className="ml-3 shrink-0">
                    {item.quantity} left
                  </Badge>
                </li>
              ))}
            </ul>
          )}

          {lowStockItems.length > 0 && (
            <div className="px-5 py-3 border-t border-gray-100">
              <Link
                to="/inventory"
                className="text-sm text-blue-600 hover:underline font-medium"
              >
                View inventory →
              </Link>
            </div>
          )}
        </div>
      </div>
    </div>
  )
}
