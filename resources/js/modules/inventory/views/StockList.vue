<template>
  <div>
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Stock Management</h1>
        <p class="mt-1 text-sm text-gray-500">Manage inventory stock and movements</p>
      </div>
      <BaseButton variant="primary" @click="openMovementModal">
        Record Movement
      </BaseButton>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-blue-100 rounded-lg">
              <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Total Items</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.totalItems }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-yellow-100 rounded-lg">
              <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Low Stock Items</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.lowStockItems }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-green-100 rounded-lg">
              <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Total Value</p>
            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(stats.totalValue) }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-purple-100 rounded-lg">
              <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Available Quantity</p>
            <p class="text-2xl font-bold text-gray-900">{{ formatNumber(stats.availableQuantity) }}</p>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- Filters -->
    <BaseCard class="mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <BaseInput
          v-model="search"
          placeholder="Search products..."
          type="search"
        />
        <BaseSelect
          v-model="warehouseFilter"
          :options="warehouseOptions"
          placeholder="Filter by warehouse"
        />
        <BaseSelect
          v-model="valuationMethodFilter"
          :options="valuationMethodOptions"
          placeholder="Filter by valuation method"
        />
      </div>
    </BaseCard>

    <!-- Stock Table -->
    <BaseCard>
      <BaseTable
        :columns="columns"
        :data="filteredStock"
        :loading="loading"
        :actions="tableActions"
        @sort="handleSort"
        @action:view-movements="viewMovements"
        @action:adjust="adjustStock"
        @action:reserve="reserveStock"
        @action:transfer="transferStock"
        @action:reorder="reorderStock"
      >
        <template #cell-product="{ row }">
          <div>
            <div class="font-medium text-gray-900">{{ row.product?.name || 'N/A' }}</div>
            <div class="text-sm text-gray-500">{{ row.product?.product_code || '' }}</div>
          </div>
        </template>

        <template #cell-warehouse="{ row }">
          <div class="text-sm text-gray-900">{{ row.warehouse?.name || 'N/A' }}</div>
        </template>

        <template #cell-quantity="{ value }">
          {{ formatNumber(value) }}
        </template>

        <template #cell-reserved_quantity="{ value }">
          {{ formatNumber(value) }}
        </template>

        <template #cell-available_quantity="{ value, row }">
          <div>
            <span :class="{ 'text-red-600 font-semibold': isLowStock(row) }">
              {{ formatNumber(value) }}
            </span>
            <BaseBadge v-if="isLowStock(row)" variant="danger" class="ml-2">Low Stock</BaseBadge>
          </div>
        </template>

        <template #cell-unit_cost="{ value }">
          {{ formatCurrency(value) }}
        </template>

        <template #cell-total_value="{ value }">
          {{ formatCurrency(value) }}
        </template>

        <template #cell-valuation_method="{ value }">
          <BaseBadge :variant="getValuationVariant(value)">
            {{ formatValuationMethod(value) }}
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

    <!-- Stock Movement Modal -->
    <BaseModal :show="movementModal.isOpen" :title="movementModalTitle" size="lg" @close="movementModal.close">
      <form @submit.prevent="handleMovementSubmit">
        <div class="space-y-4">
          <BaseSelect
            v-model="movementForm.movement_type"
            label="Movement Type"
            :options="movementTypeOptions"
            required
            placeholder="Select movement type"
            :error="movementErrors.movement_type"
          />

          <BaseSelect
            v-model.number="movementForm.warehouse_id"
            label="Warehouse"
            :options="warehouseSelectOptions"
            required
            placeholder="Select warehouse"
            :error="movementErrors.warehouse_id"
          />

          <BaseSelect
            v-model.number="movementForm.product_id"
            label="Product"
            :options="productOptions"
            required
            placeholder="Select product"
            :error="movementErrors.product_id"
          />

          <BaseInput
            v-model.number="movementForm.quantity"
            label="Quantity"
            type="number"
            required
            placeholder="0"
            :error="movementErrors.quantity"
          />

          <BaseInput
            v-model="movementForm.reference"
            label="Reference"
            placeholder="Reference number or document"
          />

          <BaseTextarea
            v-model="movementForm.notes"
            label="Notes"
            placeholder="Additional notes..."
            rows="3"
          />
        </div>

        <div class="mt-6 flex justify-end space-x-3">
          <BaseButton type="button" variant="secondary" @click="movementModal.close">
            Cancel
          </BaseButton>
          <BaseButton type="submit" variant="primary" :loading="saving">
            Record Movement
          </BaseButton>
        </div>
      </form>
    </BaseModal>

    <!-- Movements History Modal -->
    <BaseModal :show="historyModal.isOpen" title="Stock Movements History" size="xl" @close="historyModal.close">
      <div class="space-y-4">
        <BaseTable
          :columns="movementColumns"
          :data="inventoryStore.movements"
          :loading="loadingMovements"
        >
          <template #cell-movement_type="{ value }">
            <BaseBadge :variant="getMovementTypeVariant(value)">
              {{ formatMovementType(value) }}
            </BaseBadge>
          </template>

          <template #cell-quantity="{ value }">
            {{ formatNumber(value) }}
          </template>

          <template #cell-unit_cost="{ value }">
            {{ formatCurrency(value) }}
          </template>

          <template #cell-total_cost="{ value }">
            {{ formatCurrency(value) }}
          </template>

          <template #cell-created_at="{ value }">
            {{ formatDate(value) }}
          </template>
        </BaseTable>
      </div>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useInventoryStore } from '../stores/inventoryStore';
import { useProductStore } from '@/modules/product/stores/productStore';
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
const productStore = useProductStore();
const movementModal = useModal();
const historyModal = useModal();
const { notify } = useNotifications();
const pagination = usePagination();

const loading = ref(false);
const loadingMovements = ref(false);
const saving = ref(false);
const search = ref('');
const warehouseFilter = ref('');
const valuationMethodFilter = ref('');
const sortColumn = ref('');
const sortDirection = ref('asc');
const selectedStockItem = ref(null);

const movementForm = ref({
  movement_type: '',
  warehouse_id: null,
  product_id: null,
  quantity: 0,
  reference: '',
  notes: ''
});

const movementErrors = ref({});

const stats = computed(() => {
  const stock = inventoryStore.stock || [];
  return {
    totalItems: stock.length,
    lowStockItems: stock.filter(s => isLowStock(s)).length,
    totalValue: stock.reduce((sum, s) => sum + parseFloat(s.total_value || 0), 0),
    availableQuantity: stock.reduce((sum, s) => sum + parseFloat(s.available_quantity || 0), 0)
  };
});

const warehouseOptions = computed(() => {
  const options = [{ value: '', label: 'All Warehouses' }];
  (inventoryStore.warehouses || []).forEach(w => {
    options.push({ value: w.id, label: w.name });
  });
  return options;
});

const warehouseSelectOptions = computed(() => {
  return (inventoryStore.warehouses || []).map(w => ({
    value: w.id,
    label: w.name
  }));
});

const productOptions = computed(() => {
  return (productStore.products || []).map(p => ({
    value: p.id,
    label: `${p.product_code} - ${p.name}`
  }));
});

const valuationMethodOptions = [
  { value: '', label: 'All Methods' },
  { value: 'fifo', label: 'FIFO' },
  { value: 'lifo', label: 'LIFO' },
  { value: 'weighted_average', label: 'Weighted Average' },
  { value: 'standard_cost', label: 'Standard Cost' }
];

const movementTypeOptions = [
  { value: 'receive', label: 'Receive' },
  { value: 'issue', label: 'Issue' },
  { value: 'transfer', label: 'Transfer' },
  { value: 'adjust', label: 'Adjust' },
  { value: 'reserve', label: 'Reserve' },
  { value: 'release', label: 'Release' }
];

const columns = [
  { key: 'product', label: 'Product', sortable: true },
  { key: 'warehouse', label: 'Warehouse', sortable: true },
  { key: 'quantity', label: 'Quantity', sortable: true },
  { key: 'reserved_quantity', label: 'Reserved', sortable: true },
  { key: 'available_quantity', label: 'Available', sortable: true },
  { key: 'unit_cost', label: 'Unit Cost', sortable: true },
  { key: 'total_value', label: 'Total Value', sortable: true },
  { key: 'valuation_method', label: 'Valuation Method', sortable: true }
];

const movementColumns = [
  { key: 'movement_type', label: 'Type', sortable: true },
  { key: 'quantity', label: 'Quantity', sortable: true },
  { key: 'unit_cost', label: 'Unit Cost', sortable: true },
  { key: 'total_cost', label: 'Total Cost', sortable: true },
  { key: 'reference', label: 'Reference', sortable: false },
  { key: 'created_at', label: 'Date', sortable: true }
];

const tableActions = computed(() => {
  return () => {
    return [
      { key: 'view-movements', label: 'View Movements', icon: 'eye' },
      { key: 'adjust', label: 'Adjust Stock', icon: 'adjustments' },
      { key: 'reserve', label: 'Reserve Stock', icon: 'lock-closed' },
      { key: 'transfer', label: 'Transfer', icon: 'switch-horizontal' },
      { key: 'reorder', label: 'Reorder', icon: 'refresh', variant: 'warning' }
    ];
  };
});

const filteredStock = computed(() => {
  let result = inventoryStore.stock || [];

  if (search.value) {
    const searchLower = search.value.toLowerCase();
    result = result.filter(s =>
      s.product?.name?.toLowerCase().includes(searchLower) ||
      s.product?.product_code?.toLowerCase().includes(searchLower) ||
      s.warehouse?.name?.toLowerCase().includes(searchLower)
    );
  }

  if (warehouseFilter.value) {
    result = result.filter(s => s.warehouse_id === warehouseFilter.value);
  }

  if (valuationMethodFilter.value) {
    result = result.filter(s => s.valuation_method === valuationMethodFilter.value);
  }

  if (sortColumn.value) {
    result.sort((a, b) => {
      let aVal = a[sortColumn.value];
      let bVal = b[sortColumn.value];
      
      if (['quantity', 'reserved_quantity', 'available_quantity', 'unit_cost', 'total_value'].includes(sortColumn.value)) {
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

const movementModalTitle = computed(() => 'Record Stock Movement');

onMounted(async () => {
  loading.value = true;
  try {
    await Promise.all([
      inventoryStore.fetchStock(),
      inventoryStore.fetchWarehouses(),
      productStore.fetchProducts()
    ]);
  } catch (error) {
    notify('Failed to load data', 'error');
  } finally {
    loading.value = false;
  }
});

function openMovementModal() {
  resetMovementForm();
  movementModal.open();
}

async function viewMovements(stockItem) {
  selectedStockItem.value = stockItem;
  loadingMovements.value = true;
  historyModal.open();
  
  try {
    await inventoryStore.fetchMovements({
      warehouse_id: stockItem.warehouse_id,
      product_id: stockItem.product_id
    });
  } catch (error) {
    notify('Failed to load movements', 'error');
  } finally {
    loadingMovements.value = false;
  }
}

function adjustStock(stockItem) {
  resetMovementForm();
  movementForm.value.movement_type = 'adjust';
  movementForm.value.warehouse_id = stockItem.warehouse_id;
  movementForm.value.product_id = stockItem.product_id;
  movementModal.open();
}

function reserveStock(stockItem) {
  resetMovementForm();
  movementForm.value.movement_type = 'reserve';
  movementForm.value.warehouse_id = stockItem.warehouse_id;
  movementForm.value.product_id = stockItem.product_id;
  movementModal.open();
}

function transferStock(stockItem) {
  resetMovementForm();
  movementForm.value.movement_type = 'transfer';
  movementForm.value.product_id = stockItem.product_id;
  movementModal.open();
}

function reorderStock(stockItem) {
  notify('Reorder functionality coming soon', 'info');
}

async function handleMovementSubmit() {
  movementErrors.value = {};
  
  if (!movementForm.value.movement_type) {
    movementErrors.value.movement_type = 'Movement type is required';
    return;
  }
  
  if (!movementForm.value.warehouse_id) {
    movementErrors.value.warehouse_id = 'Warehouse is required';
    return;
  }

  if (!movementForm.value.product_id) {
    movementErrors.value.product_id = 'Product is required';
    return;
  }

  if (!movementForm.value.quantity || movementForm.value.quantity <= 0) {
    movementErrors.value.quantity = 'Valid quantity is required';
    return;
  }

  saving.value = true;
  try {
    const movementType = movementForm.value.movement_type;
    
    switch (movementType) {
      case 'receive':
        await inventoryStore.receiveStock(movementForm.value);
        break;
      case 'issue':
        await inventoryStore.issueStock(movementForm.value);
        break;
      case 'transfer':
        await inventoryStore.transferStock(movementForm.value);
        break;
      case 'adjust':
        await inventoryStore.adjustStock(movementForm.value);
        break;
      case 'reserve':
        await inventoryStore.reserveStock(movementForm.value);
        break;
      case 'release':
        await inventoryStore.releaseStock(movementForm.value);
        break;
    }
    
    notify('Stock movement recorded successfully', 'success');
    movementModal.close();
    resetMovementForm();
    await inventoryStore.fetchStock();
  } catch (error) {
    notify(error.message || 'Failed to record movement', 'error');
  } finally {
    saving.value = false;
  }
}

function resetMovementForm() {
  movementForm.value = {
    movement_type: '',
    warehouse_id: null,
    product_id: null,
    quantity: 0,
    reference: '',
    notes: ''
  };
  movementErrors.value = {};
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

function isLowStock(stockItem) {
  return parseFloat(stockItem.available_quantity || 0) < parseFloat(stockItem.reorder_point || 0);
}

function getValuationVariant(method) {
  const variants = {
    fifo: 'primary',
    lifo: 'info',
    weighted_average: 'success',
    standard_cost: 'warning'
  };
  return variants[method] || 'secondary';
}

function formatValuationMethod(method) {
  const labels = {
    fifo: 'FIFO',
    lifo: 'LIFO',
    weighted_average: 'Weighted Average',
    standard_cost: 'Standard Cost'
  };
  return labels[method] || method;
}

function getMovementTypeVariant(type) {
  const variants = {
    receive: 'success',
    issue: 'danger',
    transfer: 'info',
    adjust: 'warning',
    reserve: 'primary',
    release: 'secondary'
  };
  return variants[type] || 'secondary';
}

function formatMovementType(type) {
  const labels = {
    receive: 'Receive',
    issue: 'Issue',
    transfer: 'Transfer',
    adjust: 'Adjust',
    reserve: 'Reserve',
    release: 'Release'
  };
  return labels[type] || type;
}

function formatNumber(value) {
  return new Intl.NumberFormat('en-US').format(value || 0);
}

function formatCurrency(amount) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount || 0);
}

function formatDate(date) {
  if (!date) return 'N/A';
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(new Date(date));
}
</script>
