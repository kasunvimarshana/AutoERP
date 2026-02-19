<template>
  <div>
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Roles & Permissions</h1>
        <p class="mt-1 text-sm text-gray-500">Manage roles and their permissions</p>
      </div>
      <BaseButton variant="primary" @click="openCreateModal">
        Add Role
      </BaseButton>
    </div>

    <!-- Filters -->
    <BaseCard class="mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <BaseInput
          v-model="search"
          placeholder="Search roles..."
          type="search"
        />
      </div>
    </BaseCard>

    <!-- Roles Table -->
    <BaseCard>
      <BaseTable
        :columns="columns"
        :data="filteredRoles"
        :loading="loading"
        :actions="tableActions"
        @sort="handleSort"
        @action:view="viewRole"
        @action:edit="editRole"
        @action:permissions="managePermissions"
        @action:delete="deleteRole"
      >
        <template #cell-permissions_count="{ row }">
          <BaseBadge variant="secondary">
            {{ row.permissions?.length || 0 }} permissions
          </BaseBadge>
        </template>
        
        <template #cell-users_count="{ row }">
          <span class="text-gray-600">
            {{ row.users_count || 0 }} users
          </span>
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
            label="Role Name"
            required
            placeholder="Enter role name"
            :error="errors.name"
          />
          
          <BaseInput
            v-model="form.slug"
            label="Slug"
            required
            placeholder="role-slug"
            :error="errors.slug"
          />
          
          <BaseTextarea
            v-model="form.description"
            label="Description"
            placeholder="Enter role description"
            :rows="3"
          />
        </div>

        <div class="mt-6 flex justify-end space-x-3">
          <BaseButton type="button" variant="secondary" @click="modal.close">
            Cancel
          </BaseButton>
          <BaseButton type="submit" variant="primary" :loading="saving">
            {{ editingId ? 'Update' : 'Create' }} Role
          </BaseButton>
        </div>
      </form>
    </BaseModal>

    <!-- Permissions Modal -->
    <BaseModal :show="permissionsModal.isOpen" title="Manage Permissions" size="xl" @close="permissionsModal.close">
      <div v-if="selectedRole" class="space-y-4">
        <div class="flex items-center justify-between pb-4 border-b">
          <div>
            <h3 class="text-lg font-semibold">{{ selectedRole.name }}</h3>
            <p class="text-sm text-gray-500">Select permissions for this role</p>
          </div>
        </div>

        <div class="max-h-96 overflow-y-auto space-y-2">
          <label v-for="permission in availablePermissions" :key="permission.id" class="flex items-center p-2 hover:bg-gray-50 rounded">
            <input
              v-model="selectedPermissionIds"
              :value="permission.id"
              type="checkbox"
              class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
            />
            <div class="ml-3">
              <span class="text-sm font-medium text-gray-700">{{ permission.name }}</span>
              <p v-if="permission.description" class="text-xs text-gray-500">{{ permission.description }}</p>
            </div>
          </label>
        </div>

        <div class="mt-6 flex justify-end space-x-3 pt-4 border-t">
          <BaseButton type="button" variant="secondary" @click="permissionsModal.close">
            Cancel
          </BaseButton>
          <BaseButton variant="primary" :loading="saving" @click="savePermissions">
            Save Permissions
          </BaseButton>
        </div>
      </div>
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
import BaseTextarea from '@/components/common/BaseTextarea.vue';
import BaseTable from '@/components/data/BaseTable.vue';
import BasePagination from '@/components/data/BasePagination.vue';
import BaseBadge from '@/components/common/BaseBadge.vue';
import BaseModal from '@/components/layout/BaseModal.vue';

const authManagementStore = useAuthManagementStore();
const modal = useModal();
const permissionsModal = useModal();
const { showSuccess, showError } = useNotifications();

// State
const loading = ref(false);
const saving = ref(false);
const search = ref('');
const editingId = ref(null);
const selectedRole = ref(null);
const selectedPermissionIds = ref([]);
const availablePermissions = ref([]);

// Form
const form = ref({
  name: '',
  slug: '',
  description: '',
});

const errors = ref({});

// Table configuration
const columns = [
  { key: 'name', label: 'Name', sortable: true },
  { key: 'slug', label: 'Slug', sortable: true },
  { key: 'description', label: 'Description', sortable: false },
  { key: 'permissions_count', label: 'Permissions', sortable: false },
  { key: 'users_count', label: 'Users', sortable: false },
];

const tableActions = [
  { key: 'view', label: 'View', variant: 'secondary' },
  { key: 'edit', label: 'Edit', variant: 'primary' },
  { key: 'permissions', label: 'Permissions', variant: 'info' },
  { key: 'delete', label: 'Delete', variant: 'danger' },
];

// Filtering and sorting
const { sortedData, handleSort } = useTable(computed(() => authManagementStore.roles));

const filteredRoles = computed(() => {
  let data = sortedData.value;
  
  // Search filter
  if (search.value) {
    const searchLower = search.value.toLowerCase();
    data = data.filter(role =>
      role.name?.toLowerCase().includes(searchLower) ||
      role.slug?.toLowerCase().includes(searchLower) ||
      role.description?.toLowerCase().includes(searchLower)
    );
  }
  
  return data;
});

// Pagination
const pagination = usePagination(filteredRoles, 10);

const handlePageChange = (page) => {
  pagination.currentPage.value = page;
};

// Modal
const modalTitle = computed(() => 
  editingId.value ? 'Edit Role' : 'Add Role'
);

const openCreateModal = () => {
  resetForm();
  editingId.value = null;
  modal.open();
};

const resetForm = () => {
  form.value = {
    name: '',
    slug: '',
    description: '',
  };
  errors.value = {};
};

// CRUD Operations
const fetchRoles = async () => {
  loading.value = true;
  try {
    await authManagementStore.fetchRoles();
  } catch (error) {
    showError('Failed to load roles');
  } finally {
    loading.value = false;
  }
};

const fetchPermissions = async () => {
  try {
    await authManagementStore.fetchPermissions();
    availablePermissions.value = authManagementStore.permissions;
  } catch (error) {
    console.error('Failed to load permissions');
  }
};

const handleSubmit = async () => {
  errors.value = {};
  saving.value = true;

  try {
    if (editingId.value) {
      await authManagementStore.updateRole(editingId.value, form.value);
      showSuccess('Role updated successfully');
    } else {
      await authManagementStore.createRole(form.value);
      showSuccess('Role created successfully');
    }
    
    modal.close();
    resetForm();
    await fetchRoles();
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    }
    showError(error.response?.data?.message || 'Operation failed');
  } finally {
    saving.value = false;
  }
};

const viewRole = (role) => {
  console.log('View role:', role);
  showError('Role detail view not yet implemented');
};

const editRole = (role) => {
  editingId.value = role.id;
  form.value = {
    name: role.name,
    slug: role.slug,
    description: role.description,
  };
  modal.open();
};

const managePermissions = (role) => {
  selectedRole.value = role;
  selectedPermissionIds.value = role.permissions?.map(p => p.id) || [];
  permissionsModal.open();
};

const savePermissions = async () => {
  if (!selectedRole.value) return;
  
  saving.value = true;
  try {
    await authManagementStore.assignPermissionsToRole(
      selectedRole.value.id,
      selectedPermissionIds.value
    );
    showSuccess('Permissions updated successfully');
    permissionsModal.close();
    await fetchRoles();
  } catch (error) {
    showError('Failed to update permissions');
  } finally {
    saving.value = false;
  }
};

const deleteRole = async (role) => {
  if (!confirm(`Are you sure you want to delete ${role.name}?`)) {
    return;
  }

  try {
    await authManagementStore.deleteRole(role.id);
    showSuccess('Role deleted successfully');
    await fetchRoles();
  } catch (error) {
    showError('Failed to delete role');
  }
};

// Lifecycle
onMounted(() => {
  fetchRoles();
  fetchPermissions();
});
</script>
