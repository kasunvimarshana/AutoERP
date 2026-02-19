<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Process Automation</h1>
        <p class="mt-1 text-sm text-gray-500">Manage workflow definitions and instances</p>
      </div>
      <BaseButton variant="primary" @click="openCreateModal">
        Create Workflow
      </BaseButton>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-blue-100 rounded-lg">
              <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Total Workflows</p>
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Running Instances</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.running }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-indigo-100 rounded-lg">
              <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Completed Today</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.completedToday }}</p>
          </div>
        </div>
      </BaseCard>
    </div>

    <BaseCard class="mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <BaseInput
          v-model="search"
          placeholder="Search workflows..."
          type="search"
        />
        <BaseSelect
          v-model="statusFilter"
          :options="statusOptions"
          placeholder="Filter by status"
        />
        <BaseSelect
          v-model="typeFilter"
          :options="typeOptions"
          placeholder="Filter by type"
        />
      </div>
    </BaseCard>

    <BaseCard>
      <BaseTable
        :columns="columns"
        :data="filteredWorkflows"
        :loading="loading"
        :actions="tableActions"
        @sort="handleSort"
        @action:view="viewWorkflow"
        @action:edit="editWorkflow"
        @action:activate="activateWorkflow"
        @action:deactivate="deactivateWorkflow"
        @action:execute="executeWorkflow"
        @action:view_instances="viewInstances"
        @action:delete="deleteWorkflowAction"
      >
        <template #cell-workflow_code="{ value }">
          <span class="font-medium text-gray-900">{{ value }}</span>
        </template>

        <template #cell-status="{ value }">
          <BaseBadge :variant="value === 'active' ? 'success' : 'secondary'">
            {{ value }}
          </BaseBadge>
        </template>

        <template #cell-instance_count="{ value }">
          <span class="text-sm text-gray-600">{{ value || 0 }}</span>
        </template>

        <template #cell-last_executed="{ value }">
          <span class="text-sm text-gray-600">
            {{ value ? formatDate(value) : 'Never' }}
          </span>
        </template>

        <template #cell-created_at="{ value }">
          <span class="text-sm text-gray-600">{{ formatDate(value) }}</span>
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

    <BaseModal :show="modal.isOpen" :title="modalTitle" size="lg" @close="modal.close">
      <form @submit.prevent="handleSubmit">
        <div class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <BaseInput
              v-model="form.workflow_code"
              label="Workflow Code"
              required
              placeholder="WF-001"
              :error="errors.workflow_code"
            />

            <BaseSelect
              v-model="form.status"
              label="Status"
              :options="statusOptions"
              required
              :error="errors.status"
            />
          </div>

          <BaseInput
            v-model="form.name"
            label="Workflow Name"
            required
            placeholder="Order Approval Workflow"
            :error="errors.name"
          />

          <BaseTextarea
            v-model="form.description"
            label="Description"
            placeholder="Workflow description..."
            :rows="3"
          />

          <BaseInput
            v-model="form.trigger_type"
            label="Trigger Type"
            placeholder="manual, automatic, scheduled"
          />

          <BaseTextarea
            v-model="form.steps"
            label="Workflow Steps (JSON)"
            placeholder='[{"step": "approval", "assignee": "manager"}]'
            :rows="6"
            :error="errors.steps"
          />
        </div>

        <div class="mt-6 flex justify-end space-x-3">
          <BaseButton type="button" variant="secondary" @click="modal.close">
            Cancel
          </BaseButton>
          <BaseButton type="submit" variant="primary" :loading="saving">
            {{ editingId ? 'Update' : 'Create' }} Workflow
          </BaseButton>
        </div>
      </form>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useWorkflowStore } from '../stores/workflowStore';
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

const workflowStore = useWorkflowStore();
const modal = useModal();
const { showSuccess, showError } = useNotifications();

const loading = ref(false);
const saving = ref(false);
const search = ref('');
const statusFilter = ref('');
const typeFilter = ref('');
const editingId = ref(null);

const form = ref({
  workflow_code: '',
  name: '',
  description: '',
  status: 'active',
  trigger_type: '',
  steps: '',
});

const errors = ref({});

const stats = computed(() => {
  const workflows = workflowStore.definitions || [];
  const instances = workflowStore.instances || [];
  const today = new Date().toDateString();
  return {
    total: workflows.length,
    active: workflows.filter(w => w.status === 'active').length,
    running: instances.filter(i => i.status === 'running').length,
    completedToday: instances.filter(i => i.completed_at && new Date(i.completed_at).toDateString() === today).length,
  };
});

const statusOptions = [
  { label: 'All Statuses', value: '' },
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
];

const typeOptions = [
  { label: 'All Types', value: '' },
  { label: 'Approval', value: 'approval' },
  { label: 'Notification', value: 'notification' },
  { label: 'Data Processing', value: 'data_processing' },
];

const columns = [
  { key: 'workflow_code', label: 'Code', sortable: true },
  { key: 'name', label: 'Workflow Name', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'instance_count', label: 'Instances', sortable: true },
  { key: 'last_executed', label: 'Last Executed', sortable: true },
  { key: 'created_at', label: 'Created At', sortable: true },
];

const tableActions = (row) => [
  { key: 'view', label: 'View', icon: 'eye' },
  { key: 'edit', label: 'Edit', icon: 'pencil' },
  { 
    key: row.status === 'active' ? 'deactivate' : 'activate', 
    label: row.status === 'active' ? 'Deactivate' : 'Activate', 
    icon: 'power'
  },
  { key: 'execute', label: 'Execute', icon: 'play' },
  { key: 'view_instances', label: 'View Instances', icon: 'list' },
  { key: 'delete', label: 'Delete', icon: 'trash', variant: 'danger' },
];

const { sortedData, handleSort } = useTable(computed(() => workflowStore.definitions || []));

const filteredWorkflows = computed(() => {
  let data = sortedData.value;

  if (search.value) {
    const searchLower = search.value.toLowerCase();
    data = data.filter(workflow =>
      workflow.workflow_code?.toLowerCase().includes(searchLower) ||
      workflow.name?.toLowerCase().includes(searchLower) ||
      workflow.description?.toLowerCase().includes(searchLower)
    );
  }

  if (statusFilter.value) {
    data = data.filter(workflow => workflow.status === statusFilter.value);
  }

  if (typeFilter.value) {
    data = data.filter(workflow => workflow.type === typeFilter.value);
  }

  return data;
});

const pagination = usePagination(filteredWorkflows, 10);

const handlePageChange = (page) => {
  pagination.currentPage.value = page;
};

const modalTitle = computed(() => 
  editingId.value ? 'Edit Workflow' : 'Create Workflow'
);

const openCreateModal = () => {
  resetForm();
  editingId.value = null;
  modal.open();
};

const resetForm = () => {
  form.value = {
    workflow_code: '',
    name: '',
    description: '',
    status: 'active',
    trigger_type: '',
    steps: '',
  };
  errors.value = {};
};

const fetchWorkflows = async () => {
  loading.value = true;
  try {
    await workflowStore.fetchDefinitions();
    await workflowStore.fetchInstances();
  } catch (error) {
    showError('Failed to load workflows');
  } finally {
    loading.value = false;
  }
};

const handleSubmit = async () => {
  errors.value = {};
  saving.value = true;

  try {
    const data = { ...form.value };
    if (data.steps) {
      try {
        data.steps = JSON.parse(data.steps);
      } catch (e) {
        errors.value.steps = 'Invalid JSON format';
        saving.value = false;
        return;
      }
    }

    if (editingId.value) {
      await workflowStore.updateDefinition(editingId.value, data);
      showSuccess('Workflow updated successfully');
    } else {
      await workflowStore.createDefinition(data);
      showSuccess('Workflow created successfully');
    }
    
    modal.close();
    resetForm();
    await fetchWorkflows();
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    }
    showError(error.response?.data?.message || 'Operation failed');
  } finally {
    saving.value = false;
  }
};

const viewWorkflow = (workflow) => {
  showError('Workflow detail view not yet implemented');
};

const editWorkflow = (workflow) => {
  editingId.value = workflow.id;
  form.value = {
    workflow_code: workflow.workflow_code,
    name: workflow.name,
    description: workflow.description || '',
    status: workflow.status,
    trigger_type: workflow.trigger_type || '',
    steps: workflow.steps ? JSON.stringify(workflow.steps, null, 2) : '',
  };
  modal.open();
};

const activateWorkflow = async (workflow) => {
  if (!confirm(`Activate workflow "${workflow.name}"?`)) return;

  try {
    await workflowStore.activateDefinition(workflow.id);
    showSuccess('Workflow activated successfully');
    await fetchWorkflows();
  } catch (error) {
    showError('Failed to activate workflow');
  }
};

const deactivateWorkflow = async (workflow) => {
  if (!confirm(`Deactivate workflow "${workflow.name}"?`)) return;

  try {
    await workflowStore.deactivateDefinition(workflow.id);
    showSuccess('Workflow deactivated successfully');
    await fetchWorkflows();
  } catch (error) {
    showError('Failed to deactivate workflow');
  }
};

const executeWorkflow = async (workflow) => {
  if (!confirm(`Execute workflow "${workflow.name}"?`)) return;

  try {
    await workflowStore.executeDefinition(workflow.id);
    showSuccess('Workflow executed successfully');
    await fetchWorkflows();
  } catch (error) {
    showError('Failed to execute workflow');
  }
};

const viewInstances = (workflow) => {
  showError('Workflow instances view not yet implemented');
};

const deleteWorkflowAction = async (workflow) => {
  if (!confirm(`Are you sure you want to delete "${workflow.name}"?`)) return;

  try {
    await workflowStore.deleteDefinition(workflow.id);
    showSuccess('Workflow deleted successfully');
    await fetchWorkflows();
  } catch (error) {
    showError('Failed to delete workflow');
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
  fetchWorkflows();
});
</script>
