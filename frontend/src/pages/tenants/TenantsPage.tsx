import React, { useState } from 'react';
import { Plus, Search, RefreshCw, Pencil, Trash2, Building2 } from 'lucide-react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { tenantsApi } from '@/api/tenants';
import DataTable from '@/components/common/DataTable';
import Pagination from '@/components/common/Pagination';
import Modal from '@/components/common/Modal';
import ConfirmDialog from '@/components/common/ConfirmDialog';
import StatusBadge from '@/components/common/StatusBadge';
import RoleGuard from '@/components/auth/RoleGuard';
import { useTenant } from '@/hooks/useTenant';
import toast from 'react-hot-toast';
import type { Tenant, TableColumn, TenantFormData, TenantPlan, TenantStatus } from '@/types';

const planVariant = (plan: TenantPlan) => {
  switch (plan) {
    case 'enterprise': return 'purple' as const;
    case 'professional': return 'info' as const;
    case 'starter': return 'success' as const;
    default: return 'neutral' as const;
  }
};

const statusVariant = (status: TenantStatus) => {
  switch (status) {
    case 'active': return 'success' as const;
    case 'trial': return 'info' as const;
    case 'suspended': return 'danger' as const;
    default: return 'neutral' as const;
  }
};

const emptyForm: TenantFormData = {
  name: '',
  slug: '',
  domain: '',
  plan: 'free',
  status: 'active',
};

const TenantsPage: React.FC = () => {
  const qc = useQueryClient();
  const { switchTenant, currentTenant } = useTenant();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [formOpen, setFormOpen] = useState(false);
  const [editTenant, setEditTenant] = useState<Tenant | null>(null);
  const [deleteTenant, setDeleteTenant] = useState<Tenant | null>(null);
  const [formData, setFormData] = useState<TenantFormData>(emptyForm);
  const [formErrors, setFormErrors] = useState<Partial<Record<keyof TenantFormData, string>>>({});

  const searchTimeout = React.useRef<ReturnType<typeof setTimeout>>();

  const { data, isLoading, refetch } = useQuery({
    queryKey: ['tenants', { page, search: debouncedSearch }],
    queryFn: () => tenantsApi.list({ page, per_page: 15, search: debouncedSearch }),
  });

  const createMutation = useMutation({
    mutationFn: (d: TenantFormData) => tenantsApi.create(d),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['tenants'] });
      toast.success('Tenant created');
      setFormOpen(false);
    },
    onError: () => toast.error('Failed to create tenant'),
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, d }: { id: number; d: Partial<TenantFormData> }) =>
      tenantsApi.update(id, d),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['tenants'] });
      toast.success('Tenant updated');
      setEditTenant(null);
    },
    onError: () => toast.error('Failed to update tenant'),
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => tenantsApi.delete(id),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['tenants'] });
      toast.success('Tenant deleted');
      setDeleteTenant(null);
    },
    onError: () => toast.error('Failed to delete tenant'),
  });

  const handleSearch = (value: string) => {
    setSearch(value);
    clearTimeout(searchTimeout.current);
    searchTimeout.current = setTimeout(() => {
      setDebouncedSearch(value);
      setPage(1);
    }, 400);
  };

  const openCreate = () => {
    setFormData(emptyForm);
    setFormErrors({});
    setFormOpen(true);
  };

  const openEdit = (t: Tenant) => {
    setFormData({ name: t.name, slug: t.slug, domain: t.domain ?? '', plan: t.plan, status: t.status });
    setFormErrors({});
    setEditTenant(t);
  };

  const validate = (d: TenantFormData) => {
    const e: Partial<Record<keyof TenantFormData, string>> = {};
    if (!d.name.trim()) e.name = 'Name is required';
    if (!d.slug.trim()) e.slug = 'Slug is required';
    else if (!/^[a-z0-9-]+$/.test(d.slug)) e.slug = 'Slug must be lowercase letters, numbers, and hyphens';
    setFormErrors(e);
    return Object.keys(e).length === 0;
  };

  const handleFormSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!validate(formData)) return;
    if (editTenant) {
      await updateMutation.mutateAsync({ id: editTenant.id, d: formData });
    } else {
      await createMutation.mutateAsync(formData);
    }
  };

  const inputClass =
    'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 transition-colors';
  const labelClass = 'block text-sm font-medium text-gray-700 mb-1';
  const errorClass = 'mt-1 text-xs text-red-600';

  const columns: TableColumn<Tenant>[] = [
    {
      key: 'name',
      label: 'Tenant Name',
      sortable: true,
      render: (v, row) => (
        <div className="flex items-center gap-2">
          <div className="w-7 h-7 rounded-lg bg-primary-100 flex items-center justify-center">
            <Building2 size={14} className="text-primary-600" />
          </div>
          <div>
            <p className="font-medium text-gray-800">{v as string}</p>
            <p className="text-xs text-gray-400">{row.slug}</p>
          </div>
        </div>
      ),
    },
    { key: 'domain', label: 'Domain', render: (v) => (v as string) || '—' },
    {
      key: 'plan',
      label: 'Plan',
      render: (v) => (
        <StatusBadge label={(v as string).toUpperCase()} variant={planVariant(v as TenantPlan)} />
      ),
    },
    {
      key: 'status',
      label: 'Status',
      render: (v) => (
        <StatusBadge label={v as string} variant={statusVariant(v as TenantStatus)} dot />
      ),
    },
    {
      key: 'users_count',
      label: 'Users',
      className: 'text-center',
      render: (v) => <span className="text-gray-600">{(v as number) ?? '—'}</span>,
    },
    {
      key: 'id',
      label: '',
      className: 'w-32',
      render: (_, row) => (
        <div className="flex items-center gap-1">
          <button
            onClick={(e) => { e.stopPropagation(); switchTenant(row.id); }}
            className={`px-2 py-1 text-xs rounded-lg font-medium transition-colors ${
              currentTenant?.id === row.id
                ? 'bg-primary-600 text-white'
                : 'border border-gray-200 text-gray-600 hover:bg-gray-50'
            }`}
          >
            {currentTenant?.id === row.id ? 'Active' : 'Switch'}
          </button>
          <RoleGuard permissions={['tenants.manage']}>
            <button
              onClick={(e) => { e.stopPropagation(); openEdit(row); }}
              className="p-1.5 rounded-lg text-gray-400 hover:text-primary-600 hover:bg-blue-50 transition-colors"
            >
              <Pencil size={13} />
            </button>
            <button
              onClick={(e) => { e.stopPropagation(); setDeleteTenant(row); }}
              className="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
            >
              <Trash2 size={13} />
            </button>
          </RoleGuard>
        </div>
      ),
    },
  ];

  const TenantFormContent = () => (
    <form onSubmit={handleFormSubmit} className="space-y-4">
      <div className="grid grid-cols-2 gap-4">
        <div>
          <label className={labelClass}>Name *</label>
          <input
            value={formData.name}
            onChange={(e) => setFormData((p) => ({ ...p, name: e.target.value }))}
            className={inputClass}
            placeholder="Acme Corp"
          />
          {formErrors.name && <p className={errorClass}>{formErrors.name}</p>}
        </div>
        <div>
          <label className={labelClass}>Slug *</label>
          <input
            value={formData.slug}
            onChange={(e) => setFormData((p) => ({ ...p, slug: e.target.value.toLowerCase().replace(/\s+/g, '-') }))}
            className={inputClass}
            placeholder="acme-corp"
            disabled={!!editTenant}
          />
          {formErrors.slug && <p className={errorClass}>{formErrors.slug}</p>}
        </div>
      </div>
      <div>
        <label className={labelClass}>Domain</label>
        <input
          value={formData.domain}
          onChange={(e) => setFormData((p) => ({ ...p, domain: e.target.value }))}
          className={inputClass}
          placeholder="acme.example.com"
        />
      </div>
      <div className="grid grid-cols-2 gap-4">
        <div>
          <label className={labelClass}>Plan</label>
          <select
            value={formData.plan}
            onChange={(e) => setFormData((p) => ({ ...p, plan: e.target.value as TenantPlan }))}
            className={inputClass}
          >
            {(['free', 'starter', 'professional', 'enterprise'] as TenantPlan[]).map((p) => (
              <option key={p} value={p}>{p.charAt(0).toUpperCase() + p.slice(1)}</option>
            ))}
          </select>
        </div>
        <div>
          <label className={labelClass}>Status</label>
          <select
            value={formData.status}
            onChange={(e) => setFormData((p) => ({ ...p, status: e.target.value as TenantStatus }))}
            className={inputClass}
          >
            {(['active', 'inactive', 'suspended', 'trial'] as TenantStatus[]).map((s) => (
              <option key={s} value={s}>{s.charAt(0).toUpperCase() + s.slice(1)}</option>
            ))}
          </select>
        </div>
      </div>
      <div className="flex justify-end gap-3 pt-2">
        <button
          type="button"
          onClick={() => { setFormOpen(false); setEditTenant(null); }}
          className="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50"
        >
          Cancel
        </button>
        <button
          type="submit"
          disabled={createMutation.isPending || updateMutation.isPending}
          className="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
        >
          {editTenant ? 'Update Tenant' : 'Create Tenant'}
        </button>
      </div>
    </form>
  );

  return (
    <div className="space-y-5">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Tenants</h1>
          <p className="text-sm text-gray-500 mt-0.5">{data?.meta.total ?? 0} tenants</p>
        </div>
        <RoleGuard permissions={['tenants.manage']}>
          <button
            onClick={openCreate}
            className="flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-xl text-sm font-medium hover:bg-primary-700 shadow-sm"
          >
            <Plus size={16} />
            New Tenant
          </button>
        </RoleGuard>
      </div>

      <div className="flex items-center gap-3">
        <div className="relative flex-1 max-w-sm">
          <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <input
            value={search}
            onChange={(e) => handleSearch(e.target.value)}
            placeholder="Search tenants…"
            className="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
          />
        </div>
        <button onClick={() => refetch()} className="p-2 rounded-xl border border-gray-200 text-gray-500 hover:bg-gray-50">
          <RefreshCw size={16} />
        </button>
      </div>

      <DataTable
        columns={columns}
        data={data?.data ?? []}
        isLoading={isLoading}
        keyExtractor={(row) => row.id}
        emptyMessage="No tenants found."
      />

      {data?.meta && <Pagination meta={data.meta} onPageChange={setPage} />}

      <Modal open={formOpen} onClose={() => setFormOpen(false)} title="New Tenant" size="md">
        <TenantFormContent />
      </Modal>

      <Modal open={!!editTenant} onClose={() => setEditTenant(null)} title="Edit Tenant" size="md">
        <TenantFormContent />
      </Modal>

      <ConfirmDialog
        open={!!deleteTenant}
        onClose={() => setDeleteTenant(null)}
        onConfirm={() => { if (deleteTenant) deleteMutation.mutate(deleteTenant.id); }}
        title="Delete Tenant"
        message={`Delete tenant "${deleteTenant?.name}"? All associated data will be removed.`}
        confirmLabel="Delete Tenant"
        isLoading={deleteMutation.isPending}
      />
    </div>
  );
};

export default TenantsPage;
