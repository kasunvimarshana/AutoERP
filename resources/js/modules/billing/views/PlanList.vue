<template>
  <div>
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Subscription Plans</h1>
        <p class="mt-1 text-sm text-gray-500">Manage subscription plans and pricing</p>
      </div>
      <BaseButton variant="primary" @click="openCreateModal">
        Create Plan
      </BaseButton>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-blue-100 rounded-lg">
              <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Total Plans</p>
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
            <p class="text-sm font-medium text-gray-500">Active Plans</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.active }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-purple-100 rounded-lg">
              <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Free Plans</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.free }}</p>
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
            <p class="text-sm font-medium text-gray-500">Paid Plans</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.paid }}</p>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- Filters -->
    <BaseCard class="mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <BaseInput
          v-model="search"
          placeholder="Search plans..."
          type="search"
        />
        <BaseSelect
          v-model="typeFilter"
          :options="typeOptions"
          placeholder="Filter by type"
        />
        <BaseSelect
          v-model="intervalFilter"
          :options="intervalOptions"
          placeholder="Filter by billing interval"
        />
      </div>
    </BaseCard>

    <!-- Plans Table -->
    <BaseCard>
      <BaseTable
        :columns="columns"
        :data="filteredPlans"
        :loading="loading"
        :actions="tableActions"
        @sort="handleSort"
        @action:view="viewPlan"
        @action:edit="editPlan"
        @action:activate="activatePlan"
        @action:deactivate="deactivatePlan"
        @action:delete="deletePlan"
      >
        <template #cell-plan_code="{ value }">
          <span class="font-medium text-gray-900">{{ value }}</span>
        </template>

        <template #cell-type="{ value }">
          <BaseBadge :variant="getTypeVariant(value)">
            {{ formatType(value) }}
          </BaseBadge>
        </template>

        <template #cell-billing_interval="{ value }">
          <BaseBadge variant="secondary">
            {{ formatInterval(value) }}
          </BaseBadge>
        </template>

        <template #cell-price="{ value }">
          <span class="font-medium text-gray-900">{{ formatCurrency(value) }}</span>
        </template>

        <template #cell-trial_days="{ value }">
          <span class="text-sm text-gray-600">
            {{ value || 0 }} days
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
              v-model="form.plan_code"
              label="Plan Code"
              required
              placeholder="PLAN-001"
              :error="errors.plan_code"
            />

            <BaseSelect
              v-model="form.type"
              label="Plan Type"
              :options="typeOptions.filter(o => o.value)"
              required
              :error="errors.type"
            />
          </div>

          <BaseInput
            v-model="form.name"
            label="Plan Name"
            required
            placeholder="Basic Plan"
            :error="errors.name"
          />

          <BaseTextarea
            v-model="form.description"
            label="Description"
            placeholder="Plan description..."
            :rows="3"
          />

          <div class="grid grid-cols-3 gap-4">
            <BaseSelect
              v-model="form.billing_interval"
              label="Billing Interval"
              :options="intervalOptions.filter(o => o.value)"
              required
              :error="errors.billing_interval"
            />

            <BaseInput
              v-model.number="form.price"
              label="Price"
              type="number"
              min="0"
              step="0.01"
              required
              :error="errors.price"
            />

            <BaseInput
              v-model.number="form.trial_days"
              label="Trial Days"
              type="number"
              min="0"
              placeholder="0"
              :error="errors.trial_days"
            />
          </div>

          <BaseTextarea
            v-model="form.features"
            label="Features (JSON)"
            placeholder='["Feature 1", "Feature 2", "Feature 3"]'
            :rows="3"
            :error="errors.features"
          />

          <BaseTextarea
            v-model="form.limits"
            label="Limits (JSON)"
            placeholder='{"users": 10, "storage": 100}'
            :rows="3"
            :error="errors.limits"
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
            {{ editingId ? 'Update' : 'Create' }} Plan
          </BaseButton>
        </div>
      </form>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useBillingStore } from '../stores/billingStore';
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

const billingStore = useBillingStore();
const modal = useModal();
const { showSuccess, showError } = useNotifications();

const loading = ref(false);
const saving = ref(false);
const search = ref('');
const typeFilter = ref('');
const intervalFilter = ref('');
const editingId = ref(null);

const form = ref({
  plan_code: '',
  name: '',
  description: '',
  type: 'paid',
  billing_interval: 'monthly',
  price: 0,
  trial_days: 0,
  features: '',
  limits: '',
  status: 'active',
});

const errors = ref({});

const stats = computed(() => {
  const plans = billingStore.plans || [];
  return {
    total: plans.length,
    active: plans.filter(p => p.status === 'active').length,
    free: plans.filter(p => p.type === 'free').length,
    paid: plans.filter(p => ['paid', 'trial'].includes(p.type)).length,
  };
});

const typeOptions = [
  { label: 'All Types', value: '' },
  { label: 'Free', value: 'free' },
  { label: 'Trial', value: 'trial' },
  { label: 'Paid', value: 'paid' },
  { label: 'Custom', value: 'custom' },
];

const intervalOptions = [
  { label: 'All Intervals', value: '' },
  { label: 'Monthly', value: 'monthly' },
  { label: 'Quarterly', value: 'quarterly' },
  { label: 'Annually', value: 'annually' },
];

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
];

const columns = [
  { key: 'plan_code', label: 'Plan Code', sortable: true },
  { key: 'name', label: 'Name', sortable: true },
  { key: 'type', label: 'Type', sortable: true },
  { key: 'billing_interval', label: 'Interval', sortable: true },
  { key: 'price', label: 'Price', sortable: true },
  { key: 'trial_days', label: 'Trial', sortable: true },
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

const { sortedData, handleSort } = useTable(computed(() => billingStore.plans || []));

const filteredPlans = computed(() => {
  let data = sortedData.value;

  if (search.value) {
    const searchLower = search.value.toLowerCase();
    data = data.filter(plan =>
      plan.plan_code?.toLowerCase().includes(searchLower) ||
      plan.name?.toLowerCase().includes(searchLower) ||
      plan.description?.toLowerCase().includes(searchLower)
    );
  }

  if (typeFilter.value) {
    data = data.filter(plan => plan.type === typeFilter.value);
  }

  if (intervalFilter.value) {
    data = data.filter(plan => plan.billing_interval === intervalFilter.value);
  }

  return data;
});

const pagination = usePagination(filteredPlans, 10);

const handlePageChange = (page) => {
  pagination.currentPage.value = page;
};

const modalTitle = computed(() => 
  editingId.value ? 'Edit Plan' : 'Create Plan'
);

const openCreateModal = () => {
  resetForm();
  editingId.value = null;
  modal.open();
};

const resetForm = () => {
  form.value = {
    plan_code: '',
    name: '',
    description: '',
    type: 'paid',
    billing_interval: 'monthly',
    price: 0,
    trial_days: 0,
    features: '',
    limits: '',
    status: 'active',
  };
  errors.value = {};
};

const fetchPlans = async () => {
  loading.value = true;
  try {
    await billingStore.fetchPlans();
  } catch (error) {
    showError('Failed to load plans');
  } finally {
    loading.value = false;
  }
};

const handleSubmit = async () => {
  errors.value = {};
  saving.value = true;

  try {
    const data = { ...form.value };
    
    // Parse JSON fields
    if (data.features) {
      try {
        data.features = JSON.parse(data.features);
      } catch (e) {
        errors.value.features = 'Invalid JSON format';
        saving.value = false;
        return;
      }
    }
    
    if (data.limits) {
      try {
        data.limits = JSON.parse(data.limits);
      } catch (e) {
        errors.value.limits = 'Invalid JSON format';
        saving.value = false;
        return;
      }
    }

    if (editingId.value) {
      await billingStore.updatePlan(editingId.value, data);
      showSuccess('Plan updated successfully');
    } else {
      await billingStore.createPlan(data);
      showSuccess('Plan created successfully');
    }
    
    modal.close();
    resetForm();
    await fetchPlans();
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    }
    showError(error.response?.data?.message || 'Operation failed');
  } finally {
    saving.value = false;
  }
};

const viewPlan = (plan) => {
  showError('Plan detail view not yet implemented');
};

const editPlan = (plan) => {
  editingId.value = plan.id;
  form.value = {
    plan_code: plan.plan_code,
    name: plan.name,
    description: plan.description || '',
    type: plan.type,
    billing_interval: plan.billing_interval,
    price: parseFloat(plan.price || 0),
    trial_days: plan.trial_days || 0,
    features: plan.features ? JSON.stringify(plan.features, null, 2) : '',
    limits: plan.limits ? JSON.stringify(plan.limits, null, 2) : '',
    status: plan.status,
  };
  modal.open();
};

const activatePlan = async (plan) => {
  if (!confirm(`Activate plan ${plan.name}?`)) return;

  try {
    await billingStore.activatePlan(plan.id);
    showSuccess('Plan activated successfully');
    await fetchPlans();
  } catch (error) {
    showError('Failed to activate plan');
  }
};

const deactivatePlan = async (plan) => {
  if (!confirm(`Deactivate plan ${plan.name}?`)) return;

  try {
    await billingStore.deactivatePlan(plan.id);
    showSuccess('Plan deactivated successfully');
    await fetchPlans();
  } catch (error) {
    showError('Failed to deactivate plan');
  }
};

const deletePlan = async (plan) => {
  if (!confirm(`Are you sure you want to delete ${plan.name}?`)) return;

  try {
    await billingStore.deletePlan(plan.id);
    showSuccess('Plan deleted successfully');
    await fetchPlans();
  } catch (error) {
    showError('Failed to delete plan');
  }
};

const getTypeVariant = (type) => {
  const variants = {
    free: 'success',
    trial: 'info',
    paid: 'primary',
    custom: 'warning',
  };
  return variants[type] || 'secondary';
};

const formatType = (type) => {
  const labels = {
    free: 'Free',
    trial: 'Trial',
    paid: 'Paid',
    custom: 'Custom',
  };
  return labels[type] || type;
};

const formatInterval = (interval) => {
  const labels = {
    monthly: 'Monthly',
    quarterly: 'Quarterly',
    annually: 'Annually',
  };
  return labels[interval] || interval;
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount || 0);
};

onMounted(() => {
  fetchPlans();
});
</script>
