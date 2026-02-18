<template>
  <div class="page-container">
    <div class="page-header">
      <h1 class="page-title">Warehouses</h1>
      <button @click="openCreateModal" class="btn btn-primary"><PlusIcon class="icon" />Add Warehouse</button>
    </div>
    
    <div class="filters-section">
      <input v-model="filters.search" type="text" placeholder="Search by name, code, or location..." class="search-input" @input="fetchWarehouses" />
      <select v-model="filters.type" @change="fetchWarehouses" class="filter-select">
        <option value="">All Types</option>
        <option value="main">Main</option>
        <option value="branch">Branch</option>
        <option value="retail">Retail</option>
        <option value="transit">Transit</option>
      </select>
    </div>
    
    <DataTable :columns="columns" :data="warehouses" :loading="loading" :current-page="currentPage" :total-pages="totalPages" @page-change="handlePageChange">
      <template #cell-type="{ row }">
        <span class="badge badge-blue">{{ row.type }}</span>
      </template>
      <template #cell-capacity="{ row }">
        {{ row.capacity || 'N/A' }}
      </template>
      <template #actions="{ row }">
        <div class="action-buttons">
          <button @click="viewWarehouse(row)" class="btn-icon" title="View"><EyeIcon class="icon" /></button>
          <button @click="editWarehouse(row)" class="btn-icon" title="Edit"><PencilIcon class="icon" /></button>
          <button @click="viewStockLevels(row)" class="btn-icon" title="Stock Levels"><ChartBarIcon class="icon" /></button>
          <button @click="deleteWarehouse(row)" class="btn-icon btn-danger" title="Delete"><TrashIcon class="icon" /></button>
        </div>
      </template>
    </DataTable>
    
    <Modal :show="showModal" :title="isEditMode ? 'Edit Warehouse' : 'Create Warehouse'" size="large" @close="closeModal" @confirm="handleSaveWarehouse">
      <div class="form-section">
        <div class="form-grid">
          <FormInput v-model="form.code" label="Warehouse Code" placeholder="WH-001" required />
          <FormInput v-model="form.name" label="Warehouse Name" placeholder="Main Warehouse" required />
          <FormInput v-model="form.location" label="Location" placeholder="City, State" required />
          <FormSelect v-model="form.type" label="Type" :options="typeOptions" required />
          <FormInput v-model.number="form.capacity" label="Capacity" type="number" placeholder="Square feet or units" />
          <FormInput v-model="form.manager" label="Manager" placeholder="Manager name" />
        </div>
        <FormTextarea v-model="form.address" label="Full Address" rows="3" placeholder="Complete warehouse address" />
        <FormTextarea v-model="form.notes" label="Notes" rows="3" />
      </div>
    </Modal>
    
    <Modal :show="showViewModal" title="Warehouse Details" size="large" hide-footer @close="showViewModal = false">
      <div v-if="selectedWarehouse" class="view-details">
        <div class="detail-grid">
          <div class="detail-item"><span class="detail-label">Code:</span><span class="detail-value">{{ selectedWarehouse.code }}</span></div>
          <div class="detail-item"><span class="detail-label">Name:</span><span class="detail-value">{{ selectedWarehouse.name }}</span></div>
          <div class="detail-item"><span class="detail-label">Location:</span><span class="detail-value">{{ selectedWarehouse.location }}</span></div>
          <div class="detail-item"><span class="detail-label">Type:</span><span class="badge badge-blue">{{ selectedWarehouse.type }}</span></div>
          <div class="detail-item"><span class="detail-label">Capacity:</span><span class="detail-value">{{ selectedWarehouse.capacity || 'N/A' }}</span></div>
          <div class="detail-item"><span class="detail-label">Manager:</span><span class="detail-value">{{ selectedWarehouse.manager || 'N/A' }}</span></div>
        </div>
        <div v-if="selectedWarehouse.address" class="detail-section"><h4>Address</h4><p>{{ selectedWarehouse.address }}</p></div>
        <div v-if="selectedWarehouse.notes" class="detail-section"><h4>Notes</h4><p>{{ selectedWarehouse.notes }}</p></div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { PlusIcon, PencilIcon, TrashIcon, EyeIcon, ChartBarIcon } from '@heroicons/vue/24/outline'
import DataTable from '../../../components/common/DataTable.vue'
import Modal from '../../../components/common/Modal.vue'
import FormInput from '../../../components/forms/FormInput.vue'
import FormSelect from '../../../components/forms/FormSelect.vue'
import FormTextarea from '../../../components/forms/FormTextarea.vue'

const router = useRouter()
const loading = ref(false)
const showModal = ref(false)
const showViewModal = ref(false)
const isEditMode = ref(false)
const warehouses = ref([])
const selectedWarehouse = ref(null)
const currentPage = ref(1)
const totalPages = ref(1)
const filters = ref({ search: '', type: '' })
const form = ref({ code: '', name: '', location: '', type: 'main', capacity: 0, manager: '', address: '', notes: '' })
const columns = [
  { key: 'code', label: 'Code', sortable: true },
  { key: 'name', label: 'Name', sortable: true },
  { key: 'location', label: 'Location' },
  { key: 'type', label: 'Type' },
  { key: 'capacity', label: 'Capacity' },
  { key: 'manager', label: 'Manager' }
]
const typeOptions = [
  { value: 'main', label: 'Main' },
  { value: 'branch', label: 'Branch' },
  { value: 'retail', label: 'Retail' },
  { value: 'transit', label: 'Transit' }
]

onMounted(() => { fetchWarehouses() })
const fetchWarehouses = async () => { loading.value = true; warehouses.value = []; loading.value = false }
const handlePageChange = (page) => { currentPage.value = page; fetchWarehouses() }
const openCreateModal = () => { isEditMode.value = false; resetForm(); showModal.value = true }
const closeModal = () => { showModal.value = false; resetForm() }
const resetForm = () => { form.value = { code: '', name: '', location: '', type: 'main', capacity: 0, manager: '', address: '', notes: '' } }
const handleSaveWarehouse = async () => { closeModal(); await fetchWarehouses() }
const viewWarehouse = (warehouse) => { selectedWarehouse.value = warehouse; showViewModal.value = true }
const editWarehouse = (warehouse) => { form.value = { ...warehouse }; isEditMode.value = true; showModal.value = true }
const viewStockLevels = (warehouse) => { router.push({ name: 'StockLedgerList', query: { warehouse_id: warehouse.id } }) }
const deleteWarehouse = async (warehouse) => { if (confirm(`Delete warehouse ${warehouse.name}?`)) await fetchWarehouses() }
</script>

<style scoped>
.page-container { max-width: 1400px; }
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
.page-title { font-size: 28px; font-weight: 700; color: #1f2937; }
.filters-section { margin-bottom: 20px; display: flex; gap: 12px; flex-wrap: wrap; }
.search-input { flex: 1; min-width: 300px; padding: 10px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; }
.filter-select { padding: 10px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background: white; }
.btn { display: flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.2s; border: none; }
.btn-primary { background: #3b82f6; color: white; }
.btn-primary:hover { background: #2563eb; }
.icon { width: 20px; height: 20px; }
.badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 500; text-transform: capitalize; }
.badge-blue { background: #dbeafe; color: #1e40af; }
.action-buttons { display: flex; gap: 8px; }
.btn-icon { background: none; border: none; cursor: pointer; padding: 6px; color: #6b7280; transition: color 0.2s; border-radius: 4px; }
.btn-icon:hover { color: #3b82f6; background: #eff6ff; }
.btn-icon.btn-danger:hover { color: #ef4444; background: #fef2f2; }
.form-section { display: flex; flex-direction: column; gap: 20px; }
.form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
.view-details { display: flex; flex-direction: column; gap: 24px; }
.detail-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
.detail-item { display: flex; flex-direction: column; gap: 4px; }
.detail-label { font-size: 13px; font-weight: 500; color: #6b7280; }
.detail-value { font-size: 14px; color: #1f2937; }
.detail-section h4 { font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 12px; }
.detail-section p { color: #4b5563; line-height: 1.6; margin: 0; }
</style>
