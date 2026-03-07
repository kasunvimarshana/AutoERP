import React from 'react';
import { useQuery } from '@tanstack/react-query';
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  LineChart,
  Line,
} from 'recharts';
import { Package, AlertTriangle, ShoppingCart, DollarSign, TrendingUp, ArrowUpRight } from 'lucide-react';
import { dashboardApi } from '../../api/dashboard';
import Card from '../../components/ui/Card';
import Badge from '../../components/ui/Badge';
import Spinner from '../../components/ui/Spinner';
import { formatCurrency, formatDate, getOrderStatusVariant, formatStatus } from '../../utils/formatters';
import { DashboardStats } from '../../types';

const FALLBACK_STATS: DashboardStats = {
  total_products: 0,
  low_stock_items: 0,
  pending_orders: 0,
  total_revenue: 0,
  recent_orders: [],
  inventory_alerts: [],
};

const MOCK_CHART_DATA = [
  { month: 'Jan', orders: 40, revenue: 12400 },
  { month: 'Feb', orders: 55, revenue: 17300 },
  { month: 'Mar', orders: 48, revenue: 15200 },
  { month: 'Apr', orders: 70, revenue: 22100 },
  { month: 'May', orders: 65, revenue: 20500 },
  { month: 'Jun', orders: 90, revenue: 28900 },
];

interface StatCardProps {
  title: string;
  value: string | number;
  icon: React.ElementType;
  color: string;
  bg: string;
  change?: string;
}

const StatCard: React.FC<StatCardProps> = ({ title, value, icon: Icon, color, bg, change }) => (
  <div className="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
    <div className="flex items-center justify-between">
      <div>
        <p className="text-sm text-gray-500 font-medium">{title}</p>
        <p className="text-2xl font-bold text-gray-900 mt-1">{value}</p>
        {change && (
          <div className="flex items-center gap-1 mt-1">
            <ArrowUpRight className="h-3 w-3 text-green-500" />
            <span className="text-xs text-green-600 font-medium">{change} vs last month</span>
          </div>
        )}
      </div>
      <div className={`w-12 h-12 ${bg} rounded-xl flex items-center justify-center`}>
        <Icon className={`h-6 w-6 ${color}`} />
      </div>
    </div>
  </div>
);

const DashboardPage: React.FC = () => {
  const { data: stats, isLoading, isError } = useQuery({
    queryKey: ['dashboard', 'stats'],
    queryFn: dashboardApi.stats,
    retry: false,
  });

  const displayStats = stats || FALLBACK_STATS;

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <Spinner size="lg" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p className="text-sm text-gray-500 mt-1">Overview of your inventory system</p>
      </div>

      {isError && (
        <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-yellow-800">
          Could not load live stats — showing placeholder data. Ensure the backend is running.
        </div>
      )}

      <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <StatCard
          title="Total Products"
          value={displayStats.total_products}
          icon={Package}
          color="text-blue-600"
          bg="bg-blue-50"
          change="+12%"
        />
        <StatCard
          title="Low Stock Alerts"
          value={displayStats.low_stock_items}
          icon={AlertTriangle}
          color="text-orange-600"
          bg="bg-orange-50"
        />
        <StatCard
          title="Pending Orders"
          value={displayStats.pending_orders}
          icon={ShoppingCart}
          color="text-purple-600"
          bg="bg-purple-50"
          change="+5%"
        />
        <StatCard
          title="Total Revenue"
          value={formatCurrency(displayStats.total_revenue)}
          icon={DollarSign}
          color="text-green-600"
          bg="bg-green-50"
          change="+18%"
        />
      </div>

      <div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <Card title="Monthly Orders" subtitle="Orders placed per month">
          <div className="h-56">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={MOCK_CHART_DATA} margin={{ top: 4, right: 4, left: -20, bottom: 0 }}>
                <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                <XAxis dataKey="month" tick={{ fontSize: 12 }} />
                <YAxis tick={{ fontSize: 12 }} />
                <Tooltip />
                <Bar dataKey="orders" fill="#3b82f6" radius={[4, 4, 0, 0]} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </Card>

        <Card title="Revenue Trend" subtitle="Monthly revenue in USD">
          <div className="h-56">
            <ResponsiveContainer width="100%" height="100%">
              <LineChart data={MOCK_CHART_DATA} margin={{ top: 4, right: 4, left: -20, bottom: 0 }}>
                <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                <XAxis dataKey="month" tick={{ fontSize: 12 }} />
                <YAxis tick={{ fontSize: 12 }} />
                <Tooltip formatter={(v: any) => formatCurrency(v)} />
                <Line
                  type="monotone"
                  dataKey="revenue"
                  stroke="#10b981"
                  strokeWidth={2}
                  dot={{ r: 4, fill: '#10b981' }}
                />
              </LineChart>
            </ResponsiveContainer>
          </div>
        </Card>
      </div>

      <div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <Card title="Recent Orders" action={<TrendingUp className="h-4 w-4 text-gray-400" />}>
          {displayStats.recent_orders.length === 0 ? (
            <p className="text-sm text-gray-400 text-center py-8">No recent orders</p>
          ) : (
            <div className="space-y-3">
              {displayStats.recent_orders.slice(0, 5).map((order) => (
                <div
                  key={order.id}
                  className="flex items-center justify-between py-2.5 border-b border-gray-50 last:border-0"
                >
                  <div>
                    <p className="text-sm font-medium text-gray-800">{order.order_number}</p>
                    <p className="text-xs text-gray-500">{order.customer_name} · {formatDate(order.created_at)}</p>
                  </div>
                  <div className="text-right">
                    <Badge variant={getOrderStatusVariant(order.status)}>
                      {formatStatus(order.status)}
                    </Badge>
                    <p className="text-sm font-semibold text-gray-800 mt-1">
                      {formatCurrency(order.total_amount)}
                    </p>
                  </div>
                </div>
              ))}
            </div>
          )}
        </Card>

        <Card title="Inventory Alerts" subtitle="Items below reorder level">
          {displayStats.inventory_alerts.length === 0 ? (
            <p className="text-sm text-gray-400 text-center py-8">No inventory alerts</p>
          ) : (
            <div className="space-y-3">
              {displayStats.inventory_alerts.slice(0, 5).map((item) => (
                <div
                  key={item.id}
                  className="flex items-center justify-between py-2.5 border-b border-gray-50 last:border-0"
                >
                  <div>
                    <p className="text-sm font-medium text-gray-800">
                      {item.product?.name || `Product #${item.product_id}`}
                    </p>
                    <p className="text-xs text-gray-500">{item.warehouse_location}</p>
                  </div>
                  <div className="text-right">
                    <Badge variant={item.quantity === 0 ? 'danger' : 'warning'}>
                      {item.quantity === 0 ? 'Out of stock' : `${item.quantity} left`}
                    </Badge>
                    <p className="text-xs text-gray-400 mt-1">Reorder at {item.reorder_level}</p>
                  </div>
                </div>
              ))}
            </div>
          )}
        </Card>
      </div>
    </div>
  );
};

export default DashboardPage;
