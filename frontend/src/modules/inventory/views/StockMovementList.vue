<template>
  <div class="page-container">
    <div class="page-header">
      <h1 class="page-title">Stock Movements</h1>
      <button @click="openCreateModal" class="btn btn-primary"><PlusIcon class="icon" />Create Movement</button>
    </div>
    
    <div class="filters-section">
      <input v-model="filters.search" type="text" placeholder="Search by movement number..." class="search-input" @input="fetchMovements" />
      <div class="filter-group">
        <select v-model="filters.type" @change="fetchMovements" class="filter-select">
          <option value="">All Types</option>
          <option value="adjustment">Adjustment</option>
          <option value="transfer">Transfer</option>
        </select>
        <select v-model="filters.status" @change="fetchMovements" class="filter-select">
          <option value="">All Status</option>
          <option value="draft">Draft</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
    </div>
    
    <DataTable :columns="columns" :data="movements" :loading="loading" :current-page="currentPage" :total-pages="totalPages" @page-change="handlePageChange">
      <template #cell-type="{ row }">
        <span :class="['badge', row.type === 'adjustment' ? 'badge-orange' : 'badge-purple']">{{ row.type }}</span>
      </template>
      <template #cell-status="{ row }">
        <span :class="['badge', `badge-${getStatusColor(row.status)}`]">{{ row.status }}</span>
      </template>
      <template #cell-date="{ row }">{{ formatDate(row.date) }}</template>
      <template #actions="{ row }">
        <div class="action-buttons">
          <button @click="viewMovement(row)" class="btn-icon" title="View"><EyeIcon class="icon" /></button>
          <button v-if="row.status === 'draft'" @click="editMovement(row)" class="btn-icon" title="Edit"><PencilIcon class="icon" /></button>
          <button v-if="row.status === 'draft'" @click="completeMovement(row)" class="btn-icon btn-success" title="Complete"><CheckIcon class="icon" /></button>
        </div>
      </template>
    </DataTable>
    
    <Modal :show="showModal" :title="isEditMode ? 'Edit Movement' : 'Create Movement'" size="large" @close="closeModal" @confirm="handleSaveMovement">
      <div class="form-section">
        <div class="form-grid">
          <FormSelect v-model="form.type" label="Movement Type" :options="typeOptions" required />
          <FormInput v-model="form.date" label="Date" type="date" required />
          <FormSelect v-model="form.from_warehouse_id" label="From Warehouse" :options="warehouseOptions" />
          <FormSelect v-model="form.to_warehouse_id" label="To Warehouse" :options="warehouseOptions" />
        </div>
        <div class="items-section">
          <div class="section-header"><h4>Items</h4><button @click="addItem" type="button" class="btn btn-sm btn-secondary"><PlusIcon class="icon-sm" />Add Item</button></div>
          <div v-for="(item, index) in form.items" :key="index" class="item-row">
            <FormSelect v-model="item.product_id" label="Product" :options="productOptions" required />
            <FormInput v-model.number="item.quantity" label="Quantity" type="number" min="1" required />
            <FormTextarea v-model="item.notes" label="Notes" rows="2" />
            <button @click="removeItem(index)" type="button" class="btn-icon btn-danger" title="Remove"><TrashIcon class="icon" /></button>
          </div>
        </div>
        <FormTextarea v-model="form.notes" label="Notes" rows="3" />
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { PlusIcon, PencilIcon, TrashIcon, EyeIcon, CheckIcon } from '@heroicons/vue/24/outline'
import DataTable from '../../../components/common/DataTable.vue'
import Modal from '../../../components/common/Modal.vue'
import FormInput from '../../../components/forms/FormInput.vue'
import FormSelect from '../../../components/forms/FormSelect.vue'
import FormTextarea from '../../../components/forms/FormTextarea.vue'

const loading = ref(false)
const showModal = ref(false)
const isEditMode = ref(false)
const movements = ref([])
const warehouses = ref([])
const products = ref([])
const currentPage = ref(1)
const totalPages = ref(1)
const filters = ref({ search: '', type: '', status: '' })
const form = ref({ type: 'adjustment', date: new Date().toISOString().split('T')[0], from_warehouse_id: '', to_warehouse_id: '', items: [], notes: '' })
const columns = [
  { key: 'movement_number', label: 'Movement #', sortable: true },
  { key: 'type', label: 'Type' },
  { key: 'from_warehouse_name', label: 'From' },
  { key: 'to_warehouse_name', label: 'To' },
  { key: 'quantity', label: 'Quantity' },
  { key: 'date', label: 'Date', sortable: true },
  { key: 'status', label: 'Status' }
]
const typeOptions = [{ value: 'adjustment', label: 'Adjustment' }, { value: 'transfer', label: 'Transfer' }]
const warehouseOptions = computed(() => warehouses.value.map(w => ({ value: w.id, label: w.name })))
const productOptions = computed(() => products.value.map(p => ({ value: p.id, label: `${p.name} (${p.sku})` })))

onMounted(() => { fetchMovements() })
const fetchMovements = async () => { loading.value = true; movements.value = []; loading.value = false }
const handlePageChange = (page) => { currentPage.value = page; fetchMovements() }
const openCreateModal = () => { isEditMode.value = false; resetForm(); showModal.value = true }
const closeModal = () => { showModal.value = false; resetForm() }
const resetForm = () => { form.value = { type: 'adjustment', date: new Date().toISOString().split('T')[0], from_warehouse_id: '', to_warehouse_id: '', items: [], notes: '' } }
const addItem = () => { form.value.items.push({ product_id: '', quantity: 1, notes: '' }) }
const removeItem = (index) => { form.value.items.splice(index, 1) }
const handleSaveMovement = async () => { closeModal(); await fetchMovements() }
const viewMovement = (movement) => {}
const editMovement = (movement) => {}
const completeMovement = async (movement) => { if (confirm(`Complete movement ${movement.movement_number}?`)) await fetchMovements() }
const getStatusColor = (status) => ({ draft: 'gray', completed: 'green', cancelled: 'red' }[status] || 'gray')
const formatDate = (date) => date ? new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : 'N/A'
</script>

<style scoped>
.page-container { max-width: 1400px; }
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
.page-title { font-size: 28px; font-weight: 700; color: #1f2937; }
.filters-section { margin-bottom: 20px; display: flex; gap: 12px; flex-wrap: wrap; }
.search-input { flex: 1; min-width: 300px; padding: 10px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; }
.filter-group { display: flex; gap: 12px; }
.filter-select { padding: 10px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background: white; }
.btn { display: flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.2s; border: none; }
.btn-primary { background: #3b82f6; color: white; }
.btn-primary:hover { background: #2563eb; }
.btn-secondary { background: #6b7280; color: white; }
.btn-sm { padding: 6px 12px; font-size: 13px; }
.icon { width: 20px; height: 20px; }
.icon-sm { width: 16px; height: 16px; }
.badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 500; text-transform: capitalize; }
.badge-orange { background: #ffedd5; color: #9a3412; }
.badge-purple { background: #f3e8ff; color: #6b21a8; }
.badge-green { background: #dcfce7; color: #166534; }
.badge-gray { background: #f3f4f6; color: #374151; }
.badge-red { background: #fee2e2; color: #991b1b; }
.action-buttons { display: flex; gap: 8px; }
.btn-icon { background: none; border: none; cursor: pointer; padding: 6px; color: #6b7280; transition: color 0.2s; border-radius: 4px; }
.btn-icon:hover { color: #3b82f6; background: #eff6ff; }
.btn-icon.btn-success:hover { color: #16a34a; background: #f0fdf4; }
.btn-icon.btn-danger:hover { color: #ef4444; background: #fef2f2; }
.form-section { display: flex; flex-direction: column; gap: 24px; }
.form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
.items-section { border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; }
.section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.section-header h4 { font-size: 16px; font-weight: 600; color: #1f2937; margin: 0; }
.item-row { display: grid; grid-template-columns: 2fr 1fr 2fr auto; gap: 12px; align-items: end; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid #f3f4f6; }
.item-row:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
</style>
