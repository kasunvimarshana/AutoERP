<template>
  <div>
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Custom Dashboards</h1>
        <p class="mt-1 text-sm text-gray-500">Create and manage custom analytics dashboards</p>
      </div>
      <BaseButton variant="primary" @click="openCreateModal">
        Create Dashboard
      </BaseButton>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-blue-100 rounded-lg">
              <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Total Dashboards</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-green-100 rounded-lg">
              <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Public</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.public }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-yellow-100 rounded-lg">
              <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Private</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.private }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-purple-100 rounded-lg">
              <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Widgets</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.widgets }}</p>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- Filters -->
    <BaseCard class="mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <BaseInput
          v-model="search"
          placeholder="Search dashboards..."
          type="search"
        />
        <BaseSelect
          v-model="visibilityFilter"
          :options="visibilityOptions"
          placeholder="Filter by visibility"
        />
      </div>
    </BaseCard>

    <!-- Dashboards Table -->
    <BaseCard>
      <BaseTable
        :columns="columns"
        :data="filteredDashboards"
        :loading="loading"
        :actions="tableActions"
        @sort="handleSort"
        @action:view="viewDashboard"
        @action:edit="editDashboard"
        @action:set_default="setDefaultDashboardAction"
        @action:duplicate="duplicateDashboardAction"
        @action:share="shareDashboardAction"
        @action:delete="deleteDashboardAction"
      >
        <template #cell-dashboard_code="{ value }">
          <span class="font-medium text-gray-900">{{ value }}</span>
        </template>

        <template #cell-visibility="{ value }">
          <BaseBadge :variant="value === 'public' ? 'success' : 'warning'">
            {{ value }}
          </BaseBadge>
        </template>

        <template #cell-widget_count="{ value }">
          <span class="text-sm text-gray-600">{{ value || 0 }}</span>
        </template>

        <template #cell-created_by="{ row }">
          <span class="text-sm text-gray-600">
            {{ row.created_by?.name || 'System' }}
          </span>
        </template>

        <template #cell-last_updated="{ value }">
          <span class="text-sm text-gray-600">
            {{ formatDate(value) }}
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
            v-model="form.dashboard_code"
            label="Dashboard Code"
            required
            placeholder="DASH-001"
            :error="errors.dashboard_code"
          />

          <BaseInput
            v-model="form.name"
            label="Dashboard Name"
            required
            placeholder="Sales Performance Dashboard"
            :error="errors.name"
          />

          <BaseTextarea
            v-model="form.description"
            label="Description"
            placeholder="Dashboard description..."
            :rows="3"
          />

          <BaseSelect
            v-model="form.visibility"
            label="Visibility"
            :options="visibilityOptions"
            required
            :error="errors.visibility"
          />

          <BaseTextarea
            v-model="form.layout"
            label="Layout Configuration (JSON)"
            placeholder='{"widgets": [], "columns": 2}'
            :rows="6"
            :error="errors.layout"
          />
        </div>

        <div class="mt-6 flex justify-end space-x-3">
          <BaseButton type="button" variant="secondary" @click="modal.close">
            Cancel
          </BaseButton>
          <BaseButton type="submit" variant="primary" :loading="saving">
            {{ editingId ? 'Update' : 'Create' }} Dashboard
          </BaseButton>
        </div>
      </form>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useReportingStore } from '../stores/reportingStore';
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

const reportingStore = useReportingStore();
const modal = useModal();
const { showSuccess, showError } = useNotifications();

const loading = ref(false);
const saving = ref(false);
const search = ref('');
const visibilityFilter = ref('');
const editingId = ref(null);

const form = ref({
  dashboard_code: '',
  name: '',
  description: '',
  visibility: 'private',
  layout: '',
});

const errors = ref({});

const stats = computed(() => {
  const dashboards = reportingStore.dashboards || [];
  const totalWidgets = dashboards.reduce((sum, d) => sum + (d.widget_count || 0), 0);
  return {
    total: dashboards.length,
    public: dashboards.filter(d => d.visibility === 'public').length,
    private: dashboards.filter(d => d.visibility === 'private').length,
    widgets: totalWidgets,
  };
});

const visibilityOptions = [
  { label: 'All', value: '' },
  { label: 'Public', value: 'public' },
  { label: 'Private', value: 'private' },
];

const columns = [
  { key: 'dashboard_code', label: 'Code', sortable: true },
  { key: 'name', label: 'Dashboard Name', sortable: true },
  { key: 'visibility', label: 'Visibility', sortable: true },
  { key: 'widget_count', label: 'Widgets', sortable: true },
  { key: 'created_by', label: 'Created By', sortable: false },
  { key: 'last_updated', label: 'Last Updated', sortable: true },
];

const tableActions = () => [
  { key: 'view', label: 'View', icon: 'eye' },
  { key: 'edit', label: 'Edit', icon: 'pencil' },
  { key: 'set_default', label: 'Set as Default', icon: 'star' },
  { key: 'duplicate', label: 'Duplicate', icon: 'copy' },
  { key: 'share', label: 'Share', icon: 'share' },
  { key: 'delete', label: 'Delete', icon: 'trash', variant: 'danger' },
];

const { sortedData, handleSort } = useTable(computed(() => reportingStore.dashboards || []));

const filteredDashboards = computed(() => {
  let data = sortedData.value;

  if (search.value) {
    const searchLower = search.value.toLowerCase();
    data = data.filter(dashboard =>
      dashboard.dashboard_code?.toLowerCase().includes(searchLower) ||
      dashboard.name?.toLowerCase().includes(searchLower) ||
      dashboard.description?.toLowerCase().includes(searchLower)
    );
  }

  if (visibilityFilter.value) {
    data = data.filter(dashboard => dashboard.visibility === visibilityFilter.value);
  }

  return data;
});

const pagination = usePagination(filteredDashboards, 10);

const handlePageChange = (page) => {
  pagination.currentPage.value = page;
};

const modalTitle = computed(() => 
  editingId.value ? 'Edit Dashboard' : 'Create Dashboard'
);

const openCreateModal = () => {
  resetForm();
  editingId.value = null;
  modal.open();
};

const resetForm = () => {
  form.value = {
    dashboard_code: '',
    name: '',
    description: '',
    visibility: 'private',
    layout: '',
  };
  errors.value = {};
};

const fetchDashboards = async () => {
  loading.value = true;
  try {
    await reportingStore.fetchDashboards();
  } catch (error) {
    showError('Failed to load dashboards');
  } finally {
    loading.value = false;
  }
};

const handleSubmit = async () => {
  errors.value = {};
  saving.value = true;

  try {
    const data = { ...form.value };
    if (data.layout) {
      try {
        data.layout = JSON.parse(data.layout);
      } catch (e) {
        errors.value.layout = 'Invalid JSON format';
        saving.value = false;
        return;
      }
    }

    if (editingId.value) {
      await reportingStore.updateDashboard(editingId.value, data);
      showSuccess('Dashboard updated successfully');
    } else {
      await reportingStore.createDashboard(data);
      showSuccess('Dashboard created successfully');
    }
    
    modal.close();
    resetForm();
    await fetchDashboards();
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    }
    showError(error.response?.data?.message || 'Operation failed');
  } finally {
    saving.value = false;
  }
};

const viewDashboard = (dashboard) => {
  showError('Dashboard detail view not yet implemented');
};

const editDashboard = (dashboard) => {
  editingId.value = dashboard.id;
  form.value = {
    dashboard_code: dashboard.dashboard_code,
    name: dashboard.name,
    description: dashboard.description || '',
    visibility: dashboard.visibility,
    layout: dashboard.layout ? JSON.stringify(dashboard.layout, null, 2) : '',
  };
  modal.open();
};

const setDefaultDashboardAction = async (dashboard) => {
  if (!confirm(`Set "${dashboard.name}" as default dashboard?`)) return;

  try {
    await reportingStore.setDefaultDashboard(dashboard.id);
    showSuccess('Dashboard set as default successfully');
  } catch (error) {
    showError('Failed to set default dashboard');
  }
};

const duplicateDashboardAction = async (dashboard) => {
  try {
    await reportingStore.duplicateDashboard(dashboard.id);
    showSuccess('Dashboard duplicated successfully');
    await fetchDashboards();
  } catch (error) {
    showError('Failed to duplicate dashboard');
  }
};

const shareDashboardAction = async (dashboard) => {
  const userEmail = prompt('Enter email address to share with:');
  if (!userEmail) return;

  try {
    await reportingStore.shareDashboard(dashboard.id, { email: userEmail });
    showSuccess('Dashboard shared successfully');
  } catch (error) {
    showError('Failed to share dashboard');
  }
};

const deleteDashboardAction = async (dashboard) => {
  if (!confirm(`Are you sure you want to delete "${dashboard.name}"?`)) return;

  try {
    await reportingStore.deleteDashboard(dashboard.id);
    showSuccess('Dashboard deleted successfully');
    await fetchDashboards();
  } catch (error) {
    showError('Failed to delete dashboard');
  }
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

onMounted(() => {
  fetchDashboards();
});
</script>
