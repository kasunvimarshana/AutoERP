<template>
  <div class="page-container">
    <div class="page-header">
      <h1 class="page-title">Products</h1>
      <button @click="showCreateModal = true" class="btn btn-primary">
        <PlusIcon class="icon" />
        Add Product
      </button>
    </div>
    
    <div class="filters-section">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Search products..."
        class="search-input"
      />
    </div>
    
    <DataTable
      :columns="columns"
      :data="products"
      :loading="loading"
      :current-page="currentPage"
      :total-pages="totalPages"
      @page-change="handlePageChange"
    >
      <template #cell-type="{ row }">
        <span :class="['badge', `badge-${getTypeColor(row.type)}`]">
          {{ row.type }}
        </span>
      </template>
      
      <template #cell-price="{ row }">
        ${{ row.price?.toFixed(2) || '0.00' }}
      </template>
      
      <template #cell-stock="{ row }">
        <span :class="['stock-level', getStockClass(row.stock_quantity)]">
          {{ row.stock_quantity || 0 }}
        </span>
      </template>
      
      <template #actions="{ row }">
        <div class="action-buttons">
          <button @click="viewProduct(row)" class="btn-icon" title="View">
            <EyeIcon class="icon" />
          </button>
          <button @click="editProduct(row)" class="btn-icon" title="Edit">
            <PencilIcon class="icon" />
          </button>
          <button @click="deleteProductConfirm(row)" class="btn-icon" title="Delete">
            <TrashIcon class="icon" />
          </button>
        </div>
      </template>
    </DataTable>
    
    <Modal
      :show="showCreateModal"
      title="Add Product"
      size="large"
      @close="showCreateModal = false"
      @confirm="handleCreateProduct"
    >
      <div class="form-grid">
        <FormInput v-model="form.name" label="Product Name" required />
        <FormInput v-model="form.sku" label="SKU" required />
        <FormSelect
          v-model="form.type"
          label="Type"
          :options="typeOptions"
          required
        />
        <FormInput v-model="form.price" label="Price" type="number" step="0.01" />
        <FormTextarea v-model="form.description" label="Description" rows="3" />
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { PlusIcon, PencilIcon, TrashIcon, EyeIcon } from '@heroicons/vue/24/outline'
import DataTable from '../../../components/common/DataTable.vue'
import Modal from '../../../components/common/Modal.vue'
import FormInput from '../../../components/forms/FormInput.vue'
import FormSelect from '../../../components/forms/FormSelect.vue'
import FormTextarea from '../../../components/forms/FormTextarea.vue'
import { useInventoryStore } from '../../../stores/inventoryStore'

const inventoryStore = useInventoryStore()

const showCreateModal = ref(false)
const searchQuery = ref('')
const currentPage = ref(1)
const totalPages = ref(1)
const form = ref({
  name: '',
  sku: '',
  type: '',
  price: 0,
  description: ''
})

const columns = [
  { key: 'id', label: 'ID', width: '80px' },
  { key: 'sku', label: 'SKU', sortable: true },
  { key: 'name', label: 'Name', sortable: true },
  { key: 'type', label: 'Type' },
  { key: 'price', label: 'Price', sortable: true },
  { key: 'stock', label: 'Stock' }
]

const products = computed(() => inventoryStore.products)
const loading = computed(() => inventoryStore.loading)

const typeOptions = [
  { value: 'inventory', label: 'Inventory' },
  { value: 'service', label: 'Service' },
  { value: 'combo', label: 'Combo' },
  { value: 'bundle', label: 'Bundle' }
]

onMounted(() => {
  fetchProducts()
})

const fetchProducts = async () => {
  try {
    await inventoryStore.fetchProducts({ page: currentPage.value })
  } catch (error) {
    console.error('Failed to fetch products:', error)
  }
}

const handlePageChange = (page) => {
  currentPage.value = page
  fetchProducts()
}

const handleCreateProduct = async () => {
  try {
    await inventoryStore.createProduct(form.value)
    showCreateModal.value = false
    form.value = { name: '', sku: '', type: '', price: 0, description: '' }
  } catch (error) {
    console.error('Failed to create product:', error)
  }
}

const viewProduct = (product) => {
  console.log('View product:', product)
}

const editProduct = (product) => {
  console.log('Edit product:', product)
}

const deleteProductConfirm = async (product) => {
  if (confirm(`Are you sure you want to delete ${product.name}?`)) {
    try {
      await inventoryStore.deleteProduct(product.id)
    } catch (error) {
      console.error('Failed to delete product:', error)
    }
  }
}

const getTypeColor = (type) => {
  const colors = {
    inventory: 'blue',
    service: 'green',
    combo: 'purple',
    bundle: 'orange'
  }
  return colors[type] || 'gray'
}

const getStockClass = (quantity) => {
  if (quantity === 0) return 'stock-out'
  if (quantity < 10) return 'stock-low'
  return 'stock-good'
}
</script>

<style scoped>
.page-container {
  max-width: 1400px;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: #1f2937;
}

.filters-section {
  margin-bottom: 20px;
}

.search-input {
  width: 100%;
  max-width: 400px;
  padding: 10px 16px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 14px;
}

.search-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover {
  background: #2563eb;
}

.icon {
  width: 20px;
  height: 20px;
}

.action-buttons {
  display: flex;
  gap: 8px;
}

.btn-icon {
  background: none;
  border: none;
  cursor: pointer;
  padding: 6px;
  color: #6b7280;
  transition: color 0.2s;
  border-radius: 4px;
}

.btn-icon:hover {
  color: #3b82f6;
  background: #eff6ff;
}

.badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

.badge-blue {
  background: #dbeafe;
  color: #1e40af;
}

.badge-green {
  background: #dcfce7;
  color: #166534;
}

.badge-purple {
  background: #f3e8ff;
  color: #6b21a8;
}

.badge-orange {
  background: #ffedd5;
  color: #9a3412;
}

.stock-level {
  font-weight: 600;
}

.stock-good {
  color: #16a34a;
}

.stock-low {
  color: #f59e0b;
}

.stock-out {
  color: #ef4444;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.form-grid > :last-child {
  grid-column: 1 / -1;
}
</style>
