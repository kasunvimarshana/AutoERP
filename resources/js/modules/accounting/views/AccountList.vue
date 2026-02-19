<template>
  <div>
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Chart of Accounts</h1>
        <p class="mt-1 text-sm text-gray-500">Manage account structure and balances</p>
      </div>
      <BaseButton variant="primary" @click="openCreateModal">
        Add Account
      </BaseButton>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-6">
      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-blue-100 rounded-lg">
              <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Total Accounts</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-blue-100 rounded-lg">
              <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Assets</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.assets }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-red-100 rounded-lg">
              <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Liabilities</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.liabilities }}</p>
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
            <p class="text-sm font-medium text-gray-500">Equity</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.equity }}</p>
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
            <p class="text-sm font-medium text-gray-500">Active</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.active }}</p>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- Filters -->
    <BaseCard class="mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <BaseInput
          v-model="search"
          placeholder="Search accounts..."
          type="search"
        />
        <BaseSelect
          v-model="typeFilter"
          :options="typeOptions"
          placeholder="Filter by type"
        />
        <BaseSelect
          v-model="statusFilter"
          :options="statusOptions"
          placeholder="Filter by status"
        />
      </div>
    </BaseCard>

    <!-- Accounts Table -->
    <BaseCard>
      <BaseTable
        :columns="columns"
        :data="filteredAccounts"
        :loading="loading"
        :actions="tableActions"
        @sort="handleSort"
        @action:view="viewAccount"
        @action:edit="editAccount"
        @action:activate="activateAccount"
        @action:deactivate="deactivateAccount"
        @action:delete="deleteAccount"
      >
        <template #cell-account_code="{ value }">
          <span class="font-medium text-gray-900">{{ value }}</span>
        </template>

        <template #cell-type="{ value }">
          <BaseBadge :variant="getTypeVariant(value)">
            {{ formatType(value) }}
          </BaseBadge>
        </template>

        <template #cell-parent_account="{ row }">
          <span class="text-sm text-gray-600">
            {{ row.parent_account?.name || '-' }}
          </span>
        </template>

        <template #cell-balance="{ value }">
          <span class="font-medium" :class="getBalanceColor(value)">
            {{ formatCurrency(value) }}
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
              v-model="form.account_code"
              label="Account Code"
              required
              placeholder="1000"
              :error="errors.account_code"
            />

            <BaseSelect
              v-model="form.type"
              label="Account Type"
              :options="typeOptions"
              required
              :error="errors.type"
            />
          </div>

          <BaseInput
            v-model="form.name"
            label="Account Name"
            required
            placeholder="Cash on Hand"
            :error="errors.name"
          />

          <BaseSelect
            v-model="form.parent_account_id"
            label="Parent Account"
            :options="parentAccountOptions"
            placeholder="None (Top-level account)"
            :error="errors.parent_account_id"
          />

          <BaseTextarea
            v-model="form.description"
            label="Description"
            placeholder="Account description..."
            :rows="3"
          />

          <BaseSelect
            v-model="form.status"
            label="Status"
            :options="statusOptions"
            required
            :error="errors.status"
          />
        </div>

        <div class="mt-6 flex justify-end space-x-3">
          <BaseButton type="button" variant="secondary" @click="modal.close">
            Cancel
          </BaseButton>
          <BaseButton type="submit" variant="primary" :loading="saving">
            {{ editingId ? 'Update' : 'Create' }} Account
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
const typeFilter = ref('');
const statusFilter = ref('');
const editingId = ref(null);

const form = ref({
  account_code: '',
  name: '',
  type: 'asset',
  parent_account_id: '',
  description: '',
  status: 'active',
});

const errors = ref({});

const stats = computed(() => {
  const accounts = accountingStore.accounts || [];
  return {
    total: accounts.length,
    assets: accounts.filter(a => a.type === 'asset').length,
    liabilities: accounts.filter(a => a.type === 'liability').length,
    equity: accounts.filter(a => a.type === 'equity').length,
    active: accounts.filter(a => a.status === 'active').length,
  };
});

const typeOptions = [
  { label: 'All Types', value: '' },
  { label: 'Asset', value: 'asset' },
  { label: 'Liability', value: 'liability' },
  { label: 'Equity', value: 'equity' },
  { label: 'Revenue', value: 'revenue' },
  { label: 'Expense', value: 'expense' },
];

const statusOptions = [
  { label: 'All Statuses', value: '' },
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
];

const parentAccountOptions = computed(() => [
  { label: 'None (Top-level)', value: '' },
  ...(accountingStore.accounts || [])
    .filter(a => a.id !== editingId.value)
    .map(a => ({ 
      label: `${a.account_code} - ${a.name}`, 
      value: a.id 
    }))
]);

const columns = [
  { key: 'account_code', label: 'Code', sortable: true },
  { key: 'name', label: 'Account Name', sortable: true },
  { key: 'type', label: 'Type', sortable: true },
  { key: 'parent_account', label: 'Parent Account', sortable: false },
  { key: 'balance', label: 'Balance', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
];

const tableActions = computed(() => {
  return (row) => [
    { key: 'view', label: 'View', icon: 'eye' },
    { key: 'edit', label: 'Edit', icon: 'pencil' },
    { 
      key: row.status === 'active' ? 'deactivate' : 'activate', 
      label: row.status === 'active' ? 'Deactivate' : 'Activate', 
      icon: 'power'
    },
    { key: 'delete', label: 'Delete', icon: 'trash', variant: 'danger' },
  ];
});

const { sortedData, handleSort } = useTable(computed(() => accountingStore.accounts || []));

const filteredAccounts = computed(() => {
  let data = sortedData.value;

  if (search.value) {
    const searchLower = search.value.toLowerCase();
    data = data.filter(account =>
      account.account_code?.toLowerCase().includes(searchLower) ||
      account.name?.toLowerCase().includes(searchLower) ||
      account.description?.toLowerCase().includes(searchLower)
    );
  }

  if (typeFilter.value) {
    data = data.filter(account => account.type === typeFilter.value);
  }

  if (statusFilter.value) {
    data = data.filter(account => account.status === statusFilter.value);
  }

  return data;
});

const pagination = usePagination(filteredAccounts, 10);

const handlePageChange = (page) => {
  pagination.currentPage.value = page;
};

const modalTitle = computed(() => 
  editingId.value ? 'Edit Account' : 'Add Account'
);

const openCreateModal = () => {
  resetForm();
  editingId.value = null;
  modal.open();
};

const resetForm = () => {
  form.value = {
    account_code: '',
    name: '',
    type: 'asset',
    parent_account_id: '',
    description: '',
    status: 'active',
  };
  errors.value = {};
};

const fetchAccounts = async () => {
  loading.value = true;
  try {
    await accountingStore.fetchAccounts();
  } catch (error) {
    showError('Failed to load accounts');
  } finally {
    loading.value = false;
  }
};

const handleSubmit = async () => {
  errors.value = {};
  saving.value = true;

  try {
    if (editingId.value) {
      await accountingStore.updateAccount(editingId.value, form.value);
      showSuccess('Account updated successfully');
    } else {
      await accountingStore.createAccount(form.value);
      showSuccess('Account created successfully');
    }
    
    modal.close();
    resetForm();
    await fetchAccounts();
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    }
    showError(error.response?.data?.message || 'Operation failed');
  } finally {
    saving.value = false;
  }
};

const viewAccount = (account) => {
  showError('Account detail view not yet implemented');
};

const editAccount = (account) => {
  editingId.value = account.id;
  form.value = {
    account_code: account.account_code,
    name: account.name,
    type: account.type,
    parent_account_id: account.parent_account_id || '',
    description: account.description || '',
    status: account.status,
  };
  modal.open();
};

const activateAccount = async (account) => {
  if (!confirm(`Activate account ${account.name}?`)) return;

  try {
    await accountingStore.activateAccount(account.id);
    showSuccess('Account activated successfully');
    await fetchAccounts();
  } catch (error) {
    showError('Failed to activate account');
  }
};

const deactivateAccount = async (account) => {
  if (!confirm(`Deactivate account ${account.name}?`)) return;

  try {
    await accountingStore.deactivateAccount(account.id);
    showSuccess('Account deactivated successfully');
    await fetchAccounts();
  } catch (error) {
    showError('Failed to deactivate account');
  }
};

const deleteAccount = async (account) => {
  if (!confirm(`Are you sure you want to delete ${account.name}?`)) return;

  try {
    await accountingStore.deleteAccount(account.id);
    showSuccess('Account deleted successfully');
    await fetchAccounts();
  } catch (error) {
    showError('Failed to delete account');
  }
};

const getTypeVariant = (type) => {
  const variants = {
    asset: 'primary',
    liability: 'danger',
    equity: 'success',
    revenue: 'info',
    expense: 'warning',
  };
  return variants[type] || 'secondary';
};

const formatType = (type) => {
  const labels = {
    asset: 'Asset',
    liability: 'Liability',
    equity: 'Equity',
    revenue: 'Revenue',
    expense: 'Expense',
  };
  return labels[type] || type;
};

const getBalanceColor = (balance) => {
  const val = parseFloat(balance || 0);
  if (val > 0) return 'text-green-600';
  if (val < 0) return 'text-red-600';
  return 'text-gray-900';
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount || 0);
};

onMounted(() => {
  fetchAccounts();
});
</script>
