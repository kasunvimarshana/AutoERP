<template>
  <div>
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Leads</h1>
        <p class="mt-1 text-sm text-gray-500">Manage sales leads and conversions</p>
      </div>
      <BaseButton variant="primary" @click="openCreateModal">
        Add Lead
      </BaseButton>
    </div>

    <!-- Filters -->
    <BaseCard class="mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <BaseInput
          v-model="search"
          placeholder="Search leads..."
          type="search"
        />
        <BaseSelect
          v-model="statusFilter"
          :options="statusOptions"
          placeholder="Filter by status"
        />
        <BaseSelect
          v-model="sourceFilter"
          :options="sourceOptions"
          placeholder="Filter by source"
        />
      </div>
    </BaseCard>

    <!-- Leads Table -->
    <BaseCard>
      <BaseTable
        :columns="columns"
        :data="filteredLeads"
        :loading="loading"
        :actions="tableActions"
        @sort="handleSort"
        @action:view="viewLead"
        @action:edit="editLead"
        @action:convert="convertLead"
        @action:delete="deleteLead"
      >
        <template #cell-status="{ value }">
          <BaseBadge :variant="getStatusVariant(value)">
            {{ value }}
          </BaseBadge>
        </template>
        
        <template #cell-source="{ value }">
          <BaseBadge variant="secondary">
            {{ value }}
          </BaseBadge>
        </template>

        <template #cell-email="{ value }">
          <a :href="`mailto:${value}`" class="text-indigo-600 hover:text-indigo-900">
            {{ value }}
          </a>
        </template>

        <template #cell-score="{ value }">
          <div class="flex items-center">
            <div class="w-full bg-gray-200 rounded-full h-2 mr-2">
              <div class="bg-indigo-600 h-2 rounded-full" :style="{ width: `${value}%` }"></div>
            </div>
            <span class="text-sm text-gray-600">{{ value }}</span>
          </div>
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
            label="Lead Name"
            required
            placeholder="Enter lead name"
            :error="errors.name"
          />
          
          <BaseInput
            v-model="form.company"
            label="Company"
            placeholder="Company name"
            :error="errors.company"
          />
          
          <BaseInput
            v-model="form.email"
            label="Email"
            type="email"
            required
            placeholder="lead@example.com"
            :error="errors.email"
          />
          
          <BaseInput
            v-model="form.phone"
            label="Phone"
            placeholder="+1 (555) 123-4567"
            :error="errors.phone"
          />
          
          <div class="grid grid-cols-2 gap-4">
            <BaseSelect
              v-model="form.status"
              label="Status"
              :options="statusOptions"
              required
              :error="errors.status"
            />

            <BaseSelect
              v-model="form.source"
              label="Source"
              :options="sourceOptions"
              required
              :error="errors.source"
            />
          </div>
          
          <BaseInput
            v-model.number="form.score"
            label="Lead Score"
            type="number"
            min="0"
            max="100"
            placeholder="0-100"
            :error="errors.score"
          />
          
          <BaseTextarea
            v-model="form.notes"
            label="Notes"
            placeholder="Additional notes..."
            :rows="3"
          />
        </div>

        <div class="mt-6 flex justify-end space-x-3">
          <BaseButton type="button" variant="secondary" @click="modal.close">
            Cancel
          </BaseButton>
          <BaseButton type="submit" variant="primary" :loading="saving">
            {{ editingId ? 'Update' : 'Create' }} Lead
          </BaseButton>
        </div>
      </form>
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
const { showSuccess, showError } = useNotifications();

// State
const loading = ref(false);
const saving = ref(false);
const search = ref('');
const statusFilter = ref('');
const sourceFilter = ref('');
const editingId = ref(null);

// Form
const form = ref({
  name: '',
  company: '',
  email: '',
  phone: '',
  status: 'new',
  source: 'website',
  score: 50,
  notes: '',
});

const errors = ref({});

// Options
const statusOptions = [
  { label: 'New', value: 'new' },
  { label: 'Contacted', value: 'contacted' },
  { label: 'Qualified', value: 'qualified' },
  { label: 'Unqualified', value: 'unqualified' },
  { label: 'Converted', value: 'converted' },
];

const sourceOptions = [
  { label: 'Website', value: 'website' },
  { label: 'Referral', value: 'referral' },
  { label: 'Social Media', value: 'social' },
  { label: 'Email Campaign', value: 'email' },
  { label: 'Phone', value: 'phone' },
  { label: 'Trade Show', value: 'tradeshow' },
  { label: 'Other', value: 'other' },
];

// Table configuration
const columns = [
  { key: 'name', label: 'Name', sortable: true },
  { key: 'company', label: 'Company', sortable: true },
  { key: 'email', label: 'Email', sortable: true },
  { key: 'phone', label: 'Phone' },
  { key: 'source', label: 'Source', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'score', label: 'Score', sortable: true },
];

const tableActions = [
  { key: 'view', label: 'View', variant: 'secondary' },
  { key: 'edit', label: 'Edit', variant: 'primary' },
  { key: 'convert', label: 'Convert', variant: 'success' },
  { key: 'delete', label: 'Delete', variant: 'danger' },
];

// Filtering and sorting
const { sortedData, handleSort } = useTable(computed(() => crmStore.leads));

const filteredLeads = computed(() => {
  let data = sortedData.value;
  
  // Search filter
  if (search.value) {
    const searchLower = search.value.toLowerCase();
    data = data.filter(lead =>
      lead.name?.toLowerCase().includes(searchLower) ||
      lead.company?.toLowerCase().includes(searchLower) ||
      lead.email?.toLowerCase().includes(searchLower) ||
      lead.phone?.toLowerCase().includes(searchLower)
    );
  }
  
  // Status filter
  if (statusFilter.value) {
    data = data.filter(lead => lead.status === statusFilter.value);
  }
  
  // Source filter
  if (sourceFilter.value) {
    data = data.filter(lead => lead.source === sourceFilter.value);
  }
  
  return data;
});

// Pagination
const pagination = usePagination(filteredLeads, 10);

const handlePageChange = (page) => {
  pagination.currentPage.value = page;
};

// Modal
const modalTitle = computed(() => 
  editingId.value ? 'Edit Lead' : 'Add Lead'
);

const openCreateModal = () => {
  resetForm();
  editingId.value = null;
  modal.open();
};

const resetForm = () => {
  form.value = {
    name: '',
    company: '',
    email: '',
    phone: '',
    status: 'new',
    source: 'website',
    score: 50,
    notes: '',
  };
  errors.value = {};
};

// CRUD Operations
const fetchLeads = async () => {
  loading.value = true;
  try {
    await crmStore.fetchLeads();
  } catch (error) {
    showError('Failed to load leads');
  } finally {
    loading.value = false;
  }
};

const handleSubmit = async () => {
  errors.value = {};
  saving.value = true;

  try {
    if (editingId.value) {
      await crmStore.updateLead(editingId.value, form.value);
      showSuccess('Lead updated successfully');
    } else {
      await crmStore.createLead(form.value);
      showSuccess('Lead created successfully');
    }
    
    modal.close();
    resetForm();
    await fetchLeads();
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    }
    showError(error.response?.data?.message || 'Operation failed');
  } finally {
    saving.value = false;
  }
};

const viewLead = (lead) => {
  console.log('View lead:', lead);
  showError('Lead detail view not yet implemented');
};

const editLead = (lead) => {
  editingId.value = lead.id;
  form.value = { ...lead };
  modal.open();
};

const convertLead = async (lead) => {
  if (!confirm(`Convert ${lead.name} to a customer?`)) {
    return;
  }

  try {
    await crmStore.convertLeadToCustomer(lead.id);
    showSuccess('Lead converted to customer successfully');
    await fetchLeads();
  } catch (error) {
    showError('Failed to convert lead');
  }
};

const deleteLead = async (lead) => {
  if (!confirm(`Are you sure you want to delete ${lead.name}?`)) {
    return;
  }

  try {
    await crmStore.deleteLead(lead.id);
    showSuccess('Lead deleted successfully');
    await fetchLeads();
  } catch (error) {
    showError('Failed to delete lead');
  }
};

// Utilities
const getStatusVariant = (status) => {
  const variants = {
    new: 'info',
    contacted: 'secondary',
    qualified: 'success',
    unqualified: 'warning',
    converted: 'success',
  };
  return variants[status] || 'secondary';
};

// Lifecycle
onMounted(() => {
  fetchLeads();
});
</script>
