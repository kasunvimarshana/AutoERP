<template>
  <div>
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Users</h1>
        <p class="mt-1 text-sm text-gray-500">Manage user accounts and permissions</p>
      </div>
      <BaseButton variant="primary" @click="openCreateModal">
        Add User
      </BaseButton>
    </div>

    <!-- Filters -->
    <BaseCard class="mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <BaseInput
          v-model="search"
          placeholder="Search users..."
          type="search"
        />
        <BaseSelect
          v-model="statusFilter"
          :options="statusOptions"
          placeholder="Filter by status"
        />
        <BaseSelect
          v-model="roleFilter"
          :options="roleOptions"
          placeholder="Filter by role"
        />
      </div>
    </BaseCard>

    <!-- Users Table -->
    <BaseCard>
      <BaseTable
        :columns="columns"
        :data="filteredUsers"
        :loading="loading"
        :actions="tableActions"
        @sort="handleSort"
        @action:view="viewUser"
        @action:edit="editUser"
        @action:activate="activateUser"
        @action:deactivate="deactivateUser"
        @action:delete="deleteUser"
      >
        <template #cell-status="{ value }">
          <BaseBadge :variant="getStatusVariant(value)">
            {{ value }}
          </BaseBadge>
        </template>
        
        <template #cell-roles="{ value }">
          <div class="flex gap-1 flex-wrap">
            <BaseBadge v-for="role in value" :key="role.id" variant="secondary">
              {{ role.name }}
            </BaseBadge>
          </div>
        </template>

        <template #cell-email="{ value }">
          <a :href="`mailto:${value}`" class="text-indigo-600 hover:text-indigo-900">
            {{ value }}
          </a>
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
            v-model="form.name"
            label="Full Name"
            required
            placeholder="Enter user name"
            :error="errors.name"
          />
          
          <BaseInput
            v-model="form.email"
            label="Email"
            type="email"
            required
            placeholder="user@example.com"
            :error="errors.email"
          />
          
          <BaseInput
            v-if="!editingId"
            v-model="form.password"
            label="Password"
            type="password"
            required
            placeholder="Enter password"
            :error="errors.password"
          />
          
          <BaseInput
            v-if="!editingId"
            v-model="form.password_confirmation"
            label="Confirm Password"
            type="password"
            required
            placeholder="Confirm password"
            :error="errors.password_confirmation"
          />
          
          <BaseSelect
            v-model="form.status"
            label="Status"
            :options="statusOptions"
            required
            :error="errors.status"
          />
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Roles
            </label>
            <div class="space-y-2">
              <label v-for="role in availableRoles" :key="role.value" class="flex items-center">
                <input
                  v-model="form.role_ids"
                  :value="role.value"
                  type="checkbox"
                  class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
                <span class="ml-2 text-sm text-gray-700">{{ role.label }}</span>
              </label>
            </div>
          </div>
        </div>

        <div class="mt-6 flex justify-end space-x-3">
          <BaseButton type="button" variant="secondary" @click="modal.close">
            Cancel
          </BaseButton>
          <BaseButton type="submit" variant="primary" :loading="saving">
            {{ editingId ? 'Update' : 'Create' }} User
          </BaseButton>
        </div>
      </form>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthManagementStore } from '../stores/authManagementStore';
import { useModal } from '@/composables/useModal';
import { useTable } from '@/composables/useTable';
import { usePagination } from '@/composables/usePagination';
import { useNotifications } from '@/composables/useNotifications';
import BaseCard from '@/components/layout/BaseCard.vue';
import BaseButton from '@/components/common/BaseButton.vue';
import BaseInput from '@/components/common/BaseInput.vue';
import BaseSelect from '@/components/common/BaseSelect.vue';
import BaseTable from '@/components/data/BaseTable.vue';
import BasePagination from '@/components/data/BasePagination.vue';
import BaseBadge from '@/components/common/BaseBadge.vue';
import BaseModal from '@/components/layout/BaseModal.vue';

const authManagementStore = useAuthManagementStore();
const modal = useModal();
const { showSuccess, showError } = useNotifications();

// State
const loading = ref(false);
const saving = ref(false);
const search = ref('');
const statusFilter = ref('');
const roleFilter = ref('');
const editingId = ref(null);
const availableRoles = ref([]);

// Form
const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  status: 'active',
  role_ids: [],
});

const errors = ref({});

// Options
const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Suspended', value: 'suspended' },
];

const roleOptions = ref([]);

// Table configuration
const columns = [
  { key: 'name', label: 'Name', sortable: true },
  { key: 'email', label: 'Email', sortable: true },
  { key: 'roles', label: 'Roles', sortable: false },
  { key: 'status', label: 'Status', sortable: true },
];

const tableActions = [
  { key: 'view', label: 'View', variant: 'secondary' },
  { key: 'edit', label: 'Edit', variant: 'primary' },
  { key: 'activate', label: 'Activate', variant: 'success' },
  { key: 'deactivate', label: 'Deactivate', variant: 'warning' },
  { key: 'delete', label: 'Delete', variant: 'danger' },
];

// Filtering and sorting
const { sortedData, handleSort } = useTable(computed(() => authManagementStore.users));

const filteredUsers = computed(() => {
  let data = sortedData.value;
  
  // Search filter
  if (search.value) {
    const searchLower = search.value.toLowerCase();
    data = data.filter(user =>
      user.name?.toLowerCase().includes(searchLower) ||
      user.email?.toLowerCase().includes(searchLower)
    );
  }
  
  // Status filter
  if (statusFilter.value) {
    data = data.filter(user => user.status === statusFilter.value);
  }
  
  // Role filter
  if (roleFilter.value) {
    data = data.filter(user => 
      user.roles?.some(role => role.id === roleFilter.value)
    );
  }
  
  return data;
});

// Pagination
const pagination = usePagination(filteredUsers, 10);

const handlePageChange = (page) => {
  pagination.currentPage.value = page;
};

// Modal
const modalTitle = computed(() => 
  editingId.value ? 'Edit User' : 'Add User'
);

const openCreateModal = () => {
  resetForm();
  editingId.value = null;
  modal.open();
};

const resetForm = () => {
  form.value = {
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    status: 'active',
    role_ids: [],
  };
  errors.value = {};
};

// CRUD Operations
const fetchUsers = async () => {
  loading.value = true;
  try {
    await authManagementStore.fetchUsers();
  } catch (error) {
    showError('Failed to load users');
  } finally {
    loading.value = false;
  }
};

const fetchRoles = async () => {
  try {
    await authManagementStore.fetchRoles();
    availableRoles.value = authManagementStore.roles.map(role => ({
      label: role.name,
      value: role.id,
    }));
    roleOptions.value = [
      { label: 'All Roles', value: '' },
      ...availableRoles.value,
    ];
  } catch (error) {
    console.error('Failed to load roles');
  }
};

const handleSubmit = async () => {
  errors.value = {};
  saving.value = true;

  try {
    if (editingId.value) {
      await authManagementStore.updateUser(editingId.value, form.value);
      showSuccess('User updated successfully');
    } else {
      await authManagementStore.createUser(form.value);
      showSuccess('User created successfully');
    }
    
    modal.close();
    resetForm();
    await fetchUsers();
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    }
    showError(error.response?.data?.message || 'Operation failed');
  } finally {
    saving.value = false;
  }
};

const viewUser = (user) => {
  console.log('View user:', user);
  showError('User detail view not yet implemented');
};

const editUser = (user) => {
  editingId.value = user.id;
  form.value = {
    name: user.name,
    email: user.email,
    status: user.status,
    role_ids: user.roles?.map(r => r.id) || [],
  };
  modal.open();
};

const activateUser = async (user) => {
  try {
    await authManagementStore.activateUser(user.id);
    showSuccess('User activated successfully');
    await fetchUsers();
  } catch (error) {
    showError('Failed to activate user');
  }
};

const deactivateUser = async (user) => {
  try {
    await authManagementStore.deactivateUser(user.id);
    showSuccess('User deactivated successfully');
    await fetchUsers();
  } catch (error) {
    showError('Failed to deactivate user');
  }
};

const deleteUser = async (user) => {
  if (!confirm(`Are you sure you want to delete ${user.name}?`)) {
    return;
  }

  try {
    await authManagementStore.deleteUser(user.id);
    showSuccess('User deleted successfully');
    await fetchUsers();
  } catch (error) {
    showError('Failed to delete user');
  }
};

// Utilities
const getStatusVariant = (status) => {
  const variants = {
    active: 'success',
    inactive: 'secondary',
    suspended: 'danger',
  };
  return variants[status] || 'secondary';
};

// Lifecycle
onMounted(() => {
  fetchUsers();
  fetchRoles();
});
</script>
