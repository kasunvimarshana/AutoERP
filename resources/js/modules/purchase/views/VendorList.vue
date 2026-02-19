<template>
  <div>
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Vendors</h1>
        <p class="mt-1 text-sm text-gray-500">Manage vendor information and relationships</p>
      </div>
      <BaseButton variant="primary" @click="openCreateModal">
        Create Vendor
      </BaseButton>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
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
            <p class="text-sm font-medium text-gray-500">Total Vendors</p>
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
            <div class="p-3 bg-red-100 rounded-lg">
              <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Inactive</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.inactive }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-purple-100 rounded-lg">
              <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Total Purchases</p>
            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(stats.totalPurchases) }}</p>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- Filters -->
    <BaseCard class="mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <BaseInput
          v-model="search"
          placeholder="Search vendors..."
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
        <BaseSelect
          v-model="ratingFilter"
          :options="ratingOptions"
          placeholder="Filter by rating"
        />
      </div>
    </BaseCard>

    <!-- Vendors Table -->
    <BaseCard>
      <BaseTable
        :columns="columns"
        :data="filteredVendors"
        :loading="loading"
        :actions="tableActions"
        @sort="handleSort"
        @action:view="viewVendor"
        @action:edit="editVendor"
        @action:activate="activateVendor"
        @action:deactivate="deactivateVendor"
        @action:delete="deleteVendor"
      >
        <template #cell-vendor_code="{ value }">
          <span class="font-medium text-gray-900">{{ value }}</span>
        </template>

        <template #cell-name="{ row }">
          <div>
            <div class="font-medium text-gray-900">{{ row.name }}</div>
            <div class="text-sm text-gray-500">{{ row.email || '' }}</div>
          </div>
        </template>

        <template #cell-phone="{ value }">
          {{ value || 'N/A' }}
        </template>

        <template #cell-type="{ value }">
          <BaseBadge variant="info">
            {{ formatType(value) }}
          </BaseBadge>
        </template>

        <template #cell-rating="{ value }">
          <div class="flex items-center">
            <span class="text-yellow-500">{{ '★'.repeat(value || 0) }}</span>
            <span class="text-gray-300">{{ '★'.repeat(5 - (value || 0)) }}</span>
          </div>
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
    <BaseModal :show="modal.isOpen" :title="modalTitle" size="xl" @close="modal.close">
      <form @submit.prevent="handleSubmit">
        <div class="space-y-4">
          <!-- Basic Information -->
          <div class="grid grid-cols-2 gap-4">
            <BaseInput
              v-model="form.vendor_code"
              label="Vendor Code"
              required
              placeholder="VEN-001"
              :error="errors.vendor_code"
            />
            
            <BaseInput
              v-model="form.name"
              label="Vendor Name"
              required
              placeholder="Company Name"
              :error="errors.name"
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <BaseInput
              v-model="form.email"
              label="Email"
              type="email"
              placeholder="vendor@example.com"
              :error="errors.email"
            />
            
            <BaseInput
              v-model="form.phone"
              label="Phone"
              placeholder="+1 234 567 8900"
              :error="errors.phone"
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <BaseSelect
              v-model="form.type"
              label="Vendor Type"
              :options="typeOptions"
              required
              placeholder="Select type"
              :error="errors.type"
            />
            
            <BaseSelect
              v-model.number="form.rating"
              label="Rating"
              :options="ratingInputOptions"
              placeholder="Select rating"
            />
          </div>

          <BaseTextarea
            v-model="form.address"
            label="Address"
            placeholder="Full address..."
            rows="2"
          />

          <div class="grid grid-cols-2 gap-4">
            <BaseInput
              v-model="form.tax_id"
              label="Tax ID"
              placeholder="Tax identification number"
            />
            
            <BaseInput
              v-model="form.website"
              label="Website"
              placeholder="https://vendor.com"
            />
          </div>

          <BaseTextarea
            v-model="form.payment_terms"
            label="Payment Terms"
            placeholder="Payment terms..."
            rows="2"
          />

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
          <BaseButton type="submit" variant="primary" :loading="saving">
            {{ isEditing ? 'Update' : 'Create' }} Vendor
          </BaseButton>
        </div>
      </form>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePurchaseStore } from '../stores/purchaseStore';
import { useModal } from '@/composables/useModal';
import { usePagination } from '@/composables/usePagination';
import { useNotifications } from '@/composables/useNotifications';
import BaseCard from '@/components/layout/BaseCard.vue';
import BaseTable from '@/components/data/BaseTable.vue';
import BasePagination from '@/components/data/BasePagination.vue';
import BaseButton from '@/components/common/BaseButton.vue';
import BaseInput from '@/components/common/BaseInput.vue';
import BaseSelect from '@/components/common/BaseSelect.vue';
import BaseTextarea from '@/components/common/BaseTextarea.vue';
import BaseBadge from '@/components/common/BaseBadge.vue';
import BaseModal from '@/components/layout/BaseModal.vue';

const purchaseStore = usePurchaseStore();
const modal = useModal();
const { notify } = useNotifications();
const pagination = usePagination();

const loading = ref(false);
const saving = ref(false);
const search = ref('');
const statusFilter = ref('');
const typeFilter = ref('');
const ratingFilter = ref('');
const isEditing = ref(false);
const editingId = ref(null);
const sortColumn = ref('');
const sortDirection = ref('asc');

const form = ref({
  vendor_code: '',
  name: '',
  email: '',
  phone: '',
  type: '',
  rating: 0,
  address: '',
  payment_terms: '',
  tax_id: '',
  website: '',
  notes: ''
});

const errors = ref({});

const stats = computed(() => ({
  total: purchaseStore.vendors?.length || 0,
  active: purchaseStore.vendors?.filter(v => v.status === 'active').length || 0,
  inactive: purchaseStore.vendors?.filter(v => v.status === 'inactive').length || 0,
  totalPurchases: purchaseStore.purchaseOrders?.reduce((sum, po) => 
    sum + parseFloat(po.total_amount || 0), 0
  ) || 0
}));

const statusOptions = [
  { value: '', label: 'All Statuses' },
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' }
];

const typeOptions = [
  { value: '', label: 'All Types' },
  { value: 'manufacturer', label: 'Manufacturer' },
  { value: 'distributor', label: 'Distributor' },
  { value: 'wholesaler', label: 'Wholesaler' },
  { value: 'service_provider', label: 'Service Provider' },
  { value: 'contractor', label: 'Contractor' },
  { value: 'consultant', label: 'Consultant' },
  { value: 'other', label: 'Other' }
];

const ratingOptions = [
  { value: '', label: 'All Ratings' },
  { value: '5', label: '5 Stars' },
  { value: '4', label: '4 Stars' },
  { value: '3', label: '3 Stars' },
  { value: '2', label: '2 Stars' },
  { value: '1', label: '1 Star' }
];

const ratingInputOptions = [
  { value: 0, label: 'No Rating' },
  { value: 1, label: '1 Star' },
  { value: 2, label: '2 Stars' },
  { value: 3, label: '3 Stars' },
  { value: 4, label: '4 Stars' },
  { value: 5, label: '5 Stars' }
];

const columns = [
  { key: 'vendor_code', label: 'Vendor Code', sortable: true },
  { key: 'name', label: 'Name', sortable: true },
  { key: 'phone', label: 'Phone', sortable: false },
  { key: 'type', label: 'Type', sortable: true },
  { key: 'rating', label: 'Rating', sortable: true },
  { key: 'status', label: 'Status', sortable: true }
];

const tableActions = computed(() => {
  return (row) => {
    const actions = [
      { key: 'view', label: 'View', icon: 'eye' },
      { key: 'edit', label: 'Edit', icon: 'pencil' }
    ];
    
    if (row.status === 'active') {
      actions.push({ key: 'deactivate', label: 'Deactivate', icon: 'x-circle', variant: 'warning' });
    } else {
      actions.push({ key: 'activate', label: 'Activate', icon: 'check-circle', variant: 'success' });
    }
    
    actions.push({ key: 'delete', label: 'Delete', icon: 'trash', variant: 'danger' });
    
    return actions;
  };
});

const filteredVendors = computed(() => {
  let result = purchaseStore.vendors || [];

  if (search.value) {
    const searchLower = search.value.toLowerCase();
    result = result.filter(v =>
      v.vendor_code?.toLowerCase().includes(searchLower) ||
      v.name?.toLowerCase().includes(searchLower) ||
      v.email?.toLowerCase().includes(searchLower) ||
      v.phone?.toLowerCase().includes(searchLower)
    );
  }

  if (statusFilter.value) {
    result = result.filter(v => v.status === statusFilter.value);
  }

  if (typeFilter.value) {
    result = result.filter(v => v.type === typeFilter.value);
  }

  if (ratingFilter.value) {
    result = result.filter(v => v.rating === parseInt(ratingFilter.value));
  }

  if (sortColumn.value) {
    result.sort((a, b) => {
      let aVal = a[sortColumn.value];
      let bVal = b[sortColumn.value];
      
      if (sortColumn.value === 'rating') {
        aVal = parseInt(aVal || 0);
        bVal = parseInt(bVal || 0);
      }
      
      if (aVal < bVal) return sortDirection.value === 'asc' ? -1 : 1;
      if (aVal > bVal) return sortDirection.value === 'asc' ? 1 : -1;
      return 0;
    });
  }

  return result;
});

const modalTitle = computed(() => isEditing.value ? 'Edit Vendor' : 'Create Vendor');

onMounted(async () => {
  loading.value = true;
  try {
    await Promise.all([
      purchaseStore.fetchVendors(),
      purchaseStore.fetchPurchaseOrders()
    ]);
  } catch (error) {
    notify('Failed to load data', 'error');
  } finally {
    loading.value = false;
  }
});

function openCreateModal() {
  isEditing.value = false;
  editingId.value = null;
  resetForm();
  modal.open();
}

function editVendor(vendor) {
  isEditing.value = true;
  editingId.value = vendor.id;
  form.value = {
    vendor_code: vendor.vendor_code,
    name: vendor.name,
    email: vendor.email || '',
    phone: vendor.phone || '',
    type: vendor.type || '',
    rating: vendor.rating || 0,
    address: vendor.address || '',
    payment_terms: vendor.payment_terms || '',
    tax_id: vendor.tax_id || '',
    website: vendor.website || '',
    notes: vendor.notes || ''
  };
  modal.open();
}

function viewVendor(vendor) {
  notify('View functionality coming soon', 'info');
}

async function activateVendor(vendor) {
  if (!confirm(`Activate vendor ${vendor.name}?`)) return;
  
  try {
    await purchaseStore.activateVendor(vendor.id);
    notify('Vendor activated successfully', 'success');
  } catch (error) {
    notify('Failed to activate vendor', 'error');
  }
}

async function deactivateVendor(vendor) {
  if (!confirm(`Deactivate vendor ${vendor.name}?`)) return;
  
  try {
    await purchaseStore.deactivateVendor(vendor.id);
    notify('Vendor deactivated successfully', 'success');
  } catch (error) {
    notify('Failed to deactivate vendor', 'error');
  }
}

async function deleteVendor(vendor) {
  if (!confirm(`Delete vendor ${vendor.name}? This action cannot be undone.`)) return;
  
  try {
    await purchaseStore.deleteVendor(vendor.id);
    notify('Vendor deleted successfully', 'success');
  } catch (error) {
    notify('Failed to delete vendor', 'error');
  }
}

async function handleSubmit() {
  errors.value = {};
  
  if (!form.value.vendor_code) {
    errors.value.vendor_code = 'Vendor code is required';
    return;
  }
  
  if (!form.value.name) {
    errors.value.name = 'Vendor name is required';
    return;
  }

  saving.value = true;
  try {
    if (isEditing.value) {
      await purchaseStore.updateVendor(editingId.value, form.value);
      notify('Vendor updated successfully', 'success');
    } else {
      await purchaseStore.createVendor(form.value);
      notify('Vendor created successfully', 'success');
    }
    
    modal.close();
    resetForm();
  } catch (error) {
    notify(error.message || 'Failed to save vendor', 'error');
  } finally {
    saving.value = false;
  }
}

function resetForm() {
  form.value = {
    vendor_code: '',
    name: '',
    email: '',
    phone: '',
    type: '',
    rating: 0,
    address: '',
    payment_terms: '',
    tax_id: '',
    website: '',
    notes: ''
  };
  errors.value = {};
}

function handleSort(column) {
  if (sortColumn.value === column) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortColumn.value = column;
    sortDirection.value = 'asc';
  }
}

function handlePageChange(page) {
  pagination.currentPage = page;
}

function getStatusVariant(status) {
  const variants = {
    active: 'success',
    inactive: 'secondary'
  };
  return variants[status] || 'secondary';
}

function formatStatus(status) {
  const labels = {
    active: 'Active',
    inactive: 'Inactive'
  };
  return labels[status] || status;
}

function formatType(type) {
  const labels = {
    manufacturer: 'Manufacturer',
    distributor: 'Distributor',
    wholesaler: 'Wholesaler',
    service_provider: 'Service Provider',
    contractor: 'Contractor',
    consultant: 'Consultant',
    other: 'Other'
  };
  return labels[type] || type;
}

function formatCurrency(amount) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount || 0);
}
</script>
