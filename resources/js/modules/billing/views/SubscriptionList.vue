<template>
  <div>
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Customer Subscriptions</h1>
        <p class="mt-1 text-sm text-gray-500">Manage customer subscriptions and billing</p>
      </div>
      <BaseButton variant="primary" @click="openCreateModal">
        Create Subscription
      </BaseButton>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-6">
      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-blue-100 rounded-lg">
              <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Total Subscriptions</p>
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Trial</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.trial }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-yellow-100 rounded-lg">
              <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Total MRR</p>
            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(stats.mrr) }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-red-100 rounded-lg">
              <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Churned</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.churned }}</p>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- Filters -->
    <BaseCard class="mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <BaseInput
          v-model="search"
          placeholder="Search subscriptions..."
          type="search"
        />
        <BaseSelect
          v-model="statusFilter"
          :options="statusOptions"
          placeholder="Filter by status"
        />
        <BaseSelect
          v-model="planFilter"
          :options="planOptions"
          placeholder="Filter by plan"
        />
      </div>
    </BaseCard>

    <!-- Subscriptions Table -->
    <BaseCard>
      <BaseTable
        :columns="columns"
        :data="filteredSubscriptions"
        :loading="loading"
        :actions="tableActions"
        @sort="handleSort"
        @action:view="viewSubscription"
        @action:suspend="suspendSubscription"
        @action:resume="resumeSubscription"
        @action:cancel="cancelSubscription"
        @action:renew="renewSubscription"
      >
        <template #cell-subscription_code="{ value }">
          <span class="font-medium text-gray-900">{{ value }}</span>
        </template>

        <template #cell-customer="{ row }">
          <div>
            <div class="font-medium text-gray-900">{{ row.customer?.name || 'N/A' }}</div>
            <div class="text-sm text-gray-500">{{ row.customer?.email || '' }}</div>
          </div>
        </template>

        <template #cell-plan="{ row }">
          <div>
            <div class="font-medium text-gray-900">{{ row.plan?.name || 'N/A' }}</div>
            <div class="text-sm text-gray-500">{{ formatInterval(row.plan?.billing_interval) }}</div>
          </div>
        </template>

        <template #cell-status="{ value }">
          <BaseBadge :variant="getStatusVariant(value)">
            {{ formatStatus(value) }}
          </BaseBadge>
        </template>

        <template #cell-start_date="{ value }">
          {{ formatDate(value) }}
        </template>

        <template #cell-next_billing_date="{ value }">
          {{ formatDate(value) }}
        </template>

        <template #cell-current_period_amount="{ value }">
          <span class="font-medium text-gray-900">{{ formatCurrency(value) }}</span>
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
          <BaseSelect
            v-model="form.customer_id"
            label="Customer"
            :options="customerOptions"
            required
            placeholder="Select customer"
            :error="errors.customer_id"
          />

          <BaseSelect
            v-model="form.plan_id"
            label="Plan"
            :options="activePlanOptions"
            required
            placeholder="Select plan"
            :error="errors.plan_id"
          />

          <BaseInput
            v-model="form.subscription_code"
            label="Subscription Code"
            required
            placeholder="SUB-001"
            :error="errors.subscription_code"
          />

          <div class="grid grid-cols-2 gap-4">
            <BaseInput
              v-model="form.start_date"
              label="Start Date"
              type="date"
              required
              :error="errors.start_date"
            />
            
            <BaseInput
              v-model="form.end_date"
              label="End Date"
              type="date"
              :error="errors.end_date"
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <BaseInput
              v-model="form.current_period_start"
              label="Current Period Start"
              type="date"
              required
              :error="errors.current_period_start"
            />
            
            <BaseInput
              v-model="form.current_period_end"
              label="Current Period End"
              type="date"
              required
              :error="errors.current_period_end"
            />
          </div>

          <BaseInput
            v-model="form.next_billing_date"
            label="Next Billing Date"
            type="date"
            required
            :error="errors.next_billing_date"
          />

          <BaseInput
            v-model.number="form.current_period_amount"
            label="Current Period Amount"
            type="number"
            min="0"
            step="0.01"
            required
            :error="errors.current_period_amount"
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
            {{ editingId ? 'Update' : 'Create' }} Subscription
          </BaseButton>
        </div>
      </form>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useBillingStore } from '../stores/billingStore';
import { useCrmStore } from '../../crm/stores/crmStore';
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

const billingStore = useBillingStore();
const crmStore = useCrmStore();
const modal = useModal();
const { showSuccess, showError } = useNotifications();

const loading = ref(false);
const saving = ref(false);
const search = ref('');
const statusFilter = ref('');
const planFilter = ref('');
const editingId = ref(null);

const form = ref({
  customer_id: '',
  plan_id: '',
  subscription_code: '',
  start_date: new Date().toISOString().split('T')[0],
  end_date: '',
  next_billing_date: '',
  current_period_start: new Date().toISOString().split('T')[0],
  current_period_end: '',
  current_period_amount: 0,
  status: 'active',
});

const errors = ref({});

const stats = computed(() => {
  const subscriptions = billingStore.subscriptions || [];
  const activeSubscriptions = subscriptions.filter(s => s.status === 'active');
  
  // Calculate MRR (Monthly Recurring Revenue)
  const mrr = activeSubscriptions.reduce((sum, sub) => {
    const amount = parseFloat(sub.current_period_amount || 0);
    const interval = sub.plan?.billing_interval || 'monthly';
    
    // Normalize to monthly
    if (interval === 'annually') return sum + (amount / 12);
    if (interval === 'quarterly') return sum + (amount / 3);
    return sum + amount;
  }, 0);

  return {
    total: subscriptions.length,
    active: activeSubscriptions.length,
    trial: subscriptions.filter(s => s.status === 'trial').length,
    mrr: mrr,
    churned: subscriptions.filter(s => s.status === 'cancelled').length,
  };
});

const statusOptions = [
  { label: 'All Statuses', value: '' },
  { label: 'Active', value: 'active' },
  { label: 'Trial', value: 'trial' },
  { label: 'Suspended', value: 'suspended' },
  { label: 'Cancelled', value: 'cancelled' },
  { label: 'Expired', value: 'expired' },
];

const statusOptionsForm = [
  { label: 'Active', value: 'active' },
  { label: 'Trial', value: 'trial' },
  { label: 'Suspended', value: 'suspended' },
  { label: 'Cancelled', value: 'cancelled' },
  { label: 'Expired', value: 'expired' },
];

const planOptions = computed(() => [
  { label: 'All Plans', value: '' },
  ...(billingStore.plans || []).map(p => ({ 
    label: p.name, 
    value: p.id 
  }))
]);

const activePlanOptions = computed(() => [
  { label: 'Select plan', value: '' },
  ...(billingStore.plans || [])
    .filter(p => p.status === 'active')
    .map(p => ({ 
      label: `${p.name} - ${formatCurrency(p.price)}/${p.billing_interval}`, 
      value: p.id 
    }))
]);

const customerOptions = computed(() => [
  { label: 'Select customer', value: '' },
  ...(crmStore.customers || []).map(c => ({ 
    label: c.name, 
    value: c.id 
  }))
]);

const columns = [
  { key: 'subscription_code', label: 'Code', sortable: true },
  { key: 'customer', label: 'Customer', sortable: false },
  { key: 'plan', label: 'Plan', sortable: false },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'start_date', label: 'Start Date', sortable: true },
  { key: 'next_billing_date', label: 'Next Billing', sortable: true },
  { key: 'current_period_amount', label: 'Amount', sortable: true },
];

const tableActions = computed(() => {
  return (row) => {
    const actions = [
      { key: 'view', label: 'View Details', icon: 'eye' }
    ];
    
    if (row.status === 'active') {
      actions.push({ key: 'suspend', label: 'Suspend', icon: 'pause', variant: 'warning' });
    }
    
    if (row.status === 'suspended') {
      actions.push({ key: 'resume', label: 'Resume', icon: 'play', variant: 'success' });
    }
    
    if (['active', 'suspended', 'trial'].includes(row.status)) {
      actions.push({ key: 'cancel', label: 'Cancel', icon: 'x-circle', variant: 'danger' });
    }
    
    if (row.status === 'expired') {
      actions.push({ key: 'renew', label: 'Renew', icon: 'refresh', variant: 'primary' });
    }
    
    return actions;
  };
});

const { sortedData, handleSort } = useTable(computed(() => billingStore.subscriptions || []));

const filteredSubscriptions = computed(() => {
  let data = sortedData.value;

  if (search.value) {
    const searchLower = search.value.toLowerCase();
    data = data.filter(sub =>
      sub.subscription_code?.toLowerCase().includes(searchLower) ||
      sub.customer?.name?.toLowerCase().includes(searchLower) ||
      sub.plan?.name?.toLowerCase().includes(searchLower)
    );
  }

  if (statusFilter.value) {
    data = data.filter(sub => sub.status === statusFilter.value);
  }

  if (planFilter.value) {
    data = data.filter(sub => sub.plan_id === planFilter.value);
  }

  return data;
});

const pagination = usePagination(filteredSubscriptions, 10);

const handlePageChange = (page) => {
  pagination.currentPage.value = page;
};

const modalTitle = computed(() => 
  editingId.value ? 'Edit Subscription' : 'Create Subscription'
);

const openCreateModal = () => {
  resetForm();
  editingId.value = null;
  modal.open();
};

const resetForm = () => {
  form.value = {
    customer_id: '',
    plan_id: '',
    subscription_code: '',
    start_date: new Date().toISOString().split('T')[0],
    end_date: '',
    next_billing_date: '',
    current_period_start: new Date().toISOString().split('T')[0],
    current_period_end: '',
    current_period_amount: 0,
    status: 'active',
  };
  errors.value = {};
};

const fetchData = async () => {
  loading.value = true;
  try {
    await Promise.all([
      billingStore.fetchSubscriptions(),
      billingStore.fetchPlans(),
      crmStore.fetchCustomers()
    ]);
  } catch (error) {
    showError('Failed to load data');
  } finally {
    loading.value = false;
  }
};

const handleSubmit = async () => {
  errors.value = {};
  saving.value = true;

  try {
    if (editingId.value) {
      await billingStore.updateSubscription(editingId.value, form.value);
      showSuccess('Subscription updated successfully');
    } else {
      await billingStore.createSubscription(form.value);
      showSuccess('Subscription created successfully');
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

const viewSubscription = (subscription) => {
  showError('Subscription detail view not yet implemented');
};

const suspendSubscription = async (subscription) => {
  if (!confirm(`Suspend subscription ${subscription.subscription_code}?`)) return;

  try {
    await billingStore.suspendSubscription(subscription.id);
    showSuccess('Subscription suspended successfully');
    await fetchData();
  } catch (error) {
    showError('Failed to suspend subscription');
  }
};

const resumeSubscription = async (subscription) => {
  if (!confirm(`Resume subscription ${subscription.subscription_code}?`)) return;

  try {
    await billingStore.resumeSubscription(subscription.id);
    showSuccess('Subscription resumed successfully');
    await fetchData();
  } catch (error) {
    showError('Failed to resume subscription');
  }
};

const cancelSubscription = async (subscription) => {
  if (!confirm(`Cancel subscription ${subscription.subscription_code}? This action cannot be undone.`)) return;

  try {
    await billingStore.cancelSubscription(subscription.id);
    showSuccess('Subscription cancelled successfully');
    await fetchData();
  } catch (error) {
    showError('Failed to cancel subscription');
  }
};

const renewSubscription = async (subscription) => {
  if (!confirm(`Renew subscription ${subscription.subscription_code}?`)) return;

  try {
    await billingStore.renewSubscription(subscription.id);
    showSuccess('Subscription renewed successfully');
    await fetchData();
  } catch (error) {
    showError('Failed to renew subscription');
  }
};

const getStatusVariant = (status) => {
  const variants = {
    active: 'success',
    trial: 'info',
    suspended: 'warning',
    cancelled: 'danger',
    expired: 'secondary',
  };
  return variants[status] || 'secondary';
};

const formatStatus = (status) => {
  const labels = {
    active: 'Active',
    trial: 'Trial',
    suspended: 'Suspended',
    cancelled: 'Cancelled',
    expired: 'Expired',
  };
  return labels[status] || status;
};

const formatInterval = (interval) => {
  const labels = {
    monthly: 'Monthly',
    quarterly: 'Quarterly',
    annually: 'Annually',
  };
  return labels[interval] || interval;
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
