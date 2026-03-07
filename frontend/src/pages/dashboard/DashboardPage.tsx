import React from 'react';
import { Package, ShoppingCart, AlertTriangle, TrendingUp } from 'lucide-react';
import { useQuery } from '@tanstack/react-query';
import MetricCard from '@/components/dashboard/MetricCard';
import InventoryChart from '@/components/dashboard/InventoryChart';
import StatusBadge, { orderStatusVariant } from '@/components/common/StatusBadge';
import { productsApi } from '@/api/products';
import { ordersApi } from '@/api/orders';
import { Link } from 'react-router-dom';
import LoadingSpinner from '@/components/common/LoadingSpinner';

const DashboardPage: React.FC = () => {
  const { data: productsData, isLoading: productsLoading } = useQuery({
    queryKey: ['products', { per_page: 50 }],
    queryFn: () => productsApi.list({ per_page: 50 }),
  });

  const { data: lowStockData } = useQuery({
    queryKey: ['products', 'low-stock'],
    queryFn: () => productsApi.getLowStock(),
  });

  const { data: ordersData, isLoading: ordersLoading } = useQuery({
    queryKey: ['orders', { per_page: 5, sort_by: 'created_at', sort_direction: 'desc' }],
    queryFn: () =>
      ordersApi.list({ per_page: 5, sort_by: 'created_at', sort_direction: 'desc' }),
  });

  const products = productsData?.data ?? [];
  const lowStock = lowStockData ?? [];
  const recentOrders = ordersData?.data ?? [];

  if (productsLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <LoadingSpinner size="lg" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p className="text-sm text-gray-500 mt-1">Inventory & order overview</p>
      </div>

      {/* Metric cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <MetricCard
          title="Total Products"
          value={productsData?.meta.total ?? products.length}
          subtitle="Active SKUs"
          icon={<Package size={20} />}
          color="blue"
          trend={{ value: 8, label: 'this month' }}
        />
        <MetricCard
          title="Low Stock Alerts"
          value={lowStock.length}
          subtitle="Require reorder"
          icon={<AlertTriangle size={20} />}
          color="yellow"
        />
        <MetricCard
          title="Total Orders"
          value={ordersData?.meta.total ?? 0}
          subtitle="All time"
          icon={<ShoppingCart size={20} />}
          color="green"
          trend={{ value: 12, label: 'vs last month' }}
        />
        <MetricCard
          title="Revenue"
          value={`$${(recentOrders.reduce((s, o) => s + o.total, 0)).toFixed(0)}`}
          subtitle="Recent orders"
          icon={<TrendingUp size={20} />}
          color="purple"
        />
      </div>

      {/* Charts and recent orders */}
      <div className="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div className="xl:col-span-2">
          <InventoryChart products={products} maxItems={10} />
        </div>

        {/* Low stock list */}
        <div className="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-base font-semibold text-gray-800">Low Stock Items</h3>
            <Link
              to="/inventory"
              className="text-xs text-primary-600 font-medium hover:underline"
            >
              View all →
            </Link>
          </div>
          {lowStock.length === 0 ? (
            <div className="text-center py-8 text-gray-400 text-sm">All items well stocked ✓</div>
          ) : (
            <div className="space-y-2">
              {lowStock.slice(0, 6).map((p) => (
                <div
                  key={p.id}
                  className="flex items-center justify-between py-2 border-b border-gray-50 last:border-0"
                >
                  <div className="min-w-0">
                    <p className="text-sm font-medium text-gray-700 truncate">{p.name}</p>
                    <p className="text-xs text-gray-400">{p.sku}</p>
                  </div>
                  <span className="text-xs font-semibold text-red-600 shrink-0 ml-2">
                    {p.available_quantity} left
                  </span>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>

      {/* Recent orders */}
      <div className="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
        <div className="flex items-center justify-between mb-4">
          <h3 className="text-base font-semibold text-gray-800">Recent Orders</h3>
          <Link to="/orders" className="text-xs text-primary-600 font-medium hover:underline">
            View all →
          </Link>
        </div>
        {ordersLoading ? (
          <div className="py-8 flex justify-center">
            <LoadingSpinner />
          </div>
        ) : recentOrders.length === 0 ? (
          <p className="text-center py-8 text-gray-400 text-sm">No orders yet</p>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full">
              <thead>
                <tr className="text-xs text-gray-500 uppercase tracking-wide border-b border-gray-100">
                  <th className="text-left pb-2 font-semibold">Order #</th>
                  <th className="text-left pb-2 font-semibold">Customer</th>
                  <th className="text-left pb-2 font-semibold">Status</th>
                  <th className="text-right pb-2 font-semibold">Total</th>
                  <th className="text-right pb-2 font-semibold">Date</th>
                </tr>
              </thead>
              <tbody>
                {recentOrders.map((order) => (
                  <tr key={order.id} className="border-b border-gray-50 last:border-0">
                    <td className="py-2.5 text-sm font-mono text-primary-600">
                      <Link to={`/orders/${order.id}`} className="hover:underline">
                        {order.order_number}
                      </Link>
                    </td>
                    <td className="py-2.5 text-sm text-gray-700">{order.customer_name}</td>
                    <td className="py-2.5">
                      <StatusBadge
                        label={order.status}
                        variant={orderStatusVariant(order.status)}
                        dot
                      />
                    </td>
                    <td className="py-2.5 text-sm font-medium text-gray-800 text-right">
                      ${order.total.toFixed(2)}
                    </td>
                    <td className="py-2.5 text-xs text-gray-400 text-right">
                      {new Date(order.created_at).toLocaleDateString()}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
};

export default DashboardPage;
