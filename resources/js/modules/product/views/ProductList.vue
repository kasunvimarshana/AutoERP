<template>
  <div>
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Products</h1>
        <p class="mt-1 text-sm text-gray-500">Manage your product catalog</p>
      </div>
      <BaseButton variant="primary" @click="openCreateModal">
        Add Product
      </BaseButton>
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
          v-model="statusFilter"
          :options="statusOptions"
          placeholder="Filter by status"
        />
        <BaseSelect
          v-model="categoryFilter"
          :options="categoryOptions"
          placeholder="Filter by category"
        />
      </div>
    </BaseCard>

    <!-- Products Table -->
    <BaseCard>
      <BaseTable
        :columns="columns"
        :data="filteredProducts"
        :loading="loading"
        :actions="tableActions"
        @sort="handleSort"
        @action:view="viewProduct"
        @action:edit="editProduct"
        @action:delete="deleteProduct"
      >
        <template #cell-status="{ value }">
          <BaseBadge :variant="getStatusVariant(value)">
            {{ value }}
          </BaseBadge>
        </template>
        
        <template #cell-price="{ value }">
          ${{ Number(value).toFixed(2) }}
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
            v-model="form.sku"
            label="SKU"
            required
            placeholder="Enter product SKU"
            :error="errors.sku"
          />
          
          <BaseInput
            v-model="form.name"
            label="Product Name"
            required
            placeholder="Enter product name"
            :error="errors.name"
          />
          
          <BaseTextarea
            v-model="form.description"
            label="Description"
            placeholder="Enter product description"
            :rows="4"
          />
          
          <div class="grid grid-cols-2 gap-4">
            <BaseInput
              v-model.number="form.price"
              label="Price"
              type="number"
              step="0.01"
              required
              placeholder="0.00"
              :error="errors.price"
            />
            
            <BaseSelect
              v-model="form.status"
              label="Status"
              required
              :options="statusOptions"
              :error="errors.status"
            />
          </div>
          
          <BaseSelect
            v-model="form.category_id"
            label="Category"
            :options="categoryOptions"
            placeholder="Select category"
          />
        </div>
      </form>
      
      <template #footer>
        <BaseButton variant="ghost" @click="modal.close">
          Cancel
        </BaseButton>
        <BaseButton type="submit" :loading="submitting" @click="handleSubmit">
          {{ form.id ? 'Update' : 'Create' }} Product
        </BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import BaseButton from '@/components/common/BaseButton.vue';
import BaseInput from '@/components/common/BaseInput.vue';
import BaseSelect from '@/components/common/BaseSelect.vue';
import BaseTextarea from '@/components/common/BaseTextarea.vue';
import BaseBadge from '@/components/common/BaseBadge.vue';
import BaseCard from '@/components/layout/BaseCard.vue';
import BaseModal from '@/components/layout/BaseModal.vue';
import BaseTable from '@/components/data/BaseTable.vue';
import BasePagination from '@/components/data/BasePagination.vue';
import { useModal } from '@/composables/useModal';
import { usePagination } from '@/composables/usePagination';
import { useNotifications } from '@/composables/useNotifications';
import { productService } from '../services/productService';

const router = useRouter();
const modal = useModal();
const pagination = usePagination({ perPage: 15 });
const { showSuccess, showError } = useNotifications();

const products = ref([]);
const loading = ref(false);
const submitting = ref(false);
const search = ref('');
const statusFilter = ref('');
const categoryFilter = ref('');
const sortBy = ref('');
const sortOrder = ref('asc');

const form = ref({
  id: null,
  sku: '',
  name: '',
  description: '',
  price: 0,
  status: 'active',
  category_id: null,
});

const errors = ref({});

const columns = [
  { key: 'sku', label: 'SKU', sortable: true },
  { key: 'name', label: 'Name', sortable: true },
  { key: 'category.name', label: 'Category', sortable: false },
  { key: 'price', label: 'Price', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
];

const tableActions = [
  { name: 'view', label: 'View', class: 'text-blue-600 hover:text-blue-900' },
  { name: 'edit', label: 'Edit', class: 'text-indigo-600 hover:text-indigo-900' },
  { name: 'delete', label: 'Delete', class: 'text-red-600 hover:text-red-900' },
];

const statusOptions = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'draft', label: 'Draft' },
];

const categoryOptions = ref([]);

const modalTitle = computed(() => {
  return form.value.id ? 'Edit Product' : 'Create Product';
});

const filteredProducts = computed(() => {
  let result = [...products.value];
  
  // Apply search
  if (search.value) {
    const searchLower = search.value.toLowerCase();
    result = result.filter(p => 
      p.sku.toLowerCase().includes(searchLower) ||
      p.name.toLowerCase().includes(searchLower)
    );
  }
  
  // Apply status filter
  if (statusFilter.value) {
    result = result.filter(p => p.status === statusFilter.value);
  }
  
  // Apply category filter
  if (categoryFilter.value) {
    result = result.filter(p => p.category_id === categoryFilter.value);
  }
  
  // Apply sorting
  if (sortBy.value) {
    result.sort((a, b) => {
      const aVal = sortBy.value.split('.').reduce((obj, key) => obj?.[key], a);
      const bVal = sortBy.value.split('.').reduce((obj, key) => obj?.[key], b);
      
      if (sortOrder.value === 'asc') {
        return aVal > bVal ? 1 : -1;
      } else {
        return aVal < bVal ? 1 : -1;
      }
    });
  }
  
  pagination.setTotal(result.length);
  
  // Apply pagination
  const start = pagination.startIndex;
  const end = pagination.endIndex;
  return result.slice(start, end);
});

onMounted(async () => {
  await loadProducts();
});

async function loadProducts() {
  loading.value = true;
  try {
    const response = await productService.getAll();
    products.value = response.data.data || response.data || [];
    pagination.setTotal(products.value.length);
  } catch (error) {
    showError('Failed to load products');
    console.error('Failed to load products:', error);
  } finally {
    loading.value = false;
  }
}

function openCreateModal() {
  form.value = {
    id: null,
    sku: '',
    name: '',
    description: '',
    price: 0,
    status: 'active',
    category_id: null,
  };
  errors.value = {};
  modal.open();
}

function viewProduct(product) {
  router.push({ name: 'product-detail', params: { id: product.id } });
}

function editProduct(product) {
  form.value = { ...product };
  modal.open(product);
}

async function deleteProduct(product) {
  if (!confirm(`Are you sure you want to delete ${product.name}?`)) {
    return;
  }
  
  try {
    await productService.delete(product.id);
    showSuccess('Product deleted successfully');
    await loadProducts();
  } catch (error) {
    showError('Failed to delete product');
    console.error('Failed to delete product:', error);
  }
}

async function handleSubmit() {
  errors.value = {};
  submitting.value = true;
  
  try {
    if (form.value.id) {
      await productService.update(form.value.id, form.value);
      showSuccess('Product updated successfully');
    } else {
      await productService.create(form.value);
      showSuccess('Product created successfully');
    }
    
    modal.close();
    await loadProducts();
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {};
    }
    showError(form.value.id ? 'Failed to update product' : 'Failed to create product');
    console.error('Failed to save product:', error);
  } finally {
    submitting.value = false;
  }
}

function handleSort({ key, order }) {
  sortBy.value = key;
  sortOrder.value = order;
}

function handlePageChange(page) {
  pagination.goToPage(page);
}

function getStatusVariant(status) {
  const variants = {
    active: 'success',
    inactive: 'danger',
    draft: 'default',
  };
  return variants[status] || 'default';
}
</script>
