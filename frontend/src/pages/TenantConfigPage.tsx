import React, { useState, useEffect } from 'react';
import { tenantsApi } from '../api/tenants';
import type { TenantConfig } from '../types';
import { useAuth } from '../contexts/AuthContext';

const TenantConfigPage: React.FC = () => {
  const { user } = useAuth();
  const [configs, setConfigs] = useState<TenantConfig[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [showForm, setShowForm] = useState(false);
  const [formData, setFormData] = useState({ key: '', value: '', group: 'general', type: 'string' });

  const tenantId = user?.tenant_id;

  const fetchConfigs = async () => {
    if (!tenantId) return;
    setLoading(true);
    try {
      const res = await tenantsApi.getConfig(tenantId);
      setConfigs(res.data.data || []);
    } catch { setError('Failed to load configs'); }
    finally { setLoading(false); }
  };

  useEffect(() => { fetchConfigs(); }, [tenantId]);

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!tenantId) return;
    try {
      await tenantsApi.setConfig(tenantId, formData.key, formData.value, formData.group, formData.type);
      setShowForm(false);
      setFormData({ key: '', value: '', group: 'general', type: 'string' });
      fetchConfigs();
    } catch { setError('Save failed'); }
  };

  const inputStyle: React.CSSProperties = {
    width: '100%', padding: '0.5rem 0.75rem', border: '1px solid #d1d5db',
    borderRadius: '0.375rem', fontSize: '0.875rem', marginBottom: '0.75rem', boxSizing: 'border-box',
  };

  const groupColors: Record<string, string> = {
    mail: '#3b82f6',
    payment: '#10b981',
    notification: '#f59e0b',
    general: '#6b7280',
  };

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.5rem' }}>
        <div>
          <h1 style={{ fontSize: '1.5rem', fontWeight: 700, color: '#1e293b', margin: 0 }}>Tenant Configuration</h1>
          <p style={{ color: '#64748b', fontSize: '0.875rem', marginTop: '0.25rem' }}>
            Runtime settings for {user?.tenant?.name} — applied per-tenant without affecting others
          </p>
        </div>
        <button onClick={() => setShowForm(true)}
          style={{ padding: '0.5rem 1rem', background: '#3b82f6', color: 'white', border: 'none', borderRadius: '0.375rem', cursor: 'pointer', fontWeight: 500 }}>
          + Add Config
        </button>
      </div>

      {error && <div style={{ background: '#fef2f2', color: '#dc2626', padding: '0.75rem', borderRadius: '0.375rem', marginBottom: '1rem' }}>{error}</div>}

      {loading ? (
        <div style={{ textAlign: 'center', padding: '3rem', color: '#6b7280' }}>Loading...</div>
      ) : (
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(300px, 1fr))', gap: '1rem' }}>
          {configs.length === 0 ? (
            <p style={{ color: '#9ca3af', gridColumn: '1/-1' }}>No configurations yet. Add your first one!</p>
          ) : (
            configs.map((config) => (
              <div key={config.id} style={{
                background: 'white', borderRadius: '0.5rem', padding: '1rem',
                border: `2px solid ${(groupColors[config.group] || '#6b7280')}22`,
              }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '0.5rem' }}>
                  <code style={{ fontSize: '0.875rem', fontWeight: 600, color: '#1e293b' }}>{config.key}</code>
                  <span style={{
                    padding: '0.125rem 0.375rem', borderRadius: '9999px', fontSize: '0.625rem', fontWeight: 600,
                    background: (groupColors[config.group] || '#6b7280') + '22',
                    color: groupColors[config.group] || '#6b7280',
                    textTransform: 'uppercase',
                  }}>
                    {config.group}
                  </span>
                </div>
                <div style={{ fontSize: '0.875rem', color: '#4b5563', wordBreak: 'break-all' }}>
                  {config.type === 'string' && config.value.includes('secret') ? '••••••••' : config.value}
                </div>
                <div style={{ marginTop: '0.5rem', fontSize: '0.75rem', color: '#9ca3af' }}>
                  Type: {config.type}
                </div>
              </div>
            ))
          )}
        </div>
      )}

      {showForm && (
        <div style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.5)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 50 }}>
          <div style={{ background: 'white', borderRadius: '0.75rem', padding: '2rem', width: '100%', maxWidth: '450px' }}>
            <h2 style={{ marginBottom: '1.5rem', fontSize: '1.25rem', fontWeight: 600 }}>Add/Update Configuration</h2>
            <form onSubmit={handleSave}>
              <input style={inputStyle} placeholder="Config Key (e.g., mail_from_address)" value={formData.key} onChange={(e) => setFormData({ ...formData, key: e.target.value })} required />
              <input style={inputStyle} placeholder="Value" value={formData.value} onChange={(e) => setFormData({ ...formData, value: e.target.value })} required />
              <select style={inputStyle} value={formData.group} onChange={(e) => setFormData({ ...formData, group: e.target.value })}>
                <option value="general">General</option>
                <option value="mail">Mail</option>
                <option value="payment">Payment</option>
                <option value="notification">Notification</option>
              </select>
              <select style={inputStyle} value={formData.type} onChange={(e) => setFormData({ ...formData, type: e.target.value })}>
                <option value="string">String</option>
                <option value="boolean">Boolean</option>
                <option value="integer">Integer</option>
                <option value="float">Float</option>
                <option value="json">JSON</option>
              </select>
              <div style={{ display: 'flex', gap: '0.75rem', justifyContent: 'flex-end' }}>
                <button type="button" onClick={() => setShowForm(false)} style={{ padding: '0.5rem 1rem', border: '1px solid #d1d5db', borderRadius: '0.375rem', cursor: 'pointer', background: 'white' }}>Cancel</button>
                <button type="submit" style={{ padding: '0.5rem 1rem', background: '#3b82f6', color: 'white', border: 'none', borderRadius: '0.375rem', cursor: 'pointer', fontWeight: 500 }}>Save</button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default TenantConfigPage;
