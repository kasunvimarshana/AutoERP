<template>
  <div>
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Business Reports & Analytics</h1>
        <p class="mt-1 text-sm text-gray-500">Manage reports, schedules, and analytics</p>
      </div>
      <BaseButton variant="primary" @click="openCreateModal">
        Create Report
      </BaseButton>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-blue-100 rounded-lg">
              <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Total Reports</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-green-100 rounded-lg">
              <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Saved</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.saved }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-purple-100 rounded-lg">
              <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Scheduled</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.scheduled }}</p>
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
            <p class="text-sm font-medium text-gray-500">Executed Today</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.executedToday }}</p>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- Filters -->
    <BaseCard class="mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <BaseInput
          v-model="search"
          placeholder="Search reports..."
          type="search"
        />
        <BaseSelect
          v-model="typeFilter"
          :options="typeOptions"
          placeholder="Filter by type"
        />
        <BaseSelect
          v-model="formatFilter"
          :options="formatOptions"
          placeholder="Filter by format"
        />
      </div>
    </BaseCard>

    <!-- Reports Table -->
    <BaseCard>
      <BaseTable
        :columns="columns"
        :data="filteredReports"
        :loading="loading"
        :actions="tableActions"
        @sort="handleSort"
        @action:view="viewReport"
        @action:edit="editReport"
        @action:execute="executeReportAction"
        @action:schedule="scheduleReportAction"
        @action:download="downloadReport"
        @action:delete="deleteReportAction"
      >
        <template #cell-report_code="{ value }">
          <span class="font-medium text-gray-900">{{ value }}</span>
        </template>

        <template #cell-type="{ value }">
          <BaseBadge :variant="getTypeVariant(value)">
            {{ formatType(value) }}
          </BaseBadge>
        </template>

        <template #cell-format="{ value }">
          <span class="text-sm text-gray-600 uppercase">{{ value }}</span>
        </template>

        <template #cell-last_executed="{ value }">
          <span class="text-sm text-gray-600">
            {{ value ? formatDate(value) : 'Never' }}
          </span>
        </template>

        <template #cell-status="{ value }">
          <BaseBadge :variant="value === 'active' ? 'success' : 'secondary'">
            {{ value }}
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
          <div class="grid grid-cols-2 gap-4">
            <BaseInput
              v-model="form.report_code"
              label="Report Code"
              required
              placeholder="RPT-001"
              :error="errors.report_code"
            />

            <BaseSelect
              v-model="form.type"
              label="Report Type"
              :options="typeOptions"
              required
              :error="errors.type"
            />
          </div>

          <BaseInput
            v-model="form.name"
            label="Report Name"
            required
            placeholder="Monthly Sales Report"
            :error="errors.name"
          />

          <BaseTextarea
            v-model="form.description"
            label="Description"
            placeholder="Report description..."
            :rows="3"
          />

          <BaseSelect
            v-model="form.format"
            label="Output Format"
            :options="formatOptions"
            required
            :error="errors.format"
          />

          <BaseTextarea
            v-model="form.query_params"
            label="Query Parameters (JSON)"
            placeholder='{"period": "monthly", "year": 2024}'
            :rows="4"
            :error="errors.query_params"
          />

          <BaseInput
            v-model="form.schedule_frequency"
            label="Schedule Frequency"
            placeholder="daily, weekly, monthly"
          />
        </div>

        <div class="mt-6 flex justify-end space-x-3">
          <BaseButton type="button" variant="secondary" @click="modal.close">
            Cancel
          </BaseButton>
          <BaseButton type="submit" variant="primary" :loading="saving">
            {{ editingId ? 'Update' : 'Create' }} Report
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
const typeFilter = ref('');
const formatFilter = ref('');
const editingId = ref(null);

const form = ref({
  report_code: '',
  name: '',
  description: '',
  type: 'sales',
  format: 'pdf',
  query_params: '',
  schedule_frequency: '',
});

const errors = ref({});

const stats = computed(() => {
  const reports = reportingStore.reports || [];
  const today = new Date().toDateString();
  return {
    total: reports.length,
    saved: reports.filter(r => r.is_saved).length,
    scheduled: reports.filter(r => r.schedule_frequency).length,
    executedToday: reports.filter(r => r.last_executed && new Date(r.last_executed).toDateString() === today).length,
  };
});

const typeOptions = [
  { label: 'All Types', value: '' },
  { label: 'Sales', value: 'sales' },
  { label: 'Purchase', value: 'purchase' },
  { label: 'Inventory', value: 'inventory' },
  { label: 'Financial', value: 'financial' },
  { label: 'Custom', value: 'custom' },
];

const formatOptions = [
  { label: 'All Formats', value: '' },
  { label: 'PDF', value: 'pdf' },
  { label: 'CSV', value: 'csv' },
  { label: 'JSON', value: 'json' },
  { label: 'Excel', value: 'xlsx' },
];

const columns = [
  { key: 'report_code', label: 'Code', sortable: true },
  { key: 'name', label: 'Report Name', sortable: true },
  { key: 'type', label: 'Type', sortable: true },
  { key: 'format', label: 'Format', sortable: true },
  { key: 'last_executed', label: 'Last Executed', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
];

const tableActions = () => [
  { key: 'view', label: 'View', icon: 'eye' },
  { key: 'edit', label: 'Edit', icon: 'pencil' },
  { key: 'execute', label: 'Execute', icon: 'play' },
  { key: 'schedule', label: 'Schedule', icon: 'clock' },
  { key: 'download', label: 'Download', icon: 'download' },
  { key: 'delete', label: 'Delete', icon: 'trash', variant: 'danger' },
];

const { sortedData, handleSort } = useTable(computed(() => reportingStore.reports || []));

const filteredReports = computed(() => {
  let data = sortedData.value;

  if (search.value) {
    const searchLower = search.value.toLowerCase();
    data = data.filter(report =>
      report.report_code?.toLowerCase().includes(searchLower) ||
      report.name?.toLowerCase().includes(searchLower) ||
      report.description?.toLowerCase().includes(searchLower)
    );
  }

  if (typeFilter.value) {
    data = data.filter(report => report.type === typeFilter.value);
  }

  if (formatFilter.value) {
    data = data.filter(report => report.format === formatFilter.value);
  }

  return data;
});

const pagination = usePagination(filteredReports, 10);

const handlePageChange = (page) => {
  pagination.currentPage.value = page;
};

const modalTitle = computed(() => 
  editingId.value ? 'Edit Report' : 'Create Report'
);

const openCreateModal = () => {
  resetForm();
  editingId.value = null;
  modal.open();
};

const resetForm = () => {
  form.value = {
    report_code: '',
    name: '',
    description: '',
    type: 'sales',
    format: 'pdf',
    query_params: '',
    schedule_frequency: '',
  };
  errors.value = {};
};

const fetchReports = async () => {
  loading.value = true;
  try {
    await reportingStore.fetchReports();
  } catch (error) {
    showError('Failed to load reports');
  } finally {
    loading.value = false;
  }
};

const handleSubmit = async () => {
  errors.value = {};
  saving.value = true;

  try {
    const data = { ...form.value };
    if (data.query_params) {
      try {
        data.query_params = JSON.parse(data.query_params);
      } catch (e) {
        errors.value.query_params = 'Invalid JSON format';
        saving.value = false;
        return;
      }
    }

    if (editingId.value) {
      await reportingStore.updateReport(editingId.value, data);
      showSuccess('Report updated successfully');
    } else {
      await reportingStore.createReport(data);
      showSuccess('Report created successfully');
    }
    
    modal.close();
    resetForm();
    await fetchReports();
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    }
    showError(error.response?.data?.message || 'Operation failed');
  } finally {
    saving.value = false;
  }
};

const viewReport = (report) => {
  showError('Report detail view not yet implemented');
};

const editReport = (report) => {
  editingId.value = report.id;
  form.value = {
    report_code: report.report_code,
    name: report.name,
    description: report.description || '',
    type: report.type,
    format: report.format,
    query_params: report.query_params ? JSON.stringify(report.query_params, null, 2) : '',
    schedule_frequency: report.schedule_frequency || '',
  };
  modal.open();
};

const executeReportAction = async (report) => {
  if (!confirm(`Execute report "${report.name}"?`)) return;

  try {
    await reportingStore.executeReport(report.id);
    showSuccess('Report executed successfully');
    await fetchReports();
  } catch (error) {
    showError('Failed to execute report');
  }
};

const scheduleReportAction = async (report) => {
  const frequency = prompt('Enter schedule frequency (daily, weekly, monthly):', report.schedule_frequency || 'daily');
  if (!frequency) return;

  try {
    await reportingStore.scheduleReport(report.id, { frequency });
    showSuccess('Report scheduled successfully');
    await fetchReports();
  } catch (error) {
    showError('Failed to schedule report');
  }
};

const downloadReport = async (report) => {
  try {
    await reportingStore.exportReport(report.id, report.format);
    showSuccess('Report downloaded successfully');
  } catch (error) {
    showError('Failed to download report');
  }
};

const deleteReportAction = async (report) => {
  if (!confirm(`Are you sure you want to delete "${report.name}"?`)) return;

  try {
    await reportingStore.deleteReport(report.id);
    showSuccess('Report deleted successfully');
    await fetchReports();
  } catch (error) {
    showError('Failed to delete report');
  }
};

const getTypeVariant = (type) => {
  const variants = {
    sales: 'success',
    purchase: 'info',
    inventory: 'warning',
    financial: 'primary',
    custom: 'secondary',
  };
  return variants[type] || 'secondary';
};

const formatType = (type) => {
  return type.charAt(0).toUpperCase() + type.slice(1);
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

onMounted(() => {
  fetchReports();
});
</script>
