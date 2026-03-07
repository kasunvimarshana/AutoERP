import React from 'react';
import { useAuth } from '../contexts/AuthContext';

const StatCard: React.FC<{ label: string; value: string; color: string; emoji: string }> = ({ label, value, color, emoji }) => (
  <div style={{
    background: 'white',
    padding: '1.5rem',
    borderRadius: '0.75rem',
    border: `2px solid ${color}22`,
    flex: '1',
    minWidth: '180px',
  }}>
    <div style={{ fontSize: '2rem', marginBottom: '0.5rem' }}>{emoji}</div>
    <div style={{ fontSize: '2rem', fontWeight: 700, color }}>{value}</div>
    <div style={{ fontSize: '0.875rem', color: '#6b7280', marginTop: '0.25rem' }}>{label}</div>
  </div>
);

const DashboardPage: React.FC = () => {
  const { user } = useAuth();

  return (
    <div>
      <h1 style={{ fontSize: '1.75rem', fontWeight: 700, color: '#1e293b', marginBottom: '0.5rem' }}>
        Welcome back, {user?.name}!
      </h1>
      <p style={{ color: '#64748b', marginBottom: '2rem' }}>
        Tenant: <strong>{user?.tenant?.name}</strong> · Roles: {user?.roles?.map(r => r.name).join(', ')}
      </p>

      <div style={{ display: 'flex', gap: '1rem', flexWrap: 'wrap', marginBottom: '2rem' }}>
        <StatCard label="Total Products" value="—" color="#3b82f6" emoji="📦" />
        <StatCard label="Inventory Items" value="—" color="#10b981" emoji="🏭" />
        <StatCard label="Active Orders" value="—" color="#f59e0b" emoji="🛒" />
        <StatCard label="Total Users" value="—" color="#8b5cf6" emoji="👥" />
      </div>

      <div style={{ background: 'white', padding: '1.5rem', borderRadius: '0.75rem', border: '1px solid #e2e8f0' }}>
        <h2 style={{ fontSize: '1.125rem', fontWeight: 600, color: '#1e293b', marginBottom: '1rem' }}>
          System Overview
        </h2>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))', gap: '1rem' }}>
          {[
            { title: 'Multi-Tenancy', desc: 'Each tenant has isolated data with runtime-configurable settings', icon: '🏢' },
            { title: 'SSO & Auth', desc: 'Laravel Passport-based authentication with Single Sign-On', icon: '🔐' },
            { title: 'RBAC + ABAC', desc: 'Role and attribute-based fine-grained authorization', icon: '🛡️' },
            { title: 'Saga Pattern', desc: 'Distributed transactions with compensating rollbacks for Orders', icon: '🔄' },
            { title: 'Message Broker', desc: 'Event-driven architecture with RabbitMQ/Kafka support', icon: '📨' },
            { title: 'Microservices', desc: 'Controller → Service → Repository modular architecture', icon: '⚙️' },
          ].map(({ title, desc, icon }) => (
            <div key={title} style={{ padding: '1rem', background: '#f8fafc', borderRadius: '0.5rem' }}>
              <div style={{ fontSize: '1.5rem', marginBottom: '0.5rem' }}>{icon}</div>
              <div style={{ fontWeight: 600, color: '#1e293b', marginBottom: '0.25rem', fontSize: '0.9rem' }}>{title}</div>
              <div style={{ fontSize: '0.8rem', color: '#6b7280' }}>{desc}</div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

export default DashboardPage;
