<template>
  <div>
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Organization Hierarchy</h1>
        <p class="mt-1 text-sm text-gray-500">Manage organizational structure and relationships</p>
      </div>
      <BaseButton variant="primary" @click="openCreateModal">
        Create Organization
      </BaseButton>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-blue-100 rounded-lg">
              <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Total Orgs</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-green-100 rounded-lg">
              <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m7-4a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Root Orgs</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.rootOrgs }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-purple-100 rounded-lg">
              <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Child Orgs</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.childOrgs }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-indigo-100 rounded-lg">
              <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Max Depth</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.maxDepth }}</p>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- Filters -->
    <BaseCard class="mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <BaseInput
          v-model="search"
          placeholder="Search organizations by code or name..."
          type="search"
        />
        <BaseSelect
          v-model="parentOrgFilter"
          :options="parentOrgOptions"
          placeholder="Filter by parent organization"
        />
      </div>
    </BaseCard>

    <!-- Organizations Table -->
    <BaseCard>
      <BaseTable
        :columns="columns"
        :data="filteredOrganizations"
        :loading="loading"
        :actions="tableActions"
        @sort="handleSort"
        @action:view="viewOrganization"
        @action:edit="editOrganization"
        @action:hierarchy="viewHierarchy"
        @action:move="moveOrganization"
        @action:delete="deleteOrganization"
      >
        <template #cell-org_code="{ value }">
          <span class="font-medium text-gray-900">{{ value }}</span>
        </template>

        <template #cell-status="{ value }">
          <BaseBadge :variant="getStatusVariant(value)">
            {{ formatStatus(value) }}
          </BaseBadge>
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
            v-model="form.org_code"
            label="Organization Code"
            required
            placeholder="ORG-001"
            :error="errors.org_code"
          />

          <BaseInput
            v-model="form.name"
            label="Organization Name"
            required
            placeholder="Enter organization name"
            :error="errors.name"
          />

          <BaseSelect
            v-model="form.parent_org_id"
            label="Parent Organization"
            :options="parentOrgSelectOptions"
            placeholder="Select parent organization (optional)"
            :error="errors.parent_org_id"
          />

          <BaseTextarea
            v-model="form.description"
            label="Description"
            placeholder="Enter organization description"
            :rows="3"
            :error="errors.description"
          />

          <BaseSelect
            v-model="form.status"
            label="Status"
            :options="statusOptionsForm"
            required
            :error="errors.status"
          />
        </div>

        <div class="mt-6 flex justify-end space-x-3">
          <BaseButton type="button" variant="secondary" @click="modal.close">
            Cancel
          </BaseButton>
          <BaseButton type="submit" variant="primary" :loading="saving">
            {{ editingId ? 'Update' : 'Create' }} Organization
          </BaseButton>
        </div>
      </form>
    </BaseModal>

    <!-- Hierarchy View Modal -->
    <BaseModal :show="hierarchyModal.isOpen" title="Organization Hierarchy" size="xl" @close="hierarchyModal.close">
      <div class="space-y-4">
        <div v-if="hierarchyLoading" class="text-center py-8">
          <p class="text-gray-500">Loading hierarchy...</p>
        </div>
        <div v-else-if="hierarchyData" class="space-y-2">
          <div v-for="item in hierarchyData" :key="item.id" :style="{ marginLeft: (item.level * 20) + 'px' }" class="p-2 bg-gray-50 rounded">
            <div class="flex items-center">
              <span class="font-medium">{{ item.name }}</span>
              <span class="ml-2 text-xs text-gray-500">({{ item.org_code }})</span>
              <span class="ml-2 text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded">Level {{ item.level }}</span>
            </div>
          </div>
        </div>
      </div>
    </BaseModal>

    <!-- Move Organization Modal -->
    <BaseModal :show="moveModal.isOpen" title="Move Organization" size="lg" @close="moveModal.close">
      <form @submit.prevent="handleMove">
        <div class="space-y-4">
          <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
            <p class="text-sm text-blue-800">
              <strong>Moving:</strong> {{ organizationToMove?.name }}
            </p>
          </div>

          <BaseSelect
            v-model="moveForm.new_parent_org_id"
            label="New Parent Organization"
            :options="parentOrgSelectOptionsForMove"
            placeholder="Select new parent organization"
            required
            :error="errors.new_parent_org_id"
          />
        </div>

        <div class="mt-6 flex justify-end space-x-3">
          <BaseButton type="button" variant="secondary" @click="moveModal.close">
            Cancel
          </BaseButton>
          <BaseButton type="submit" variant="primary" :loading="saving">
            Move Organization
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
const hierarchyModal = useModal();
const moveModal = useModal();
const { showSuccess, showError } = useNotifications();

// State
const loading = ref(false);
const saving = ref(false);
const hierarchyLoading = ref(false);
const search = ref('');
const parentOrgFilter = ref('');
const editingId = ref(null);
const hierarchyData = ref(null);
const organizationToMove = ref(null);

// Form
const form = ref({
  org_code: '',
  name: '',
  parent_org_id: '',
  description: '',
  status: 'active',
});

const moveForm = ref({
  new_parent_org_id: '',
});

const errors = ref({});

// Options
const statusOptionsForm = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
];

// Table configuration
const columns = [
  { key: 'org_code', label: 'Code', sortable: true },
  { key: 'name', label: 'Name', sortable: true },
  { key: 'parent_org', label: 'Parent Org', sortable: true },
  { key: 'level', label: 'Level', sortable: true },
  { key: 'user_count', label: 'Users', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
];

const tableActions = [
  { key: 'view', label: 'View', variant: 'secondary' },
  { key: 'edit', label: 'Edit', variant: 'primary' },
  { key: 'hierarchy', label: 'Hierarchy', variant: 'info', icon: 'tree' },
  { key: 'move', label: 'Move', variant: 'warning' },
  { key: 'delete', label: 'Delete', variant: 'danger' },
];

// Statistics
const stats = computed(() => {
  const organizations = tenantStore.organizations || [];
  const rootOrgs = organizations.filter(o => !o.parent_org_id || o.parent_org_id === null);
  const childOrgs = organizations.filter(o => o.parent_org_id);
  
  let maxDepth = 0;
  organizations.forEach(org => {
    if (org.level && org.level > maxDepth) {
      maxDepth = org.level;
    }
  });

  return {
    total: organizations.length,
    rootOrgs: rootOrgs.length,
    childOrgs: childOrgs.length,
    maxDepth: maxDepth,
  };
});

// Filtering and sorting
const { sortedData, handleSort } = useTable(computed(() => tenantStore.organizations || []));

const filteredOrganizations = computed(() => {
  let data = sortedData.value;

  // Search filter
  if (search.value) {
    const searchLower = search.value.toLowerCase();
    data = data.filter(org =>
      org.org_code?.toLowerCase().includes(searchLower) ||
      org.name?.toLowerCase().includes(searchLower)
    );
  }

  // Parent organization filter
  if (parentOrgFilter.value) {
    data = data.filter(org => org.parent_org_id === parseInt(parentOrgFilter.value));
  }

  return data;
});

// Pagination
const pagination = usePagination(filteredOrganizations, 10);

const handlePageChange = (page) => {
  pagination.currentPage.value = page;
};

// Parent organization options for filters
const parentOrgOptions = computed(() => {
  const organizations = tenantStore.organizations || [];
  const options = [
    { label: 'All Organizations', value: '' },
  ];
  
  organizations.forEach(org => {
    options.push({
      label: org.name,
      value: org.id.toString(),
    });
  });

  return options;
});

// Parent organization options for select dropdown
const parentOrgSelectOptions = computed(() => {
  const organizations = tenantStore.organizations || [];
  const options = [
    { label: 'None (Root Organization)', value: '' },
  ];
  
  organizations.forEach(org => {
    // Don't include the org being edited as its own parent
    if (!editingId.value || org.id !== editingId.value) {
      options.push({
        label: org.name,
        value: org.id.toString(),
      });
    }
  });

  return options;
});

// Parent organization options for move modal (excluding current org and its children)
const parentOrgSelectOptionsForMove = computed(() => {
  const organizations = tenantStore.organizations || [];
  const options = [
    { label: 'None (Root Organization)', value: '' },
  ];
  
  organizations.forEach(org => {
    // Don't include the org being moved or any of its children
    if (!organizationToMove.value || (org.id !== organizationToMove.value.id && org.parent_org_id !== organizationToMove.value.id)) {
      options.push({
        label: org.name,
        value: org.id.toString(),
      });
    }
  });

  return options;
});

// Modal
const modalTitle = computed(() => 
  editingId.value ? 'Edit Organization' : 'Create Organization'
);

const openCreateModal = () => {
  resetForm();
  editingId.value = null;
  modal.open();
};

const resetForm = () => {
  form.value = {
    org_code: '',
    name: '',
    parent_org_id: '',
    description: '',
    status: 'active',
  };
  errors.value = {};
};

// CRUD Operations
const fetchOrganizations = async () => {
  loading.value = true;
  try {
    await tenantStore.fetchOrganizations();
  } catch (error) {
    showError('Failed to load organizations');
  } finally {
    loading.value = false;
  }
};

const handleSubmit = async () => {
  errors.value = {};
  saving.value = true;

  try {
    const submitData = {
      org_code: form.value.org_code,
      name: form.value.name,
      parent_org_id: form.value.parent_org_id ? parseInt(form.value.parent_org_id) : null,
      description: form.value.description,
      status: form.value.status,
    };

    if (editingId.value) {
      await tenantStore.updateOrganization(editingId.value, submitData);
      showSuccess('Organization updated successfully');
    } else {
      await tenantStore.createOrganization(submitData);
      showSuccess('Organization created successfully');
    }
    
    modal.close();
    resetForm();
    await fetchOrganizations();
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    }
    showError(error.response?.data?.message || 'Operation failed');
  } finally {
    saving.value = false;
  }
};

const viewOrganization = (organization) => {
  console.log('View organization:', organization);
  showError('Organization detail view not yet implemented');
};

const editOrganization = (organization) => {
  editingId.value = organization.id;
  form.value = {
    org_code: organization.org_code || '',
    name: organization.name || '',
    parent_org_id: organization.parent_org_id ? organization.parent_org_id.toString() : '',
    description: organization.description || '',
    status: organization.status || 'active',
  };
  modal.open();
};

const viewHierarchy = async (organization) => {
  hierarchyLoading.value = true;
  try {
    const response = await tenantStore.fetchOrganizationHierarchy(organization.id);
    hierarchyData.value = response.data || [];
    hierarchyModal.open();
  } catch (error) {
    showError('Failed to load organization hierarchy');
  } finally {
    hierarchyLoading.value = false;
  }
};

const moveOrganization = (organization) => {
  organizationToMove.value = organization;
  moveForm.value = {
    new_parent_org_id: organization.parent_org_id ? organization.parent_org_id.toString() : '',
  };
  errors.value = {};
  moveModal.open();
};

const handleMove = async () => {
  errors.value = {};
  saving.value = true;

  try {
    if (!organizationToMove.value) {
      throw new Error('No organization selected for move');
    }

    const moveData = {
      parent_org_id: moveForm.value.new_parent_org_id ? parseInt(moveForm.value.new_parent_org_id) : null,
    };

    await tenantStore.updateOrganization(organizationToMove.value.id, moveData);
    showSuccess('Organization moved successfully');
    moveModal.close();
    organizationToMove.value = null;
    await fetchOrganizations();
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    }
    showError(error.response?.data?.message || 'Failed to move organization');
  } finally {
    saving.value = false;
  }
};

const deleteOrganization = async (organization) => {
  if (!confirm(`Are you sure you want to delete ${organization.name}? This action cannot be undone.`)) {
    return;
  }

  try {
    await tenantStore.deleteOrganization(organization.id);
    showSuccess('Organization deleted successfully');
    await fetchOrganizations();
  } catch (error) {
    showError('Failed to delete organization');
  }
};

// Utilities
const getStatusVariant = (status) => {
  const variants = {
    active: 'success',
    inactive: 'secondary',
  };
  return variants[status] || 'secondary';
};

const formatStatus = (status) => {
  const labels = {
    active: 'Active',
    inactive: 'Inactive',
  };
  return labels[status] || status;
};

// Lifecycle
onMounted(() => {
  fetchOrganizations();
});
</script>
