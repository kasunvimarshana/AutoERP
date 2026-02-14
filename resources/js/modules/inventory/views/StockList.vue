<template>
  <div>
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Inventory</h1>
        <p class="mt-1 text-sm text-gray-600">
          Track and manage stock levels
        </p>
      </div>
      <div class="mt-4 sm:mt-0 flex space-x-3">
        <Button variant="outline" @click="$router.push({ name: 'inventory.adjustment' })">
          Stock Adjustment
        </Button>
      </div>
    </div>

    <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="h-12 w-12 rounded-md flex items-center justify-center bg-green-100 text-green-600 text-xl">
                📦
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">
                  Total Items
                </dt>
                <dd class="text-2xl font-semibold text-gray-900">
                  {{ stats.totalItems }}
                </dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="h-12 w-12 rounded-md flex items-center justify-center bg-yellow-100 text-yellow-600 text-xl">
                ⚠️
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">
                  Low Stock
                </dt>
                <dd class="text-2xl font-semibold text-gray-900">
                  {{ stats.lowStock }}
                </dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="h-12 w-12 rounded-md flex items-center justify-center bg-red-100 text-red-600 text-xl">
                ❌
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">
                  Out of Stock
                </dt>
                <dd class="text-2xl font-semibold text-gray-900">
                  {{ stats.outOfStock }}
                </dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="mb-4">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Search inventory..."
        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
        @input="handleSearch"
      />
    </div>

    <DataTable
      :columns="columns"
      :data="items"
      :loading="loading"
      :pagination="pagination"
      @page-change="handlePageChange"
      empty-text="No inventory items found"
      :has-actions="false"
    >
      <template #cell-product="{ row }">
        <div>
          <div class="font-medium text-gray-900">{{ row.product?.name }}</div>
          <div class="text-sm text-gray-500">{{ row.product?.sku }}</div>
        </div>
      </template>

      <template #cell-quantity="{ row }">
        <span
          class="inline-flex rounded-full px-3 py-1 text-sm font-semibold"
          :class="getStockClass(row.quantity, row.min_quantity)"
        >
          {{ row.quantity }}
        </span>
      </template>

      <template #cell-min_quantity="{ row }">
        <div class="text-gray-600">{{ row.min_quantity || 0 }}</div>
      </template>

      <template #cell-value="{ row }">
        <div class="text-gray-900">${{ calculateValue(row) }}</div>
      </template>

      <template #cell-location="{ row }">
        <div class="text-gray-600">{{ row.location || '-' }}</div>
      </template>

      <template #cell-branch="{ row }">
        <div class="text-gray-600">{{ row.branch?.name || 'Main' }}</div>
      </template>
    </DataTable>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useProductStore } from '../../../stores/product';
import DataTable from '../../../components/DataTable.vue';
import Button from '../../../components/Button.vue';

const router = useRouter();
const productStore = useProductStore();

const searchQuery = ref('');
const loading = ref(false);
const items = ref([]);
const pagination = ref(null);

const stats = computed(() => {
  const total = items.value.length;
  const low = items.value.filter(item => 
    item.quantity <= (item.min_quantity || 10) && item.quantity > 0
  ).length;
  const out = items.value.filter(item => item.quantity === 0).length;
  
  return {
    totalItems: total,
    lowStock: low,
    outOfStock: out,
  };
});

const columns = [
  { key: 'product', label: 'Product' },
  { key: 'quantity', label: 'Stock' },
  { key: 'min_quantity', label: 'Min Level' },
  { key: 'value', label: 'Value' },
  { key: 'location', label: 'Location' },
  { key: 'branch', label: 'Branch' },
];

onMounted(() => {
  loadInventory();
});

const loadInventory = async (page = 1) => {
  loading.value = true;
  try {
    const response = await productStore.fetchProducts({ page, with_stock: true });
    items.value = response.data.map(product => ({
      id: product.id,
      product: product,
      quantity: product.stock || 0,
      min_quantity: product.min_stock || 10,
      location: product.location || 'Warehouse A',
      branch: { name: 'Main Branch' },
    }));
    pagination.value = response.meta || response.pagination;
  } catch (error) {
    console.error('Failed to load inventory:', error);
  } finally {
    loading.value = false;
  }
};

const handlePageChange = (page) => {
  loadInventory(page);
};

const handleSearch = () => {
  if (searchQuery.value.length > 2) {
    // Implement search
  } else if (searchQuery.value.length === 0) {
    loadInventory();
  }
};

const getStockClass = (quantity, minQuantity) => {
  if (quantity === 0) return 'bg-red-100 text-red-800';
  if (quantity <= (minQuantity || 10)) return 'bg-yellow-100 text-yellow-800';
  return 'bg-green-100 text-green-800';
};

const calculateValue = (row) => {
  const price = row.product?.price || 0;
  const quantity = row.quantity || 0;
  return (price * quantity).toFixed(2);
};
</script>
