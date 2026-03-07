import { Package, Warehouse, ShoppingCart, AlertTriangle, TrendingUp, ArrowRight } from 'lucide-react';
import { Link } from 'react-router-dom';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { useProducts } from '../hooks/useProducts';
import { useInventory, useLowStock } from '../hooks/useInventory';
import { useOrders } from '../hooks/useOrders';
import LoadingSpinner from '../components/common/LoadingSpinner';
import StatusBadge from '../components/common/StatusBadge';

function StatCard({ icon: Icon, label, value, color, loading }) {
  return (
    <div className="bg-white rounded-xl p-6 shadow-sm border border-gray-100 flex items-center gap-4">
      <div className={`w-12 h-12 rounded-xl flex items-center justify-center ${color}`}>
        <Icon size={22} className="text-white" />
      </div>
      <div>
        <p className="text-sm text-gray-500">{label}</p>
        {loading ? <div className="h-7 w-16 bg-gray-100 animate-pulse rounded mt-1" /> : <p className="text-2xl font-bold text-gray-800">{value ?? '—'}</p>}
      </div>
    </div>
  );
}

const monthlyData = [
  { month: 'Jan', orders: 42 }, { month: 'Feb', orders: 58 }, { month: 'Mar', orders: 75 },
  { month: 'Apr', orders: 63 }, { month: 'May', orders: 91 }, { month: 'Jun', orders: 84 },
];

export default function Dashboard() {
  const { data: products, isLoading: loadingP } = useProducts({ page: 1, per_page: 1 });
  const { data: inventory, isLoading: loadingI } = useInventory({ page: 1, per_page: 1 });
  const { data: orders, isLoading: loadingO } = useOrders({ page: 1, per_page: 5 });
  const { data: lowStock, isLoading: loadingL } = useLowStock();

  return (
    <div className="space-y-6">
      <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <StatCard icon={Package}      label="Total Products"  value={products?.total}     color="bg-indigo-500" loading={loadingP} />
        <StatCard icon={Warehouse}    label="Inventory Items" value={inventory?.total}    color="bg-emerald-500" loading={loadingI} />
        <StatCard icon={ShoppingCart} label="Active Orders"   value={orders?.total}       color="bg-orange-500" loading={loadingO} />
        <StatCard icon={AlertTriangle} label="Low Stock Alerts" value={lowStock?.length ?? 0} color="bg-red-500" loading={loadingL} />
      </div>

      <div className="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div className="xl:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
          <div className="flex items-center justify-between mb-4">
            <h2 className="font-semibold text-gray-800 flex items-center gap-2"><TrendingUp size={18} className="text-indigo-500" /> Monthly Orders</h2>
          </div>
          <ResponsiveContainer width="100%" height={220}>
            <BarChart data={monthlyData}>
              <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
              <XAxis dataKey="month" tick={{ fontSize: 12 }} />
              <YAxis tick={{ fontSize: 12 }} />
              <Tooltip />
              <Bar dataKey="orders" fill="#6366f1" radius={[4, 4, 0, 0]} />
            </BarChart>
          </ResponsiveContainer>
        </div>

        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
          <div className="flex items-center justify-between mb-4">
            <h2 className="font-semibold text-gray-800 flex items-center gap-2"><AlertTriangle size={18} className="text-red-400" /> Low Stock</h2>
            <Link to="/inventory" className="text-xs text-indigo-600 hover:underline flex items-center gap-1">View all <ArrowRight size={12} /></Link>
          </div>
          {loadingL ? <LoadingSpinner /> : !lowStock?.length ? (
            <p className="text-sm text-gray-400 text-center py-8">No low stock items</p>
          ) : (
            <div className="space-y-3">
              {lowStock.slice(0, 6).map((item) => (
                <div key={item.id} className="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                  <div>
                    <p className="text-sm font-medium text-gray-700">{item.product_name || item.sku}</p>
                    <p className="text-xs text-gray-400">{item.warehouse_location}</p>
                  </div>
                  <span className="text-sm font-semibold text-red-500">{item.quantity} left</span>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div className="flex items-center justify-between mb-4">
          <h2 className="font-semibold text-gray-800">Recent Orders</h2>
          <Link to="/orders" className="text-xs text-indigo-600 hover:underline flex items-center gap-1">View all <ArrowRight size={12} /></Link>
        </div>
        {loadingO ? <LoadingSpinner /> : (
          <table className="w-full text-sm">
            <thead><tr className="border-b border-gray-100 text-gray-500 text-left">{['Order #','Customer','Status','Items','Total','Date'].map(h => <th key={h} className="pb-2 font-medium">{h}</th>)}</tr></thead>
            <tbody>
              {(orders?.data || orders?.items || []).slice(0, 5).map((o) => (
                <tr key={o.id} className="border-b border-gray-50 hover:bg-gray-50">
                  <td className="py-2 font-mono text-indigo-600">{o.order_number || `#${o.id}`}</td>
                  <td className="py-2">{o.customer_name || o.customer_email}</td>
                  <td className="py-2"><StatusBadge status={o.status} /></td>
                  <td className="py-2">{o.total_items ?? o.items?.length ?? '—'}</td>
                  <td className="py-2 font-medium">${Number(o.total_amount || 0).toFixed(2)}</td>
                  <td className="py-2 text-gray-400">{o.created_at ? new Date(o.created_at).toLocaleDateString() : '—'}</td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>
    </div>
  );
}
