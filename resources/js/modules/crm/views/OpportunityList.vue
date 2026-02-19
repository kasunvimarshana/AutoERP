<template>
  <div>
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Opportunities</h1>
        <p class="mt-1 text-sm text-gray-500">Track sales opportunities and pipeline</p>
      </div>
      <BaseButton variant="primary" @click="openCreateModal">
        Add Opportunity
      </BaseButton>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <BaseCard>
        <div class="text-center">
          <p class="text-sm text-gray-500">Total Value</p>
          <p class="text-2xl font-bold text-gray-900">${{ totalValue.toLocaleString() }}</p>
        </div>
      </BaseCard>
      <BaseCard>
        <div class="text-center">
          <p class="text-sm text-gray-500">Opportunities</p>
          <p class="text-2xl font-bold text-gray-900">{{ filteredOpportunities.length }}</p>
        </div>
      </BaseCard>
      <BaseCard>
        <div class="text-center">
          <p class="text-sm text-gray-500">Avg. Value</p>
          <p class="text-2xl font-bold text-gray-900">${{ avgValue.toLocaleString() }}</p>
        </div>
      </BaseCard>
      <BaseCard>
        <div class="text-center">
          <p class="text-sm text-gray-500">Win Rate</p>
          <p class="text-2xl font-bold text-gray-900">{{ winRate }}%</p>
        </div>
      </BaseCard>
    </div>

    <!-- Filters -->
    <BaseCard class="mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <BaseInput
          v-model="search"
          placeholder="Search opportunities..."
          type="search"
        />
        <BaseSelect
          v-model="stageFilter"
          :options="stageOptions"
          placeholder="Filter by stage"
        />
        <BaseSelect
          v-model="probabilityFilter"
          :options="probabilityOptions"
          placeholder="Filter by probability"
        />
      </div>
    </BaseCard>

    <!-- Opportunities Table -->
    <BaseCard>
      <BaseTable
        :columns="columns"
        :data="paginatedOpportunities"
        :loading="loading"
        :actions="tableActions"
        @sort="handleSort"
        @action:view="viewOpportunity"
        @action:edit="editOpportunity"
        @action:stage="updateStage"
        @action:delete="deleteOpportunity"
      >
        <template #cell-stage="{ value }">
          <BaseBadge :variant="getStageVariant(value)">
            {{ value }}
          </BaseBadge>
        </template>
        
        <template #cell-value="{ value }">
          <span class="font-semibold text-gray-900">${{ Number(value).toLocaleString() }}</span>
        </template>

        <template #cell-probability="{ value }">
          <div class="flex items-center">
            <div class="w-full bg-gray-200 rounded-full h-2 mr-2">
              <div class="bg-green-600 h-2 rounded-full" :style="{ width: `${value}%` }"></div>
            </div>
            <span class="text-sm text-gray-600">{{ value }}%</span>
          </div>
        </template>

        <template #cell-expected_close_date="{ value }">
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

    <!-- Create/Edit Modal -->
    <BaseModal :show="modal.isOpen" :title="modalTitle" size="lg" @close="modal.close">
      <form @submit.prevent="handleSubmit">
        <div class="space-y-4">
          <BaseInput
            v-model="form.name"
            label="Opportunity Name"
            required
            placeholder="Enter opportunity name"
            :error="errors.name"
          />
          
          <BaseInput
            v-model.number="form.value"
            label="Value"
            type="number"
            step="0.01"
            required
            placeholder="0.00"
            :error="errors.value"
          />
          
          <div class="grid grid-cols-2 gap-4">
            <BaseSelect
              v-model="form.stage"
              label="Stage"
              :options="stageOptions"
              required
              :error="errors.stage"
            />

            <BaseInput
              v-model.number="form.probability"
              label="Probability (%)"
              type="number"
              min="0"
              max="100"
              required
              placeholder="0-100"
              :error="errors.probability"
            />
          </div>
          
          <BaseInput
            v-model="form.expected_close_date"
            label="Expected Close Date"
            type="date"
            required
            :error="errors.expected_close_date"
          />
          
          <BaseInput
            v-model="form.customer_name"
            label="Customer/Company"
            placeholder="Customer name"
            :error="errors.customer_name"
          />
          
          <BaseTextarea
            v-model="form.description"
            label="Description"
            placeholder="Opportunity description..."
            :rows="4"
          />
        </div>

        <div class="mt-6 flex justify-end space-x-3">
          <BaseButton type="button" variant="secondary" @click="modal.close">
            Cancel
          </BaseButton>
          <BaseButton type="submit" variant="primary" :loading="saving">
            {{ editingId ? 'Update' : 'Create' }} Opportunity
          </BaseButton>
        </div>
      </form>
    </BaseModal>

    <!-- Stage Update Modal -->
    <BaseModal :show="stageModal.isOpen" title="Update Stage" size="md" @close="stageModal.close">
      <div v-if="selectedOpportunity" class="space-y-4">
        <div>
          <p class="text-sm text-gray-500">Opportunity</p>
          <p class="font-semibold">{{ selectedOpportunity.name }}</p>
        </div>
        
        <BaseSelect
          v-model="newStage"
          label="New Stage"
          :options="stageOptions"
          required
        />

        <div class="mt-6 flex justify-end space-x-3">
          <BaseButton type="button" variant="secondary" @click="stageModal.close">
            Cancel
          </BaseButton>
          <BaseButton variant="primary" :loading="saving" @click="saveStage">
            Update Stage
          </BaseButton>
        </div>
      </div>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useCrmStore } from '../stores/crmStore';
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

const crmStore = useCrmStore();
const modal = useModal();
const stageModal = useModal();
const { showSuccess, showError } = useNotifications();

// State
const loading = ref(false);
const saving = ref(false);
const search = ref('');
const stageFilter = ref('');
const probabilityFilter = ref('');
const editingId = ref(null);
const selectedOpportunity = ref(null);
const newStage = ref('');

// Form
const form = ref({
  name: '',
  value: 0,
  stage: 'prospecting',
  probability: 20,
  expected_close_date: '',
  customer_name: '',
  description: '',
});

const errors = ref({});

// Options
const stageOptions = [
  { label: 'Prospecting', value: 'prospecting' },
  { label: 'Qualification', value: 'qualification' },
  { label: 'Proposal', value: 'proposal' },
  { label: 'Negotiation', value: 'negotiation' },
  { label: 'Closed Won', value: 'closed_won' },
  { label: 'Closed Lost', value: 'closed_lost' },
];

const probabilityOptions = [
  { label: 'Low (0-33%)', value: 'low' },
  { label: 'Medium (34-66%)', value: 'medium' },
  { label: 'High (67-100%)', value: 'high' },
];

// Table configuration
const columns = [
  { key: 'name', label: 'Name', sortable: true },
  { key: 'customer_name', label: 'Customer', sortable: true },
  { key: 'value', label: 'Value', sortable: true },
  { key: 'stage', label: 'Stage', sortable: true },
  { key: 'probability', label: 'Probability', sortable: true },
  { key: 'expected_close_date', label: 'Close Date', sortable: true },
];

const tableActions = [
  { key: 'view', label: 'View', variant: 'secondary' },
  { key: 'edit', label: 'Edit', variant: 'primary' },
  { key: 'stage', label: 'Update Stage', variant: 'info' },
  { key: 'delete', label: 'Delete', variant: 'danger' },
];

// Filtering and sorting
const { sortedData, handleSort } = useTable(computed(() => crmStore.opportunities));

const filteredOpportunities = computed(() => {
  let data = sortedData.value;
  
  // Search filter
  if (search.value) {
    const searchLower = search.value.toLowerCase();
    data = data.filter(opp =>
      opp.name?.toLowerCase().includes(searchLower) ||
      opp.customer_name?.toLowerCase().includes(searchLower) ||
      opp.description?.toLowerCase().includes(searchLower)
    );
  }
  
  // Stage filter
  if (stageFilter.value) {
    data = data.filter(opp => opp.stage === stageFilter.value);
  }
  
  // Probability filter
  if (probabilityFilter.value) {
    data = data.filter(opp => {
      const prob = opp.probability;
      if (probabilityFilter.value === 'low') return prob <= 33;
      if (probabilityFilter.value === 'medium') return prob > 33 && prob <= 66;
      if (probabilityFilter.value === 'high') return prob > 66;
      return true;
    });
  }
  
  return data;
});

// Statistics
const totalValue = computed(() => {
  return filteredOpportunities.value.reduce((sum, opp) => sum + Number(opp.value || 0), 0);
});

const avgValue = computed(() => {
  const count = filteredOpportunities.value.length;
  return count > 0 ? Math.round(totalValue.value / count) : 0;
});

const winRate = computed(() => {
  const total = crmStore.opportunities.length;
  const won = crmStore.opportunities.filter(o => o.stage === 'closed_won').length;
  return total > 0 ? Math.round((won / total) * 100) : 0;
});

// Pagination
const pagination = usePagination(filteredOpportunities, 10);

const paginatedOpportunities = computed(() => {
  const start = (pagination.currentPage.value - 1) * pagination.perPage.value;
  const end = start + pagination.perPage.value;
  return filteredOpportunities.value.slice(start, end);
});

const handlePageChange = (page) => {
  pagination.currentPage.value = page;
};

// Modal
const modalTitle = computed(() => 
  editingId.value ? 'Edit Opportunity' : 'Add Opportunity'
);

const openCreateModal = () => {
  resetForm();
  editingId.value = null;
  modal.open();
};

const resetForm = () => {
  form.value = {
    name: '',
    value: 0,
    stage: 'prospecting',
    probability: 20,
    expected_close_date: '',
    customer_name: '',
    description: '',
  };
  errors.value = {};
};

// CRUD Operations
const fetchOpportunities = async () => {
  loading.value = true;
  try {
    await crmStore.fetchOpportunities();
  } catch (error) {
    showError('Failed to load opportunities');
  } finally {
    loading.value = false;
  }
};

const handleSubmit = async () => {
  errors.value = {};
  saving.value = true;

  try {
    if (editingId.value) {
      await crmStore.updateOpportunity(editingId.value, form.value);
      showSuccess('Opportunity updated successfully');
    } else {
      await crmStore.createOpportunity(form.value);
      showSuccess('Opportunity created successfully');
    }
    
    modal.close();
    resetForm();
    await fetchOpportunities();
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    }
    showError(error.response?.data?.message || 'Operation failed');
  } finally {
    saving.value = false;
  }
};

const viewOpportunity = (opportunity) => {
  console.log('View opportunity:', opportunity);
  showError('Opportunity detail view not yet implemented');
};

const editOpportunity = (opportunity) => {
  editingId.value = opportunity.id;
  form.value = { ...opportunity };
  modal.open();
};

const updateStage = (opportunity) => {
  selectedOpportunity.value = opportunity;
  newStage.value = opportunity.stage;
  stageModal.open();
};

const saveStage = async () => {
  if (!selectedOpportunity.value || !newStage.value) return;
  
  saving.value = true;
  try {
    await crmStore.updateOpportunityStage(selectedOpportunity.value.id, newStage.value);
    showSuccess('Stage updated successfully');
    stageModal.close();
    await fetchOpportunities();
  } catch (error) {
    showError('Failed to update stage');
  } finally {
    saving.value = false;
  }
};

const deleteOpportunity = async (opportunity) => {
  if (!confirm(`Are you sure you want to delete ${opportunity.name}?`)) {
    return;
  }

  try {
    await crmStore.deleteOpportunity(opportunity.id);
    showSuccess('Opportunity deleted successfully');
    await fetchOpportunities();
  } catch (error) {
    showError('Failed to delete opportunity');
  }
};

// Utilities
const getStageVariant = (stage) => {
  const variants = {
    prospecting: 'info',
    qualification: 'secondary',
    proposal: 'warning',
    negotiation: 'primary',
    closed_won: 'success',
    closed_lost: 'danger',
  };
  return variants[stage] || 'secondary';
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString();
};

// Lifecycle
onMounted(() => {
  fetchOpportunities();
});
</script>
