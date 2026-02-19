<template>
  <div>
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Customers</h1>
        <p class="mt-1 text-sm text-gray-500">Manage your customer relationships</p>
      </div>
      <BaseButton variant="primary" @click="openCreateModal">
        Add Customer
      </BaseButton>
    </div>

    <!-- Filters -->
    <BaseCard class="mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <BaseInput
          v-model="search"
          placeholder="Search customers..."
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

    <!-- Customers Table -->
    <BaseCard>
      <BaseTable
        :columns="columns"
        :data="filteredCustomers"
        :loading="loading"
        :actions="tableActions"
        @sort="handleSort"
        @action:view="viewCustomer"
        @action:edit="editCustomer"
        @action:delete="deleteCustomer"
      >
        <template #cell-status="{ value }">
          <BaseBadge :variant="getStatusVariant(value)">
            {{ value }}
          </BaseBadge>
        </template>
        
        <template #cell-type="{ value }">
          <BaseBadge variant="secondary">
            {{ value }}
          </BaseBadge>
        </template>

        <template #cell-email="{ value }">
          <a :href="`mailto:${value}`" class="text-indigo-600 hover:text-indigo-900">
            {{ value }}
          </a>
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
            label="Customer Name"
            required
            placeholder="Enter customer name"
            :error="errors.name"
          />
          
          <BaseInput
            v-model="form.email"
            label="Email"
            type="email"
            required
            placeholder="customer@example.com"
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
              v-model="form.type"
              label="Customer Type"
              :options="typeOptions"
              required
              :error="errors.type"
            />

            <BaseSelect
              v-model="form.status"
              label="Status"
              :options="statusOptions"
              required
              :error="errors.status"
            />
          </div>
          
          <BaseTextarea
            v-model="form.address"
            label="Address"
            placeholder="Enter customer address"
            :rows="3"
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
            {{ editingId ? 'Update' : 'Create' }} Customer
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
const typeFilter = ref('');
const editingId = ref(null);

// Form
const form = ref({
  name: '',
  email: '',
  phone: '',
  type: 'individual',
  status: 'active',
  address: '',
  notes: '',
});

const errors = ref({});

// Options
const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Pending', value: 'pending' },
  { label: 'Suspended', value: 'suspended' },
];

const typeOptions = [
  { label: 'Individual', value: 'individual' },
  { label: 'Company', value: 'company' },
  { label: 'Partner', value: 'partner' },
];

// Table configuration
const columns = [
  { key: 'name', label: 'Name', sortable: true },
  { key: 'email', label: 'Email', sortable: true },
  { key: 'phone', label: 'Phone' },
  { key: 'type', label: 'Type', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
];

const tableActions = [
  { key: 'view', label: 'View', variant: 'secondary' },
  { key: 'edit', label: 'Edit', variant: 'primary' },
  { key: 'delete', label: 'Delete', variant: 'danger' },
];

// Filtering and sorting
const { sortedData, handleSort } = useTable(computed(() => crmStore.customers));

const filteredCustomers = computed(() => {
  let data = sortedData.value;
  
  // Search filter
  if (search.value) {
    const searchLower = search.value.toLowerCase();
    data = data.filter(customer =>
      customer.name?.toLowerCase().includes(searchLower) ||
      customer.email?.toLowerCase().includes(searchLower) ||
      customer.phone?.toLowerCase().includes(searchLower)
    );
  }
  
  // Status filter
  if (statusFilter.value) {
    data = data.filter(customer => customer.status === statusFilter.value);
  }
  
  // Type filter
  if (typeFilter.value) {
    data = data.filter(customer => customer.type === typeFilter.value);
  }
  
  return data;
});

// Pagination
const pagination = usePagination(filteredCustomers, 10);

const handlePageChange = (page) => {
  pagination.currentPage.value = page;
};

// Modal
const modalTitle = computed(() => 
  editingId.value ? 'Edit Customer' : 'Add Customer'
);

const openCreateModal = () => {
  resetForm();
  editingId.value = null;
  modal.open();
};

const resetForm = () => {
  form.value = {
    name: '',
    email: '',
    phone: '',
    type: 'individual',
    status: 'active',
    address: '',
    notes: '',
  };
  errors.value = {};
};

// CRUD Operations
const fetchCustomers = async () => {
  loading.value = true;
  try {
    await crmStore.fetchCustomers();
  } catch (error) {
    showError('Failed to load customers');
  } finally {
    loading.value = false;
  }
};

const handleSubmit = async () => {
  errors.value = {};
  saving.value = true;

  try {
    if (editingId.value) {
      await crmStore.updateCustomer(editingId.value, form.value);
      showSuccess('Customer updated successfully');
    } else {
      await crmStore.createCustomer(form.value);
      showSuccess('Customer created successfully');
    }
    
    modal.close();
    resetForm();
    await fetchCustomers();
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    }
    showError(error.response?.data?.message || 'Operation failed');
  } finally {
    saving.value = false;
  }
};

const viewCustomer = (customer) => {
  // Navigate to customer detail page
  console.log('View customer:', customer);
  showError('Customer detail view not yet implemented');
};

const editCustomer = (customer) => {
  editingId.value = customer.id;
  form.value = { ...customer };
  modal.open();
};

const deleteCustomer = async (customer) => {
  if (!confirm(`Are you sure you want to delete ${customer.name}?`)) {
    return;
  }

  try {
    await crmStore.deleteCustomer(customer.id);
    showSuccess('Customer deleted successfully');
    await fetchCustomers();
  } catch (error) {
    showError('Failed to delete customer');
  }
};

// Utilities
const getStatusVariant = (status) => {
  const variants = {
    active: 'success',
    inactive: 'secondary',
    pending: 'warning',
    suspended: 'danger',
  };
  return variants[status] || 'secondary';
};

// Lifecycle
onMounted(() => {
  fetchCustomers();
});
</script>
