import React, { useState, useEffect } from 'react';
import { usersApi } from '../api/users';
import type { UserFilters, CreateUserPayload } from '../api/users';
import type { User } from '../types';
import DataTable from '../components/DataTable';
import Pagination from '../components/Pagination';

const UsersPage: React.FC = () => {
  const [users, setUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);
  const [currentPage, setCurrentPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [search, setSearch] = useState('');
  const [showForm, setShowForm] = useState(false);
  const [editingUser, setEditingUser] = useState<User | null>(null);
  const [formData, setFormData] = useState<CreateUserPayload>({ name: '', email: '', password: '', is_active: true });
  const [error, setError] = useState('');

  const fetchUsers = async (page = 1) => {
    setLoading(true);
    try {
      const filters: UserFilters = { per_page: 10, page, search: search || undefined };
      const res = await usersApi.list(filters);
      const responseData = res.data.data;
      if (responseData && 'data' in responseData) {
        setUsers((responseData as { data: User[] }).data);
        setLastPage((responseData as { last_page: number }).last_page);
      } else {
        setUsers(responseData as User[]);
      }
    } catch {
      setError('Failed to load users');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { fetchUsers(currentPage); }, [currentPage, search]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      if (editingUser) {
        await usersApi.update(editingUser.id, formData);
      } else {
        await usersApi.create(formData);
      }
      setShowForm(false);
      setEditingUser(null);
      setFormData({ name: '', email: '', password: '', is_active: true });
      fetchUsers(currentPage);
    } catch (err: unknown) {
      setError((err as { response?: { data?: { message?: string } } })?.response?.data?.message || 'Operation failed');
    }
  };

  const handleDelete = async (user: User) => {
    if (!confirm(`Delete user ${user.name}?`)) return;
    try {
      await usersApi.delete(user.id);
      fetchUsers(currentPage);
    } catch {
      setError('Delete failed');
    }
  };

  const handleEdit = (user: User) => {
    setEditingUser(user);
    setFormData({ name: user.name, email: user.email, password: '', is_active: user.is_active });
    setShowForm(true);
  };

  const columns = [
    { key: 'id', label: 'ID' },
    { key: 'name', label: 'Name' },
    { key: 'email', label: 'Email' },
    {
      key: 'roles', label: 'Roles',
      render: (_: unknown, row: User) => row.roles?.map(r => r.name).join(', ') || '—',
    },
    {
      key: 'is_active', label: 'Status',
      render: (val: unknown) => (
        <span style={{
          padding: '0.125rem 0.5rem',
          borderRadius: '9999px',
          fontSize: '0.75rem',
          background: val ? '#dcfce7' : '#fee2e2',
          color: val ? '#16a34a' : '#dc2626',
        }}>{val ? 'Active' : 'Inactive'}</span>
      ),
    },
  ];

  const inputStyle: React.CSSProperties = {
    width: '100%', padding: '0.5rem 0.75rem', border: '1px solid #d1d5db',
    borderRadius: '0.375rem', fontSize: '0.875rem', marginBottom: '0.75rem', boxSizing: 'border-box',
  };

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.5rem' }}>
        <h1 style={{ fontSize: '1.5rem', fontWeight: 700, color: '#1e293b', margin: 0 }}>Users</h1>
        <button
          onClick={() => { setShowForm(true); setEditingUser(null); setFormData({ name: '', email: '', password: '', is_active: true }); }}
          style={{ padding: '0.5rem 1rem', background: '#3b82f6', color: 'white', border: 'none', borderRadius: '0.375rem', cursor: 'pointer', fontWeight: 500 }}
        >
          + New User
        </button>
      </div>

      {error && <div style={{ background: '#fef2f2', color: '#dc2626', padding: '0.75rem', borderRadius: '0.375rem', marginBottom: '1rem', fontSize: '0.875rem' }}>{error}</div>}

      <div style={{ marginBottom: '1rem' }}>
        <input
          type="text"
          placeholder="Search users..."
          value={search}
          onChange={(e) => { setSearch(e.target.value); setCurrentPage(1); }}
          style={{ ...inputStyle, marginBottom: 0, maxWidth: '300px' }}
        />
      </div>

      <DataTable
        columns={columns as Parameters<typeof DataTable>[0]['columns']}
        data={users as unknown as Record<string, unknown>[]}
        loading={loading}
        onEdit={(row) => handleEdit(row as unknown as User)}
        onDelete={(row) => handleDelete(row as unknown as User)}
      />

      <Pagination currentPage={currentPage} lastPage={lastPage} onPageChange={setCurrentPage} />

      {showForm && (
        <div style={{
          position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.5)',
          display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 50,
        }}>
          <div style={{ background: 'white', borderRadius: '0.75rem', padding: '2rem', width: '100%', maxWidth: '450px' }}>
            <h2 style={{ marginBottom: '1.5rem', fontSize: '1.25rem', fontWeight: 600 }}>
              {editingUser ? 'Edit User' : 'Create User'}
            </h2>
            <form onSubmit={handleSubmit}>
              <input style={inputStyle} placeholder="Name" value={formData.name} onChange={(e) => setFormData({ ...formData, name: e.target.value })} required />
              <input style={inputStyle} type="email" placeholder="Email" value={formData.email} onChange={(e) => setFormData({ ...formData, email: e.target.value })} required />
              <input style={inputStyle} type="password" placeholder={editingUser ? 'New password (leave blank to keep)' : 'Password'} value={formData.password} onChange={(e) => setFormData({ ...formData, password: e.target.value })} required={!editingUser} />
              <label style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', marginBottom: '1rem', fontSize: '0.875rem' }}>
                <input type="checkbox" checked={formData.is_active} onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })} />
                Active
              </label>
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

export default UsersPage;
