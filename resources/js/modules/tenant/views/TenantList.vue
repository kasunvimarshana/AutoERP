<template>
  <div>
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Multi-Tenant Administration</h1>
        <p class="mt-1 text-sm text-gray-500">Manage tenant accounts and configurations</p>
      </div>
      <BaseButton variant="primary" @click="openCreateModal">
        Create Tenant
      </BaseButton>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-blue-100 rounded-lg">
              <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Total Tenants</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-green-100 rounded-lg">
              <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Active</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.active }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-purple-100 rounded-lg">
              <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Inactive</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.inactive }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-indigo-100 rounded-lg">
              <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Total Users</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.totalUsers }}</p>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- Filters -->
    <BaseCard class="mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <BaseInput
          v-model="search"
          placeholder="Search tenants by code or name..."
          type="search"
        />
        <BaseSelect
          v-model="statusFilter"
          :options="statusOptions"
          placeholder="Filter by status"
        />
      </div>
    </BaseCard>

    <!-- Tenants Table -->
    <BaseCard>
      <BaseTable
        :columns="columns"
        :data="filteredTenants"
        :loading="loading"
        :actions="tableActions"
        @sort="handleSort"
        @action:view="viewTenant"
        @action:edit="editTenant"
        @action:activate="activateTenant"
        @action:deactivate="deactivateTenant"
        @action:suspend="suspendTenant"
        @action:delete="deleteTenant"
      >
        <template #cell-tenant_code="{ value }">
          <span class="font-medium text-gray-900">{{ value }}</span>
        </template>

        <template #cell-status="{ value }">
          <BaseBadge :variant="getStatusVariant(value)">
            {{ formatStatus(value) }}
          </BaseBadge>
        </template>

        <template #cell-created_at="{ value }">
          {{ formatDate(value) }}
        </template>
      </BaseTable>

      <div v-if="pagination.totalPages > 1" class="mt-4">
        <BasePagination
          :current-page="pagination.currentPage"
          :total-pages="pagination.totalPages"
          :total="pagination.total"
          :per-page="pagination.perPage"
          @page-change="handlePageChange"
        />
      </div>
    </BaseCard>

    <!-- Create/Edit Modal -->
    <BaseModal :show="modal.isOpen" :title="modalTitle" size="lg" @close="modal.close">
      <form @submit.prevent="handleSubmit">
        <div class="space-y-4">
          <BaseInput
            v-model="form.tenant_code"
            label="Tenant Code"
            required
            placeholder="TENANT-001"
            :error="errors.tenant_code"
          />

          <BaseInput
            v-model="form.name"
            label="Tenant Name"
            required
            placeholder="Enter tenant name"
            :error="errors.name"
          />

          <BaseInput
            v-model="form.domain"
            label="Domain"
            required
            placeholder="tenant.example.com"
            :error="errors.domain"
          />

          <div class="grid grid-cols-2 gap-4">
            <BaseSelect
              v-model="form.status"
              label="Status"
              :options="statusOptionsForm"
              required
              :error="errors.status"
            />

            <BaseInput
              v-model.number="form.max_users"
              label="Max Users"
              type="number"
              min="1"
              required
              :error="errors.max_users"
            />
          </div>

          <BaseTextarea
            v-model="form.settings"
            label="Settings (JSON)"
            placeholder='{"feature_flags": {}, "limits": {}}'
            :rows="4"
            :error="errors.settings"
          />
        </div>

        <div class="mt-6 flex justify-end space-x-3">
          <BaseButton type="button" variant="secondary" @click="modal.close">
            Cancel
          </BaseButton>
          <BaseButton type="submit" variant="primary" :loading="saving">
            {{ editingId ? 'Update' : 'Create' }} Tenant
          </BaseButton>
        </div>
      </form>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useTenantStore } from '../stores/tenantStore';
import { useModal } from '@/composables/useModal';
import { useTable } from '@/composables/useTable';
import { usePagination } from '@/composables/usePagination';
import { useNotifications } from '@/composables/useNotifications';
import BaseCard from '@/components/layout/BaseCard.vue';
import BaseButton from '@/components/common/BaseButton.vue';
import BaseInput from '@/components/common/BaseInput.vue';
import BaseSelect from '@/components/common/BaseSelect.vue';
import BaseTextarea from '@/components/common/BaseTextarea.vue';
import BaseTable from '@/components/data/BaseTable.vue';
import BasePagination from '@/components/data/BasePagination.vue';
import BaseBadge from '@/components/common/BaseBadge.vue';
import BaseModal from '@/components/layout/BaseModal.vue';

const tenantStore = useTenantStore();
const modal = useModal();
const { showSuccess, showError } = useNotifications();

// State
const loading = ref(false);
const saving = ref(false);
const search = ref('');
const statusFilter = ref('');
const editingId = ref(null);

// Form
const form = ref({
  tenant_code: '',
  name: '',
  domain: '',
  status: 'active',
  max_users: 100,
  settings: '{}',
});

const errors = ref({});

// Options
const statusOptions = [
  { label: 'All Statuses', value: '' },
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Suspended', value: 'suspended' },
];

const statusOptionsForm = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Suspended', value: 'suspended' },
];

// Table configuration
const columns = [
  { key: 'tenant_code', label: 'Code', sortable: true },
  { key: 'name', label: 'Name', sortable: true },
  { key: 'domain', label: 'Domain', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'user_count', label: 'Users', sortable: true },
  { key: 'created_at', label: 'Created', sortable: true },
];

const tableActions = [
  { key: 'view', label: 'View', variant: 'secondary' },
  { key: 'edit', label: 'Edit', variant: 'primary' },
  { key: 'activate', label: 'Activate', variant: 'success' },
  { key: 'deactivate', label: 'Deactivate', variant: 'warning' },
  { key: 'suspend', label: 'Suspend', variant: 'danger' },
  { key: 'delete', label: 'Delete', variant: 'danger' },
];

// Statistics
const stats = computed(() => {
  const tenants = tenantStore.tenants || [];
  const activeTenants = tenants.filter(t => t.status === 'active');
  const inactiveTenants = tenants.filter(t => t.status === 'inactive');
  const totalUsers = tenants.reduce((sum, t) => sum + (t.user_count || 0), 0);

  return {
    total: tenants.length,
    active: activeTenants.length,
    inactive: inactiveTenants.length,
    totalUsers: totalUsers,
  };
});

// Filtering and sorting
const { sortedData, handleSort } = useTable(computed(() => tenantStore.tenants || []));

const filteredTenants = computed(() => {
  let data = sortedData.value;

  // Search filter
  if (search.value) {
    const searchLower = search.value.toLowerCase();
    data = data.filter(tenant =>
      tenant.tenant_code?.toLowerCase().includes(searchLower) ||
      tenant.name?.toLowerCase().includes(searchLower) ||
      tenant.domain?.toLowerCase().includes(searchLower)
    );
  }

  // Status filter
  if (statusFilter.value) {
    data = data.filter(tenant => tenant.status === statusFilter.value);
  }

  return data;
});

// Pagination
const pagination = usePagination(filteredTenants, 10);

const handlePageChange = (page) => {
  pagination.currentPage.value = page;
};

// Modal
const modalTitle = computed(() => 
  editingId.value ? 'Edit Tenant' : 'Create Tenant'
);

const openCreateModal = () => {
  resetForm();
  editingId.value = null;
  modal.open();
};

const resetForm = () => {
  form.value = {
    tenant_code: '',
    name: '',
    domain: '',
    status: 'active',
    max_users: 100,
    settings: '{}',
  };
  errors.value = {};
};

// CRUD Operations
const fetchTenants = async () => {
  loading.value = true;
  try {
    await tenantStore.fetchTenants();
  } catch (error) {
    showError('Failed to load tenants');
  } finally {
    loading.value = false;
  }
};

const handleSubmit = async () => {
  errors.value = {};
  saving.value = true;

  try {
    // Validate settings as JSON
    try {
      JSON.parse(form.value.settings);
    } catch (e) {
      errors.value.settings = 'Settings must be valid JSON';
      throw new Error('Invalid JSON in settings');
    }

    if (editingId.value) {
      await tenantStore.updateTenant(editingId.value, form.value);
      showSuccess('Tenant updated successfully');
    } else {
      await tenantStore.createTenant(form.value);
      showSuccess('Tenant created successfully');
    }
    
    modal.close();
    resetForm();
    await fetchTenants();
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    }
    if (!errors.value.settings) {
      showError(error.response?.data?.message || 'Operation failed');
    }
  } finally {
    saving.value = false;
  }
};

const viewTenant = (tenant) => {
  console.log('View tenant:', tenant);
  showError('Tenant detail view not yet implemented');
};

const editTenant = (tenant) => {
  editingId.value = tenant.id;
  form.value = {
    tenant_code: tenant.tenant_code || '',
    name: tenant.name || '',
    domain: tenant.domain || '',
    status: tenant.status || 'active',
    max_users: tenant.max_users || 100,
    settings: typeof tenant.settings === 'string' ? tenant.settings : JSON.stringify(tenant.settings || {}),
  };
  modal.open();
};

const activateTenant = async (tenant) => {
  if (!confirm(`Activate tenant ${tenant.name}?`)) {
    return;
  }

  try {
    await tenantStore.activateTenant(tenant.id);
    showSuccess('Tenant activated successfully');
    await fetchTenants();
  } catch (error) {
    showError('Failed to activate tenant');
  }
};

const deactivateTenant = async (tenant) => {
  if (!confirm(`Deactivate tenant ${tenant.name}?`)) {
    return;
  }

  try {
    await tenantStore.deactivateTenant(tenant.id);
    showSuccess('Tenant deactivated successfully');
    await fetchTenants();
  } catch (error) {
    showError('Failed to deactivate tenant');
  }
};

const suspendTenant = async (tenant) => {
  if (!confirm(`Suspend tenant ${tenant.name}?`)) {
    return;
  }

  try {
    await tenantStore.suspendTenant(tenant.id);
    showSuccess('Tenant suspended successfully');
    await fetchTenants();
  } catch (error) {
    showError('Failed to suspend tenant');
  }
};

const deleteTenant = async (tenant) => {
  if (!confirm(`Are you sure you want to delete ${tenant.name}? This action cannot be undone.`)) {
    return;
  }

  try {
    await tenantStore.deleteTenant(tenant.id);
    showSuccess('Tenant deleted successfully');
    await fetchTenants();
  } catch (error) {
    showError('Failed to delete tenant');
  }
};

// Utilities
const getStatusVariant = (status) => {
  const variants = {
    active: 'success',
    inactive: 'secondary',
    suspended: 'warning',
  };
  return variants[status] || 'secondary';
};

const formatStatus = (status) => {
  const labels = {
    active: 'Active',
    inactive: 'Inactive',
    suspended: 'Suspended',
  };
  return labels[status] || status;
};

const formatDate = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString();
};

// Lifecycle
onMounted(() => {
  fetchTenants();
});
</script>
