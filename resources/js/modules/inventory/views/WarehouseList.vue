<template>
  <div>
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Warehouses</h1>
        <p class="mt-1 text-sm text-gray-500">Manage warehouse locations and inventory</p>
      </div>
      <BaseButton variant="primary" @click="openCreateModal">
        Create Warehouse
      </BaseButton>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-blue-100 rounded-lg">
              <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Total Warehouses</p>
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Total Capacity</p>
            <p class="text-2xl font-bold text-gray-900">{{ formatNumber(stats.totalCapacity) }}</p>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- Filters -->
    <BaseCard class="mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <BaseInput
          v-model="search"
          placeholder="Search warehouses..."
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

    <!-- Warehouses Table -->
    <BaseCard>
      <BaseTable
        :columns="columns"
        :data="filteredWarehouses"
        :loading="loading"
        :actions="tableActions"
        @sort="handleSort"
        @action:view="viewWarehouse"
        @action:edit="editWarehouse"
        @action:activate="activateWarehouse"
        @action:deactivate="deactivateWarehouse"
        @action:delete="deleteWarehouse"
      >
        <template #cell-warehouse_code="{ value }">
          <span class="font-medium text-gray-900">{{ value }}</span>
        </template>

        <template #cell-name="{ row }">
          <div>
            <div class="font-medium text-gray-900">{{ row.name }}</div>
            <div class="text-sm text-gray-500">{{ row.location || '' }}</div>
          </div>
        </template>

        <template #cell-type="{ value }">
          <BaseBadge :variant="getTypeVariant(value)">
            {{ formatType(value) }}
          </BaseBadge>
        </template>

        <template #cell-capacity="{ value }">
          {{ formatNumber(value) }}
        </template>

        <template #cell-current_stock="{ value }">
          {{ formatNumber(value || 0) }}
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
              v-model="form.warehouse_code"
              label="Warehouse Code"
              required
              placeholder="WH-001"
              :error="errors.warehouse_code"
            />
            
            <BaseInput
              v-model="form.name"
              label="Warehouse Name"
              required
              placeholder="Main Warehouse"
              :error="errors.name"
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <BaseInput
              v-model="form.location"
              label="Location"
              required
              placeholder="City, State"
              :error="errors.location"
            />
            
            <BaseSelect
              v-model="form.type"
              label="Warehouse Type"
              :options="typeOptions"
              required
              placeholder="Select type"
              :error="errors.type"
            />
          </div>

          <BaseInput
            v-model.number="form.capacity"
            label="Capacity"
            type="number"
            placeholder="0"
            :error="errors.capacity"
          />

          <BaseTextarea
            v-model="form.address"
            label="Address"
            placeholder="Full address..."
            rows="2"
          />

          <!-- Contact Information -->
          <div class="border-t pt-4">
            <h3 class="text-lg font-medium text-gray-900 mb-3">Contact Information</h3>
            <div class="grid grid-cols-2 gap-4">
              <BaseInput
                v-model="form.contact_name"
                label="Contact Name"
                placeholder="Contact person name"
              />
              
              <BaseInput
                v-model="form.contact_phone"
                label="Contact Phone"
                placeholder="+1 234 567 8900"
              />
            </div>

            <BaseInput
              v-model="form.contact_email"
              label="Contact Email"
              type="email"
              placeholder="contact@example.com"
              class="mt-4"
            />
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
          <BaseButton type="submit" variant="primary" :loading="saving">
            {{ isEditing ? 'Update' : 'Create' }} Warehouse
          </BaseButton>
        </div>
      </form>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useInventoryStore } from '../stores/inventoryStore';
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

const inventoryStore = useInventoryStore();
const modal = useModal();
const { notify } = useNotifications();
const pagination = usePagination();

const loading = ref(false);
const saving = ref(false);
const search = ref('');
const statusFilter = ref('');
const typeFilter = ref('');
const isEditing = ref(false);
const editingId = ref(null);
const sortColumn = ref('');
const sortDirection = ref('asc');

const form = ref({
  warehouse_code: '',
  name: '',
  location: '',
  type: '',
  capacity: 0,
  address: '',
  contact_name: '',
  contact_phone: '',
  contact_email: '',
  notes: ''
});

const errors = ref({});

const stats = computed(() => ({
  total: inventoryStore.warehouses?.length || 0,
  active: inventoryStore.warehouses?.filter(w => w.status === 'active').length || 0,
  inactive: inventoryStore.warehouses?.filter(w => w.status === 'inactive').length || 0,
  totalCapacity: inventoryStore.warehouses?.reduce((sum, w) => 
    sum + parseFloat(w.capacity || 0), 0
  ) || 0
}));

const statusOptions = [
  { value: '', label: 'All Statuses' },
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' }
];

const typeOptions = [
  { value: '', label: 'All Types' },
  { value: 'main', label: 'Main' },
  { value: 'regional', label: 'Regional' },
  { value: 'transit', label: 'Transit' },
  { value: 'returns', label: 'Returns' }
];

const columns = [
  { key: 'warehouse_code', label: 'Code', sortable: true },
  { key: 'name', label: 'Name', sortable: true },
  { key: 'type', label: 'Type', sortable: true },
  { key: 'capacity', label: 'Capacity', sortable: true },
  { key: 'current_stock', label: 'Current Stock', sortable: true },
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

const filteredWarehouses = computed(() => {
  let result = inventoryStore.warehouses || [];

  if (search.value) {
    const searchLower = search.value.toLowerCase();
    result = result.filter(w =>
      w.warehouse_code?.toLowerCase().includes(searchLower) ||
      w.name?.toLowerCase().includes(searchLower) ||
      w.location?.toLowerCase().includes(searchLower)
    );
  }

  if (statusFilter.value) {
    result = result.filter(w => w.status === statusFilter.value);
  }

  if (typeFilter.value) {
    result = result.filter(w => w.type === typeFilter.value);
  }

  if (sortColumn.value) {
    result.sort((a, b) => {
      let aVal = a[sortColumn.value];
      let bVal = b[sortColumn.value];
      
      if (['capacity', 'current_stock'].includes(sortColumn.value)) {
        aVal = parseFloat(aVal || 0);
        bVal = parseFloat(bVal || 0);
      }
      
      if (aVal < bVal) return sortDirection.value === 'asc' ? -1 : 1;
      if (aVal > bVal) return sortDirection.value === 'asc' ? 1 : -1;
      return 0;
    });
  }

  return result;
});

const modalTitle = computed(() => isEditing.value ? 'Edit Warehouse' : 'Create Warehouse');

onMounted(async () => {
  loading.value = true;
  try {
    await inventoryStore.fetchWarehouses();
  } catch (error) {
    notify('Failed to load warehouses', 'error');
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

function editWarehouse(warehouse) {
  isEditing.value = true;
  editingId.value = warehouse.id;
  form.value = {
    warehouse_code: warehouse.warehouse_code,
    name: warehouse.name,
    location: warehouse.location || '',
    type: warehouse.type || '',
    capacity: warehouse.capacity || 0,
    address: warehouse.address || '',
    contact_name: warehouse.contact_name || '',
    contact_phone: warehouse.contact_phone || '',
    contact_email: warehouse.contact_email || '',
    notes: warehouse.notes || ''
  };
  modal.open();
}

function viewWarehouse(warehouse) {
  notify('View functionality coming soon', 'info');
}

async function activateWarehouse(warehouse) {
  if (!confirm(`Activate warehouse ${warehouse.name}?`)) return;
  
  try {
    await inventoryStore.activateWarehouse(warehouse.id);
    notify('Warehouse activated successfully', 'success');
  } catch (error) {
    notify('Failed to activate warehouse', 'error');
  }
}

async function deactivateWarehouse(warehouse) {
  if (!confirm(`Deactivate warehouse ${warehouse.name}?`)) return;
  
  try {
    await inventoryStore.deactivateWarehouse(warehouse.id);
    notify('Warehouse deactivated successfully', 'success');
  } catch (error) {
    notify('Failed to deactivate warehouse', 'error');
  }
}

async function deleteWarehouse(warehouse) {
  if (!confirm(`Delete warehouse ${warehouse.name}? This action cannot be undone.`)) return;
  
  try {
    await inventoryStore.deleteWarehouse(warehouse.id);
    notify('Warehouse deleted successfully', 'success');
  } catch (error) {
    notify('Failed to delete warehouse', 'error');
  }
}

async function handleSubmit() {
  errors.value = {};
  
  if (!form.value.warehouse_code) {
    errors.value.warehouse_code = 'Warehouse code is required';
    return;
  }
  
  if (!form.value.name) {
    errors.value.name = 'Warehouse name is required';
    return;
  }

  if (!form.value.location) {
    errors.value.location = 'Location is required';
    return;
  }

  if (!form.value.type) {
    errors.value.type = 'Warehouse type is required';
    return;
  }

  saving.value = true;
  try {
    if (isEditing.value) {
      await inventoryStore.updateWarehouse(editingId.value, form.value);
      notify('Warehouse updated successfully', 'success');
    } else {
      await inventoryStore.createWarehouse(form.value);
      notify('Warehouse created successfully', 'success');
    }
    
    modal.close();
    resetForm();
  } catch (error) {
    notify(error.message || 'Failed to save warehouse', 'error');
  } finally {
    saving.value = false;
  }
}

function resetForm() {
  form.value = {
    warehouse_code: '',
    name: '',
    location: '',
    type: '',
    capacity: 0,
    address: '',
    contact_name: '',
    contact_phone: '',
    contact_email: '',
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

function getTypeVariant(type) {
  const variants = {
    main: 'primary',
    regional: 'info',
    transit: 'warning',
    returns: 'secondary'
  };
  return variants[type] || 'secondary';
}

function formatType(type) {
  const labels = {
    main: 'Main',
    regional: 'Regional',
    transit: 'Transit',
    returns: 'Returns'
  };
  return labels[type] || type;
}

function formatNumber(value) {
  return new Intl.NumberFormat('en-US').format(value || 0);
}
</script>
