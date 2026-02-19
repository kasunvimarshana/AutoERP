<template>
  <div>
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Journal Entries</h1>
        <p class="mt-1 text-sm text-gray-500">Manage journal entries and transactions</p>
      </div>
      <BaseButton variant="primary" @click="openCreateModal">
        Create Entry
      </BaseButton>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-6">
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
            <p class="text-sm font-medium text-gray-500">Total Entries</p>
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
            <p class="text-sm font-medium text-gray-500">Posted</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.posted }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-yellow-100 rounded-lg">
              <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Draft</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.draft }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-purple-100 rounded-lg">
              <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Total Debit</p>
            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(stats.totalDebit) }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-indigo-100 rounded-lg">
              <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Total Credit</p>
            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(stats.totalCredit) }}</p>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- Filters -->
    <BaseCard class="mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <BaseInput
          v-model="search"
          placeholder="Search entries..."
          type="search"
        />
        <BaseSelect
          v-model="statusFilter"
          :options="statusOptions"
          placeholder="Filter by status"
        />
        <BaseInput
          v-model="fromDate"
          type="date"
          placeholder="From date"
          label="From Date"
        />
        <BaseInput
          v-model="toDate"
          type="date"
          placeholder="To date"
          label="To Date"
        />
      </div>
    </BaseCard>

    <!-- Journal Entries Table -->
    <BaseCard>
      <BaseTable
        :columns="columns"
        :data="filteredEntries"
        :loading="loading"
        :actions="tableActions"
        @sort="handleSort"
        @action:view="viewEntry"
        @action:edit="editEntry"
        @action:post="postEntry"
        @action:reverse="reverseEntry"
        @action:delete="deleteEntry"
      >
        <template #cell-entry_number="{ value }">
          <span class="font-medium text-gray-900">{{ value }}</span>
        </template>

        <template #cell-entry_date="{ value }">
          {{ formatDate(value) }}
        </template>

        <template #cell-total_debit="{ value }">
          <span class="font-medium text-purple-600">{{ formatCurrency(value) }}</span>
        </template>

        <template #cell-total_credit="{ value }">
          <span class="font-medium text-indigo-600">{{ formatCurrency(value) }}</span>
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
    <BaseModal :show="modal.isOpen" :title="modalTitle" size="2xl" @close="modal.close">
      <form @submit.prevent="handleSubmit">
        <div class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <BaseInput
              v-model="form.entry_number"
              label="Entry Number"
              required
              placeholder="JE-001"
              :error="errors.entry_number"
            />
            
            <BaseInput
              v-model="form.entry_date"
              label="Entry Date"
              type="date"
              required
              :error="errors.entry_date"
            />
          </div>

          <BaseInput
            v-model="form.reference"
            label="Reference"
            placeholder="Reference number/document"
            :error="errors.reference"
          />

          <BaseTextarea
            v-model="form.description"
            label="Description"
            placeholder="Entry description..."
            rows="2"
            required
            :error="errors.description"
          />

          <!-- Journal Lines -->
          <div class="border-t pt-4">
            <div class="flex items-center justify-between mb-3">
              <label class="block text-sm font-medium text-gray-700">Journal Lines</label>
              <BaseButton type="button" variant="secondary" size="sm" @click="addLine">
                Add Line
              </BaseButton>
            </div>

            <div v-if="form.lines.length === 0" class="text-center py-4 text-gray-500 border rounded-lg">
              No lines added yet. Click "Add Line" to start.
            </div>

            <div v-for="(line, index) in form.lines" :key="index" class="mb-3 p-3 border rounded-lg bg-gray-50">
              <div class="grid grid-cols-12 gap-2">
                <div class="col-span-5">
                  <BaseSelect
                    v-model="line.account_id"
                    :options="accountOptions"
                    placeholder="Select account"
                    size="sm"
                  />
                </div>
                <div class="col-span-2">
                  <BaseInput
                    v-model.number="line.debit_amount"
                    type="number"
                    placeholder="Debit"
                    size="sm"
                    min="0"
                    step="0.01"
                    @input="clearCredit(index)"
                  />
                </div>
                <div class="col-span-2">
                  <BaseInput
                    v-model.number="line.credit_amount"
                    type="number"
                    placeholder="Credit"
                    size="sm"
                    min="0"
                    step="0.01"
                    @input="clearDebit(index)"
                  />
                </div>
                <div class="col-span-2">
                  <BaseInput
                    v-model="line.description"
                    placeholder="Line description"
                    size="sm"
                  />
                </div>
                <div class="col-span-1 flex items-center">
                  <button
                    type="button"
                    @click="removeLine(index)"
                    class="text-red-600 hover:text-red-800"
                  >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Balance Check -->
          <div class="border-t pt-4 space-y-2">
            <div class="flex justify-between">
              <span class="text-sm font-medium text-gray-700">Total Debit:</span>
              <span class="font-bold text-purple-600">{{ formatCurrency(calculateTotalDebit()) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-sm font-medium text-gray-700">Total Credit:</span>
              <span class="font-bold text-indigo-600">{{ formatCurrency(calculateTotalCredit()) }}</span>
            </div>
            <div class="flex justify-between border-t pt-2">
              <span class="text-sm font-bold text-gray-900">Difference:</span>
              <span 
                class="font-bold" 
                :class="isBalanced() ? 'text-green-600' : 'text-red-600'"
              >
                {{ formatCurrency(Math.abs(calculateTotalDebit() - calculateTotalCredit())) }}
              </span>
            </div>
            <div v-if="!isBalanced()" class="text-sm text-red-600 font-medium">
              ⚠️ Entry must be balanced (Debit = Credit)
            </div>
          </div>

          <BaseTextarea
            v-model="form.notes"
            label="Notes"
            placeholder="Additional notes..."
            rows="2"
          />
        </div>

        <div class="mt-6 flex justify-end space-x-3">
          <BaseButton type="button" variant="secondary" @click="modal.close">
            Cancel
          </BaseButton>
          <BaseButton type="submit" variant="primary" :loading="saving" :disabled="!isBalanced() || form.lines.length === 0">
            {{ editingId ? 'Update' : 'Create' }} Entry
          </BaseButton>
        </div>
      </form>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAccountingStore } from '../stores/accountingStore';
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

const accountingStore = useAccountingStore();
const modal = useModal();
const { showSuccess, showError } = useNotifications();

const loading = ref(false);
const saving = ref(false);
const search = ref('');
const statusFilter = ref('');
const fromDate = ref('');
const toDate = ref('');
const editingId = ref(null);

const form = ref({
  entry_number: '',
  entry_date: new Date().toISOString().split('T')[0],
  reference: '',
  description: '',
  notes: '',
  lines: []
});

const errors = ref({});

const stats = computed(() => {
  const entries = accountingStore.journalEntries || [];
  return {
    total: entries.length,
    posted: entries.filter(e => e.status === 'posted').length,
    draft: entries.filter(e => e.status === 'draft').length,
    totalDebit: entries.reduce((sum, e) => sum + parseFloat(e.total_debit || 0), 0),
    totalCredit: entries.reduce((sum, e) => sum + parseFloat(e.total_credit || 0), 0),
  };
});

const statusOptions = [
  { label: 'All Statuses', value: '' },
  { label: 'Draft', value: 'draft' },
  { label: 'Posted', value: 'posted' },
  { label: 'Reversed', value: 'reversed' },
];

const accountOptions = computed(() => [
  { label: 'Select account', value: '' },
  ...(accountingStore.accounts || [])
    .filter(a => a.status === 'active')
    .map(a => ({ 
      label: `${a.account_code} - ${a.name}`, 
      value: a.id 
    }))
]);

const columns = [
  { key: 'entry_number', label: 'Entry #', sortable: true },
  { key: 'entry_date', label: 'Date', sortable: true },
  { key: 'reference', label: 'Reference', sortable: true },
  { key: 'description', label: 'Description', sortable: false },
  { key: 'total_debit', label: 'Total Debit', sortable: true },
  { key: 'total_credit', label: 'Total Credit', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
];

const tableActions = computed(() => {
  return (row) => {
    const actions = [
      { key: 'view', label: 'View', icon: 'eye' },
      { key: 'edit', label: 'Edit', icon: 'pencil', show: row.status === 'draft' }
    ];
    
    if (row.status === 'draft') {
      actions.push({ key: 'post', label: 'Post Entry', icon: 'check' });
    }
    
    if (row.status === 'posted') {
      actions.push({ key: 'reverse', label: 'Reverse Entry', icon: 'refresh', variant: 'warning' });
    }
    
    if (row.status === 'draft') {
      actions.push({ key: 'delete', label: 'Delete', icon: 'trash', variant: 'danger' });
    }
    
    return actions.filter(a => a.show !== false);
  };
});

const { sortedData, handleSort } = useTable(computed(() => accountingStore.journalEntries || []));

const filteredEntries = computed(() => {
  let data = sortedData.value;

  if (search.value) {
    const searchLower = search.value.toLowerCase();
    data = data.filter(entry =>
      entry.entry_number?.toLowerCase().includes(searchLower) ||
      entry.reference?.toLowerCase().includes(searchLower) ||
      entry.description?.toLowerCase().includes(searchLower)
    );
  }

  if (statusFilter.value) {
    data = data.filter(entry => entry.status === statusFilter.value);
  }

  if (fromDate.value) {
    data = data.filter(entry => new Date(entry.entry_date) >= new Date(fromDate.value));
  }

  if (toDate.value) {
    data = data.filter(entry => new Date(entry.entry_date) <= new Date(toDate.value));
  }

  return data;
});

const pagination = usePagination(filteredEntries, 10);

const handlePageChange = (page) => {
  pagination.currentPage.value = page;
};

const modalTitle = computed(() => 
  editingId.value ? 'Edit Journal Entry' : 'Create Journal Entry'
);

const openCreateModal = () => {
  resetForm();
  editingId.value = null;
  modal.open();
};

const resetForm = () => {
  form.value = {
    entry_number: '',
    entry_date: new Date().toISOString().split('T')[0],
    reference: '',
    description: '',
    notes: '',
    lines: []
  };
  errors.value = {};
};

const addLine = () => {
  form.value.lines.push({
    account_id: '',
    debit_amount: 0,
    credit_amount: 0,
    description: ''
  });
};

const removeLine = (index) => {
  form.value.lines.splice(index, 1);
};

const clearDebit = (index) => {
  if (form.value.lines[index].credit_amount > 0) {
    form.value.lines[index].debit_amount = 0;
  }
};

const clearCredit = (index) => {
  if (form.value.lines[index].debit_amount > 0) {
    form.value.lines[index].credit_amount = 0;
  }
};

const calculateTotalDebit = () => {
  return form.value.lines.reduce((sum, line) => sum + parseFloat(line.debit_amount || 0), 0);
};

const calculateTotalCredit = () => {
  return form.value.lines.reduce((sum, line) => sum + parseFloat(line.credit_amount || 0), 0);
};

const isBalanced = () => {
  const debit = calculateTotalDebit();
  const credit = calculateTotalCredit();
  return Math.abs(debit - credit) < 0.01 && debit > 0;
};

const fetchData = async () => {
  loading.value = true;
  try {
    await Promise.all([
      accountingStore.fetchJournalEntries(),
      accountingStore.fetchAccounts()
    ]);
  } catch (error) {
    showError('Failed to load data');
  } finally {
    loading.value = false;
  }
};

const handleSubmit = async () => {
  errors.value = {};
  
  if (!isBalanced()) {
    showError('Entry must be balanced (Total Debit = Total Credit)');
    return;
  }
  
  if (form.value.lines.length === 0) {
    showError('Please add at least one journal line');
    return;
  }

  saving.value = true;

  try {
    const data = {
      ...form.value,
      total_debit: calculateTotalDebit(),
      total_credit: calculateTotalCredit()
    };

    if (editingId.value) {
      await accountingStore.updateJournalEntry(editingId.value, data);
      showSuccess('Journal entry updated successfully');
    } else {
      await accountingStore.createJournalEntry(data);
      showSuccess('Journal entry created successfully');
    }
    
    modal.close();
    resetForm();
    await fetchData();
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    }
    showError(error.response?.data?.message || 'Operation failed');
  } finally {
    saving.value = false;
  }
};

const viewEntry = (entry) => {
  showError('Entry detail view not yet implemented');
};

const editEntry = (entry) => {
  editingId.value = entry.id;
  form.value = {
    entry_number: entry.entry_number,
    entry_date: entry.entry_date,
    reference: entry.reference || '',
    description: entry.description,
    notes: entry.notes || '',
    lines: entry.lines?.map(line => ({
      account_id: line.account_id,
      debit_amount: parseFloat(line.debit_amount || 0),
      credit_amount: parseFloat(line.credit_amount || 0),
      description: line.description || ''
    })) || []
  };
  modal.open();
};

const postEntry = async (entry) => {
  if (!confirm(`Post journal entry ${entry.entry_number}? This action cannot be undone.`)) return;

  try {
    await accountingStore.postJournalEntry(entry.id);
    showSuccess('Journal entry posted successfully');
    await fetchData();
  } catch (error) {
    showError('Failed to post journal entry');
  }
};

const reverseEntry = async (entry) => {
  if (!confirm(`Reverse journal entry ${entry.entry_number}? This will create a reversing entry.`)) return;

  try {
    await accountingStore.reverseJournalEntry(entry.id);
    showSuccess('Journal entry reversed successfully');
    await fetchData();
  } catch (error) {
    showError('Failed to reverse journal entry');
  }
};

const deleteEntry = async (entry) => {
  if (!confirm(`Are you sure you want to delete entry ${entry.entry_number}?`)) return;

  try {
    await accountingStore.deleteJournalEntry(entry.id);
    showSuccess('Journal entry deleted successfully');
    await fetchData();
  } catch (error) {
    showError('Failed to delete journal entry');
  }
};

const getStatusVariant = (status) => {
  const variants = {
    draft: 'warning',
    posted: 'success',
    reversed: 'secondary',
  };
  return variants[status] || 'secondary';
};

const formatStatus = (status) => {
  const labels = {
    draft: 'Draft',
    posted: 'Posted',
    reversed: 'Reversed',
  };
  return labels[status] || status;
};

const formatDate = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString();
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount || 0);
};

onMounted(() => {
  fetchData();
});
</script>
