import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { productApi } from '../services/api';

export default function Dashboard() {
  const { data: products } = useQuery({ queryKey: ['products-count'], queryFn: () => productApi.list({ per_page: 1 }) });
  const { data: inventory } = useQuery({ queryKey: ['inventory-count'], queryFn: () => productApi.list({ per_page: 1 }) });

  const cards = [
    { label: 'Products', value: products?.data?.meta?.total ?? '–', icon: '📦', color: '#6366f1' },
    { label: 'Inventory Items', value: inventory?.data?.meta?.total ?? '–', icon: '🏭', color: '#10b981' },
    { label: 'Active Orders', value: '–', icon: '🛒', color: '#f59e0b' },
    { label: 'Users', value: '–', icon: '👥', color: '#ec4899' },
  ];

  return (
    <div>
      <h1 style={{ marginBottom: 24, fontSize: 24, color: '#1e293b' }}>Dashboard</h1>
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(200px, 1fr))', gap: 20 }}>
        {cards.map(({ label, value, icon, color }) => (
          <div key={label} style={{ background: '#fff', borderRadius: 12, padding: 20, boxShadow: '0 1px 3px rgba(0,0,0,0.1)', borderTop: `4px solid ${color}` }}>
            <div style={{ fontSize: 32 }}>{icon}</div>
            <div style={{ fontSize: 28, fontWeight: 700, color, margin: '8px 0 4px' }}>{value}</div>
            <div style={{ color: '#64748b', fontSize: 14 }}>{label}</div>
          </div>
        ))}
      </div>
    </div>
  );
}
