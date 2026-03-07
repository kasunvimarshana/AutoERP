import React, { useState } from 'react';
import { useUsers } from '../hooks/useUsers';
import { useAuth } from '../hooks/useAuth';
import Table from '../components/common/Table';
import SearchBar from '../components/common/SearchBar';
import Pagination from '../components/common/Pagination';
import LoadingSpinner from '../components/common/LoadingSpinner';
import StatusBadge from '../components/common/StatusBadge';
import Modal from '../components/common/Modal';
import FormField from '../components/common/FormField';
import { toast } from 'react-toastify';
import { formatDate } from '../utils/formatters';
import { Shield } from 'lucide-react';

const AVAILABLE_ROLES = ['admin', 'manager', 'staff', 'customer'];

export default function UsersPage() {
  const { hasRole } = useAuth();
  if (!hasRole('admin')) return <div className="text-red-500 p-4">Access denied. Admin only.</div>;

  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [roleTarget, setRoleTarget] = useState(null);
  const [newRole, setNewRole] = useState('');

  const { data, isLoading, isError, assignRole, revokeRole, isAssigning } = useUsers({ page, search, per_page: 15 });

  const handleAssign = async () => {
    if (!newRole) return;
    try {
      await assignRole({ id: roleTarget.id, role: newRole });
      toast.success(`Role '${newRole}' assigned`);
      setRoleTarget(null);
      setNewRole('');
    } catch {
      toast.error('Failed to assign role');
    }
  };

  const handleRevoke = async (userId, role) => {
    try {
      await revokeRole({ id: userId, role });
      toast.success(`Role '${role}' revoked`);
    } catch {
      toast.error('Failed to revoke role');
    }
  };

  const columns = [
    { header: 'Name', accessor: 'first_name', cell: (r) => (
      <div>
        <div className="font-medium text-gray-900">{r.first_name} {r.last_name}</div>
        <div className="text-xs text-gray-500">{r.username}</div>
      </div>
    )},
    { header: 'Email', accessor: 'email', cell: (r) => <span className="text-gray-700">{r.email}</span> },
    { header: 'Roles', accessor: 'roles', cell: (r) => (
      <div className="flex flex-wrap gap-1">
        {(r.roles || []).map((role) => (
          <span key={role} className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
            {role}
            <button onClick={() => handleRevoke(r.id, role)} className="text-indigo-400 hover:text-indigo-700 ml-0.5">×</button>
          </span>
        ))}
      </div>
    )},
    { header: 'Status', accessor: 'is_active', cell: (r) => (
      <StatusBadge status={r.is_active ? 'active' : 'inactive'} />
    )},
    { header: 'Last Login', accessor: 'last_login_at', cell: (r) => (
      <span className="text-gray-500 text-sm">{r.last_login_at ? formatDate(r.last_login_at) : 'Never'}</span>
    )},
    { header: 'Actions', accessor: 'actions', cell: (r) => (
      <button onClick={() => { setRoleTarget(r); setNewRole(''); }}
        className="flex items-center gap-1 text-indigo-600 hover:text-indigo-800 text-sm">
        <Shield size={14} /> Assign Role
      </button>
    )},
  ];

  if (isLoading) return <LoadingSpinner />;
  if (isError) return <div className="text-red-500 p-4">Failed to load users.</div>;

  const users = data?.data || [];
  const meta = data?.meta || {};

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Users</h1>
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div className="p-4 border-b border-gray-100">
          <SearchBar value={search} onChange={(v) => { setSearch(v); setPage(1); }}
            placeholder="Search users..." />
        </div>
        <Table columns={columns} data={users} />
        <div className="p-4 border-t border-gray-100">
          <Pagination currentPage={meta.current_page || 1} totalPages={meta.last_page || 1}
            onPageChange={setPage} />
        </div>
      </div>

      <Modal isOpen={!!roleTarget} onClose={() => setRoleTarget(null)}
        title={`Assign Role — ${roleTarget?.first_name} ${roleTarget?.last_name}`} size="sm">
        <div className="space-y-4">
          <FormField label="Role">
            <select value={newRole} onChange={(e) => setNewRole(e.target.value)}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
              <option value="">Select a role...</option>
              {AVAILABLE_ROLES.filter((r) => !(roleTarget?.roles || []).includes(r)).map((r) => (
                <option key={r} value={r}>{r}</option>
              ))}
            </select>
          </FormField>
          <div className="flex justify-end gap-3">
            <button onClick={() => setRoleTarget(null)}
              className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
              Cancel
            </button>
            <button onClick={handleAssign} disabled={!newRole || isAssigning}
              className="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50">
              {isAssigning ? 'Assigning...' : 'Assign Role'}
            </button>
          </div>
        </div>
      </Modal>
    </div>
  );
}
