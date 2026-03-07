import { useQuery } from '@tanstack/react-query';
import { inventoryService } from '../services/inventoryService';
import { orderService } from '../services/orderService';
import { Package, Boxes, ShoppingCart, TrendingUp } from 'lucide-react';

interface StatCardProps {
  icon: React.ElementType;
  label: string;
  value: number | string;
  color: string;
}

function StatCard({ icon: Icon, label, value, color }: StatCardProps) {
  return (
    <div style={{
      background: '#fff', borderRadius: 10, padding: '20px 24px',
      boxShadow: '0 1px 4px rgba(0,0,0,0.06)', display: 'flex',
      alignItems: 'center', gap: 16,
    }}>
      <div style={{
        background: color + '20', borderRadius: 10, padding: 12,
        display: 'flex', alignItems: 'center', justifyContent: 'center',
      }}>
        <Icon size={24} color={color} />
      </div>
      <div>
        <p style={{ color: '#64748b', fontSize: 13, marginBottom: 2 }}>{label}</p>
        <p style={{ fontSize: 24, fontWeight: 700 }}>{value}</p>
      </div>
    </div>
  );
}

/**
 * Dashboard overview page showing key metrics.
 */
export default function DashboardPage() {
  const { data: productsData } = useQuery({
    queryKey: ['products'],
    queryFn:  () => inventoryService.listProducts(),
  });

  const { data: ordersData } = useQuery({
    queryKey: ['orders'],
    queryFn:  () => orderService.listOrders(),
  });

  const confirmedOrders = ordersData?.data?.filter(o => o.status === 'confirmed').length ?? 0;

  return (
    <div>
      <h2 style={{ fontSize: 22, fontWeight: 700, marginBottom: 24 }}>Dashboard</h2>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: 16, marginBottom: 32 }}>
        <StatCard icon={Package}     label="Total Products"    value={productsData?.total ?? '–'} color="#3b82f6" />
        <StatCard icon={Boxes}       label="Inventory Items"   value={productsData?.total ?? '–'} color="#10b981" />
        <StatCard icon={ShoppingCart} label="Total Orders"     value={ordersData?.total ?? '–'}   color="#f59e0b" />
        <StatCard icon={TrendingUp}  label="Confirmed Orders"  value={confirmedOrders}             color="#6366f1" />
      </div>

      <div style={{ background: '#fff', borderRadius: 10, padding: 24, boxShadow: '0 1px 4px rgba(0,0,0,0.06)' }}>
        <h3 style={{ fontSize: 16, fontWeight: 600, marginBottom: 16 }}>Recent Orders</h3>
        {ordersData?.data?.slice(0, 5).map(order => (
          <div key={order.id} style={{
            display: 'flex', justifyContent: 'space-between', alignItems: 'center',
            padding: '10px 0', borderBottom: '1px solid #f1f5f9',
          }}>
            <div>
              <p style={{ fontWeight: 600, fontSize: 14 }}>Order #{order.id.slice(0, 8)}</p>
              <p style={{ color: '#64748b', fontSize: 12 }}>{order.confirmed_at ? new Date(order.confirmed_at).toLocaleDateString() : '–'}</p>
            </div>
            <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
              <span style={{ fontSize: 15, fontWeight: 600 }}>${order.total_amount}</span>
              <StatusBadge status={order.status} />
            </div>
          </div>
        )) ?? <p style={{ color: '#94a3b8' }}>No orders yet.</p>}
      </div>
    </div>
  );
}

function StatusBadge({ status }: { status: string }) {
  const colors: Record<string, { bg: string; text: string }> = {
    confirmed: { bg: '#dcfce7', text: '#16a34a' },
    pending:   { bg: '#fef9c3', text: '#ca8a04' },
    cancelled: { bg: '#fee2e2', text: '#dc2626' },
    failed:    { bg: '#fef2f2', text: '#b91c1c' },
  };
  const style = colors[status] ?? { bg: '#f1f5f9', text: '#64748b' };
  return (
    <span style={{
      background: style.bg, color: style.text,
      padding: '3px 10px', borderRadius: 12, fontSize: 12, fontWeight: 600,
    }}>
      {status}
    </span>
  );
}
