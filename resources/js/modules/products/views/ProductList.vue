<template>
  <div>
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Products</h1>
        <p class="mt-1 text-sm text-gray-600">
          Manage your product catalog
        </p>
      </div>
      <div class="mt-4 sm:mt-0">
        <Button @click="$router.push({ name: 'products.create' })">
          Add Product
        </Button>
      </div>
    </div>

    <div class="mb-4">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Search products..."
        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
        @input="handleSearch"
      />
    </div>

    <DataTable
      :columns="columns"
      :data="productStore.products"
      :loading="productStore.loading"
      :pagination="productStore.pagination"
      @page-change="handlePageChange"
      empty-text="No products found"
    >
      <template #cell-name="{ row }">
        <div class="font-medium text-gray-900">{{ row.name }}</div>
      </template>

      <template #cell-sku="{ row }">
        <div class="text-gray-600 font-mono text-sm">{{ row.sku || '-' }}</div>
      </template>

      <template #cell-price="{ row }">
        <div class="text-gray-900">${{ formatPrice(row.price) }}</div>
      </template>

      <template #cell-stock="{ row }">
        <span
          class="inline-flex rounded-full px-2 text-xs font-semibold leading-5"
          :class="getStockClass(row.stock)"
        >
          {{ row.stock || 0 }}
        </span>
      </template>

      <template #cell-status="{ row }">
        <span
          class="inline-flex rounded-full px-2 text-xs font-semibold leading-5"
          :class="row.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
        >
          {{ row.status || 'active' }}
        </span>
      </template>

      <template #actions="{ row }">
        <div class="flex space-x-2">
          <button
            @click="handleEdit(row.id)"
            class="text-blue-600 hover:text-blue-900"
          >
            Edit
          </button>
          <button
            @click="handleDelete(row.id)"
            class="text-red-600 hover:text-red-900"
          >
            Delete
          </button>
        </div>
      </template>
    </DataTable>

    <Modal v-model="showDeleteModal" title="Confirm Delete" size="sm">
      <p class="text-sm text-gray-500">
        Are you sure you want to delete this product? This action cannot be undone.
      </p>
      <template #footer>
        <Button variant="outline" @click="showDeleteModal = false">
          Cancel
        </Button>
        <Button variant="danger" @click="confirmDelete" :loading="deleting">
          Delete
        </Button>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useProductStore } from '../../../stores/product';
import DataTable from '../../../components/DataTable.vue';
import Button from '../../../components/Button.vue';
import Modal from '../../../components/Modal.vue';

const router = useRouter();
const productStore = useProductStore();

const searchQuery = ref('');
const showDeleteModal = ref(false);
const productToDelete = ref(null);
const deleting = ref(false);

const columns = [
  { key: 'name', label: 'Name' },
  { key: 'sku', label: 'SKU' },
  { key: 'price', label: 'Price' },
  { key: 'stock', label: 'Stock' },
  { key: 'status', label: 'Status' },
];

onMounted(() => {
  loadProducts();
});

const loadProducts = async (page = 1) => {
  try {
    await productStore.fetchProducts({ page });
  } catch (error) {
    console.error('Failed to load products:', error);
  }
};

const handlePageChange = (page) => {
  loadProducts(page);
};

const handleSearch = () => {
  if (searchQuery.value.length > 2) {
    productStore.searchProducts(searchQuery.value);
  } else if (searchQuery.value.length === 0) {
    loadProducts();
  }
};

const handleEdit = (id) => {
  router.push({ name: 'products.edit', params: { id } });
};

const handleDelete = (id) => {
  productToDelete.value = id;
  showDeleteModal.value = true;
};

const confirmDelete = async () => {
  deleting.value = true;
  try {
    await productStore.deleteProduct(productToDelete.value);
    showDeleteModal.value = false;
    productToDelete.value = null;
  } catch (error) {
    console.error('Failed to delete product:', error);
  } finally {
    deleting.value = false;
  }
};

const formatPrice = (price) => {
  return parseFloat(price || 0).toFixed(2);
};

const getStockClass = (stock) => {
  const qty = parseInt(stock || 0);
  if (qty === 0) return 'bg-red-100 text-red-800';
  if (qty < 10) return 'bg-yellow-100 text-yellow-800';
  return 'bg-green-100 text-green-800';
};
</script>
