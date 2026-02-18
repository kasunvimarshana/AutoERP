<template>
  <div class="page-container">
    <div class="page-header">
      <h1 class="page-title">Work Orders</h1>
      <button @click="openCreateModal" class="btn btn-primary">
        <PlusIcon class="icon" />
        Create Work Order
      </button>
    </div>
    
    <div class="filters-section">
      <input
        v-model="filters.search"
        type="text"
        placeholder="Search by work order number or operation..."
        class="search-input"
        @input="fetchWorkOrders"
      />
      <div class="filter-group">
        <select v-model="filters.status" @change="fetchWorkOrders" class="filter-select">
          <option value="">All Status</option>
          <option value="pending">Pending</option>
          <option value="in_progress">In Progress</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
        </select>
        <select v-model="filters.production_order_id" @change="fetchWorkOrders" class="filter-select">
          <option value="">All Production Orders</option>
          <option v-for="po in productionOrders" :key="po.id" :value="po.id">
            {{ po.order_number }}
          </option>
        </select>
        <select v-model="filters.assigned_to" @change="fetchWorkOrders" class="filter-select">
          <option value="">All Operators</option>
          <option v-for="user in users" :key="user.id" :value="user.id">
            {{ user.name }}
          </option>
        </select>
      </div>
    </div>
    
    <DataTable
      :columns="columns"
      :data="workOrders"
      :loading="loading"
      :current-page="currentPage"
      :total-pages="totalPages"
      @page-change="handlePageChange"
    >
      <template #cell-work_order_number="{ row }">
        <span class="font-semibold text-blue-600">{{ row.work_order_number }}</span>
      </template>
      
      <template #cell-scheduled_date="{ row }">
        {{ formatDate(row.scheduled_date) }}
      </template>
      
      <template #cell-duration="{ row }">
        <div class="duration-cell">
          <span class="duration-planned">{{ formatDuration(row.estimated_duration) }}</span>
          <span v-if="row.actual_duration" class="duration-actual">
            / {{ formatDuration(row.actual_duration) }}
          </span>
        </div>
      </template>
      
      <template #cell-efficiency="{ row }">
        <div v-if="row.actual_duration && row.estimated_duration" class="efficiency-cell">
          <span :class="['efficiency-badge', getEfficiencyClass(row)]">
            {{ calculateEfficiency(row) }}%
          </span>
        </div>
        <span v-else class="text-muted">-</span>
      </template>
      
      <template #cell-status="{ row }">
        <span :class="['badge', `badge-${getStatusColor(row.status)}`]">
          {{ formatStatus(row.status) }}
        </span>
      </template>
      
      <template #actions="{ row }">
        <div class="action-buttons">
          <button @click="viewWorkOrder(row)" class="btn-icon" title="View">
            <EyeIcon class="icon" />
          </button>
          <button 
            v-if="row.status === 'pending'"
            @click="editWorkOrder(row)" 
            class="btn-icon" 
            title="Edit"
          >
            <PencilIcon class="icon" />
          </button>
          <button 
            v-if="row.status === 'pending'"
            @click="startWorkOrder(row)" 
            class="btn-icon btn-success" 
            title="Start"
          >
            <PlayIcon class="icon" />
          </button>
          <button 
            v-if="row.status === 'in_progress'"
            @click="pauseWorkOrder(row)" 
            class="btn-icon btn-warning" 
            title="Pause"
          >
            <PauseIcon class="icon" />
          </button>
          <button 
            v-if="row.status === 'in_progress'"
            @click="completeWorkOrder(row)" 
            class="btn-icon btn-success" 
            title="Complete"
          >
            <CheckCircleIcon class="icon" />
          </button>
          <button 
            v-if="['pending', 'in_progress'].includes(row.status)"
            @click="cancelWorkOrder(row)" 
            class="btn-icon btn-danger" 
            title="Cancel"
          >
            <XCircleIcon class="icon" />
          </button>
        </div>
      </template>
    </DataTable>
    
    <Modal
      :show="showModal"
      :title="isEditMode ? 'Edit Work Order' : 'Create Work Order'"
      size="large"
      @close="closeModal"
      @confirm="handleSaveWorkOrder"
    >
      <div class="form-section">
        <div class="form-grid">
          <FormSelect
            v-model="form.production_order_id"
            label="Production Order"
            :options="productionOrderOptions"
            required
          />
          <FormInput
            v-model="form.operation_name"
            label="Operation Name"
            placeholder="e.g., Assembly, Welding, Painting"
            required
          />
          <FormInput
            v-model="form.workstation"
            label="Workstation"
            placeholder="Machine or work area"
          />
          <FormSelect
            v-model="form.assigned_to"
            label="Assigned To"
            :options="userOptions"
          />
          <FormInput
            v-model="form.scheduled_date"
            label="Scheduled Date"
            type="date"
            required
          />
          <FormInput
            v-model.number="form.setup_time"
            label="Setup Time (minutes)"
            type="number"
            min="0"
          />
          <FormInput
            v-model.number="form.run_time"
            label="Run Time (minutes)"
            type="number"
            min="0"
            required
          />
          <FormSelect
            v-model="form.status"
            label="Status"
            :options="statusOptions"
            required
          />
        </div>
        
        <div class="time-tracking-section" v-if="form.status !== 'pending'">
          <div class="section-header">
            <h4>Time Tracking</h4>
          </div>
          <div class="form-grid">
            <FormInput
              v-model="form.actual_start_time"
              label="Actual Start Time"
              type="datetime-local"
            />
            <FormInput
              v-model="form.actual_end_time"
              label="Actual End Time"
              type="datetime-local"
            />
            <div class="form-group">
              <label class="form-label">Actual Duration</label>
              <div class="read-only-field">{{ calculateActualDuration() }} minutes</div>
            </div>
            <div class="form-group">
              <label class="form-label">Efficiency</label>
              <div class="read-only-field">{{ calculateFormEfficiency() }}%</div>
            </div>
          </div>
        </div>
        
        <div class="quality-section">
          <div class="section-header">
            <h4>Quality Checkpoints</h4>
            <button @click="addCheckpoint" type="button" class="btn btn-sm btn-secondary">
              <PlusIcon class="icon-sm" />
              Add Checkpoint
            </button>
          </div>
          
          <div v-for="(checkpoint, index) in form.quality_checkpoints" :key="index" class="checkpoint-row">
            <FormInput
              v-model="checkpoint.name"
              label="Checkpoint Name"
              required
            />
            <FormSelect
              v-model="checkpoint.status"
              label="Status"
              :options="checkpointStatusOptions"
            />
            <FormTextarea
              v-model="checkpoint.notes"
              label="Notes"
              rows="2"
            />
            <button @click="removeCheckpoint(index)" type="button" class="btn-icon btn-danger" title="Remove">
              <TrashIcon class="icon" />
            </button>
          </div>
        </div>
        
        <div class="materials-section">
          <div class="section-header">
            <h4>Material Consumption</h4>
            <button @click="addMaterial" type="button" class="btn btn-sm btn-secondary">
              <PlusIcon class="icon-sm" />
              Add Material
            </button>
          </div>
          
          <div v-for="(material, index) in form.materials_consumed" :key="index" class="material-row">
            <FormSelect
              v-model="material.product_id"
              label="Material"
              :options="productOptions"
              required
            />
            <FormInput
              v-model.number="material.quantity"
              label="Quantity Consumed"
              type="number"
              min="0"
              step="0.01"
              required
            />
            <FormInput
              v-model="material.unit"
              label="Unit"
              placeholder="pcs, kg, L, etc."
              required
            />
            <FormInput
              v-model="material.batch_number"
              label="Batch/Lot Number"
            />
            <button @click="removeMaterial(index)" type="button" class="btn-icon btn-danger" title="Remove">
              <TrashIcon class="icon" />
            </button>
          </div>
        </div>
        
        <FormTextarea
          v-model="form.instructions"
          label="Instructions"
          rows="3"
          placeholder="Detailed work instructions and procedures"
        />
        
        <FormTextarea
          v-model="form.notes"
          label="Notes"
          rows="3"
          placeholder="Additional notes and observations"
        />
      </div>
    </Modal>
    
    <Modal
      :show="showViewModal"
      title="Work Order Details"
      size="large"
      hide-footer
      @close="showViewModal = false"
    >
      <div v-if="selectedWorkOrder" class="view-details">
        <div class="detail-header">
          <div>
            <h3>{{ selectedWorkOrder.work_order_number }}</h3>
            <p class="detail-subtitle">
              Operation: {{ selectedWorkOrder.operation_name }} | 
              Production Order: {{ selectedWorkOrder.production_order_number }}
            </p>
          </div>
          <span :class="['badge badge-lg', `badge-${getStatusColor(selectedWorkOrder.status)}`]">
            {{ formatStatus(selectedWorkOrder.status) }}
          </span>
        </div>
        
        <div class="detail-grid">
          <div class="detail-item">
            <span class="detail-label">Workstation:</span>
            <span class="detail-value">{{ selectedWorkOrder.workstation || 'N/A' }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Assigned To:</span>
            <span class="detail-value">{{ selectedWorkOrder.assigned_to_name || 'Unassigned' }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Scheduled Date:</span>
            <span class="detail-value">{{ formatDate(selectedWorkOrder.scheduled_date) }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Product:</span>
            <span class="detail-value">{{ selectedWorkOrder.product_name || 'N/A' }}</span>
          </div>
        </div>
        
        <div class="time-metrics">
          <h4>Time Metrics</h4>
          <div class="metrics-grid">
            <div class="metric-card">
              <span class="metric-label">Setup Time</span>
              <span class="metric-value">{{ formatDuration(selectedWorkOrder.setup_time) }}</span>
            </div>
            <div class="metric-card">
              <span class="metric-label">Run Time (Estimated)</span>
              <span class="metric-value">{{ formatDuration(selectedWorkOrder.run_time) }}</span>
            </div>
            <div class="metric-card">
              <span class="metric-label">Total Estimated</span>
              <span class="metric-value">{{ formatDuration(selectedWorkOrder.estimated_duration) }}</span>
            </div>
            <div class="metric-card">
              <span class="metric-label">Actual Duration</span>
              <span class="metric-value">{{ formatDuration(selectedWorkOrder.actual_duration) }}</span>
            </div>
          </div>
          
          <div v-if="selectedWorkOrder.actual_duration && selectedWorkOrder.estimated_duration" class="efficiency-display">
            <label class="detail-label">Efficiency</label>
            <div class="efficiency-bar">
              <div 
                class="efficiency-fill" 
                :class="getEfficiencyClass(selectedWorkOrder)"
                :style="{ width: `${Math.min(calculateEfficiency(selectedWorkOrder), 100)}%` }"
              >
                <span class="efficiency-text">{{ calculateEfficiency(selectedWorkOrder) }}%</span>
              </div>
            </div>
          </div>
          
          <div class="time-details">
            <div class="time-item">
              <span class="detail-label">Actual Start:</span>
              <span class="detail-value">{{ formatDateTime(selectedWorkOrder.actual_start_time) }}</span>
            </div>
            <div class="time-item">
              <span class="detail-label">Actual End:</span>
              <span class="detail-value">{{ formatDateTime(selectedWorkOrder.actual_end_time) }}</span>
            </div>
          </div>
        </div>
        
        <div v-if="selectedWorkOrder.quality_checkpoints && selectedWorkOrder.quality_checkpoints.length > 0" class="quality-detail">
          <h4>Quality Checkpoints</h4>
          <div class="checkpoint-list">
            <div v-for="checkpoint in selectedWorkOrder.quality_checkpoints" :key="checkpoint.id" class="checkpoint-card">
              <div class="checkpoint-header">
                <span class="checkpoint-name">{{ checkpoint.name }}</span>
                <span :class="['badge', getCheckpointStatusClass(checkpoint.status)]">
                  {{ checkpoint.status || 'Pending' }}
                </span>
              </div>
              <p v-if="checkpoint.notes" class="checkpoint-notes">{{ checkpoint.notes }}</p>
            </div>
          </div>
        </div>
        
        <div v-if="selectedWorkOrder.materials_consumed && selectedWorkOrder.materials_consumed.length > 0" class="materials-detail">
          <h4>Material Consumption</h4>
          <table class="detail-table">
            <thead>
              <tr>
                <th>Material</th>
                <th>Quantity</th>
                <th>Unit</th>
                <th>Batch/Lot Number</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="material in selectedWorkOrder.materials_consumed" :key="material.id">
                <td>{{ material.product_name }}</td>
                <td>{{ material.quantity }}</td>
                <td>{{ material.unit }}</td>
                <td>{{ material.batch_number || 'N/A' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <div v-if="selectedWorkOrder.instructions" class="detail-section">
          <h4>Instructions</h4>
          <p class="instruction-text">{{ selectedWorkOrder.instructions }}</p>
        </div>
        
        <div v-if="selectedWorkOrder.notes" class="detail-section">
          <h4>Notes</h4>
          <p>{{ selectedWorkOrder.notes }}</p>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { 
  PlusIcon, PencilIcon, TrashIcon, EyeIcon,
  PlayIcon, PauseIcon, CheckCircleIcon, XCircleIcon
} from '@heroicons/vue/24/outline'
import DataTable from '../../../components/common/DataTable.vue'
import Modal from '../../../components/common/Modal.vue'
import FormInput from '../../../components/forms/FormInput.vue'
import FormSelect from '../../../components/forms/FormSelect.vue'
import FormTextarea from '../../../components/forms/FormTextarea.vue'
import manufacturingService from '../../../services/manufacturingService'
import inventoryService from '../../../services/inventoryService'
import iamService from '../../../services/iamService'

const loading = ref(false)
const showModal = ref(false)
const showViewModal = ref(false)
const isEditMode = ref(false)
const workOrders = ref([])
const selectedWorkOrder = ref(null)
const currentPage = ref(1)
const totalPages = ref(1)
const productionOrders = ref([])
const products = ref([])
const users = ref([])

const filters = ref({
  search: '',
  status: '',
  production_order_id: '',
  assigned_to: ''
})

const form = ref({
  production_order_id: '',
  operation_name: '',
  workstation: '',
  assigned_to: '',
  scheduled_date: new Date().toISOString().split('T')[0],
  setup_time: 0,
  run_time: 60,
  status: 'pending',
  actual_start_time: '',
  actual_end_time: '',
  quality_checkpoints: [],
  materials_consumed: [],
  instructions: '',
  notes: ''
})

const columns = [
  { key: 'work_order_number', label: 'WO Number', sortable: true },
  { key: 'production_order_number', label: 'PO Number', sortable: true },
  { key: 'operation_name', label: 'Operation', sortable: true },
  { key: 'assigned_to_name', label: 'Assigned To', sortable: true },
  { key: 'scheduled_date', label: 'Scheduled Date', sortable: true },
  { key: 'duration', label: 'Duration (Est/Act)' },
  { key: 'efficiency', label: 'Efficiency' },
  { key: 'status', label: 'Status' }
]

const statusOptions = [
  { value: 'pending', label: 'Pending' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'completed', label: 'Completed' },
  { value: 'cancelled', label: 'Cancelled' }
]

const checkpointStatusOptions = [
  { value: 'pending', label: 'Pending' },
  { value: 'passed', label: 'Passed' },
  { value: 'failed', label: 'Failed' }
]

const productionOrderOptions = computed(() => 
  productionOrders.value
    .filter(po => ['planned', 'in_progress'].includes(po.status))
    .map(po => ({ value: po.id, label: `${po.order_number} - ${po.product_name}` }))
)

const userOptions = computed(() => 
  users.value.map(u => ({ value: u.id, label: u.name }))
)

const productOptions = computed(() => 
  products.value.map(p => ({ value: p.id, label: `${p.name} (${p.sku || 'N/A'})` }))
)

onMounted(async () => {
  await Promise.all([
    fetchWorkOrders(),
    fetchProductionOrders(),
    fetchProducts(),
    fetchUsers()
  ])
})

const fetchWorkOrders = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      search: filters.value.search,
      status: filters.value.status,
      production_order_id: filters.value.production_order_id,
      assigned_to: filters.value.assigned_to
    }
    const response = await manufacturingService.getWorkOrders(params)
    workOrders.value = response.data.data || []
    totalPages.value = response.data.meta?.last_page || 1
  } catch (error) {
    console.error('Failed to fetch work orders:', error)
    workOrders.value = []
  } finally {
    loading.value = false
  }
}

const fetchProductionOrders = async () => {
  try {
    const response = await manufacturingService.getProductionOrders()
    productionOrders.value = response.data.data || []
  } catch (error) {
    console.error('Failed to fetch production orders:', error)
  }
}

const fetchProducts = async () => {
  try {
    const response = await inventoryService.getProducts()
    products.value = response.data.data || []
  } catch (error) {
    console.error('Failed to fetch products:', error)
  }
}

const fetchUsers = async () => {
  try {
    const response = await iamService.getUsers({ status: 'active' })
    users.value = response.data.data || []
  } catch (error) {
    console.error('Failed to fetch users:', error)
  }
}

const handlePageChange = (page) => {
  currentPage.value = page
  fetchWorkOrders()
}

const openCreateModal = () => {
  isEditMode.value = false
  resetForm()
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  resetForm()
}

const resetForm = () => {
  form.value = {
    production_order_id: '',
    operation_name: '',
    workstation: '',
    assigned_to: '',
    scheduled_date: new Date().toISOString().split('T')[0],
    setup_time: 0,
    run_time: 60,
    status: 'pending',
    actual_start_time: '',
    actual_end_time: '',
    quality_checkpoints: [],
    materials_consumed: [],
    instructions: '',
    notes: ''
  }
}

const addCheckpoint = () => {
  form.value.quality_checkpoints.push({
    name: '',
    status: 'pending',
    notes: ''
  })
}

const removeCheckpoint = (index) => {
  form.value.quality_checkpoints.splice(index, 1)
}

const addMaterial = () => {
  form.value.materials_consumed.push({
    product_id: '',
    quantity: 0,
    unit: 'pcs',
    batch_number: ''
  })
}

const removeMaterial = (index) => {
  form.value.materials_consumed.splice(index, 1)
}

const calculateActualDuration = () => {
  if (!form.value.actual_start_time || !form.value.actual_end_time) return 0
  const start = new Date(form.value.actual_start_time)
  const end = new Date(form.value.actual_end_time)
  return Math.round((end - start) / 60000)
}

const calculateFormEfficiency = () => {
  const estimated = (form.value.setup_time || 0) + (form.value.run_time || 0)
  const actual = calculateActualDuration()
  if (!actual || !estimated) return 0
  return Math.round((estimated / actual) * 100)
}

const calculateEfficiency = (workOrder) => {
  if (!workOrder.actual_duration || !workOrder.estimated_duration) return 0
  return Math.round((workOrder.estimated_duration / workOrder.actual_duration) * 100)
}

const getEfficiencyClass = (workOrder) => {
  const efficiency = calculateEfficiency(workOrder)
  if (efficiency >= 95) return 'efficiency-excellent'
  if (efficiency >= 85) return 'efficiency-good'
  if (efficiency >= 70) return 'efficiency-fair'
  return 'efficiency-poor'
}

const handleSaveWorkOrder = async () => {
  if (!form.value.production_order_id || !form.value.operation_name) {
    alert('Please fill in all required fields.')
    return
  }
  
  try {
    const data = {
      ...form.value,
      estimated_duration: (form.value.setup_time || 0) + (form.value.run_time || 0),
      actual_duration: calculateActualDuration()
    }
    
    if (isEditMode.value) {
      await manufacturingService.updateWorkOrder(form.value.id, data)
    } else {
      await manufacturingService.createWorkOrder(data)
    }
    
    closeModal()
    await fetchWorkOrders()
  } catch (error) {
    console.error('Failed to save work order:', error)
    alert('Failed to save work order. Please try again.')
  }
}

const viewWorkOrder = async (workOrder) => {
  try {
    const response = await manufacturingService.getWorkOrder(workOrder.id)
    selectedWorkOrder.value = response.data.data
    showViewModal.value = true
  } catch (error) {
    console.error('Failed to fetch work order details:', error)
  }
}

const editWorkOrder = async (workOrder) => {
  try {
    const response = await manufacturingService.getWorkOrder(workOrder.id)
    const data = response.data.data
    form.value = {
      id: data.id,
      production_order_id: data.production_order_id,
      operation_name: data.operation_name,
      workstation: data.workstation || '',
      assigned_to: data.assigned_to || '',
      scheduled_date: data.scheduled_date,
      setup_time: data.setup_time || 0,
      run_time: data.run_time || 60,
      status: data.status,
      actual_start_time: data.actual_start_time || '',
      actual_end_time: data.actual_end_time || '',
      quality_checkpoints: data.quality_checkpoints || [],
      materials_consumed: data.materials_consumed || [],
      instructions: data.instructions || '',
      notes: data.notes || ''
    }
    isEditMode.value = true
    showModal.value = true
  } catch (error) {
    console.error('Failed to fetch work order:', error)
  }
}

const startWorkOrder = async (workOrder) => {
  if (confirm(`Start work order ${workOrder.work_order_number}?`)) {
    try {
      await manufacturingService.updateWorkOrder(workOrder.id, { 
        status: 'in_progress',
        actual_start_time: new Date().toISOString()
      })
      await fetchWorkOrders()
    } catch (error) {
      console.error('Failed to start work order:', error)
      alert('Failed to start work order. Please try again.')
    }
  }
}

const pauseWorkOrder = async (workOrder) => {
  if (confirm(`Pause work order ${workOrder.work_order_number}?`)) {
    try {
      await manufacturingService.updateWorkOrder(workOrder.id, { status: 'pending' })
      await fetchWorkOrders()
    } catch (error) {
      console.error('Failed to pause work order:', error)
      alert('Failed to pause work order. Please try again.')
    }
  }
}

const completeWorkOrder = async (workOrder) => {
  if (confirm(`Complete work order ${workOrder.work_order_number}?`)) {
    try {
      await manufacturingService.updateWorkOrder(workOrder.id, { 
        status: 'completed',
        actual_end_time: new Date().toISOString()
      })
      await fetchWorkOrders()
    } catch (error) {
      console.error('Failed to complete work order:', error)
      alert('Failed to complete work order. Please try again.')
    }
  }
}

const cancelWorkOrder = async (workOrder) => {
  if (confirm(`Cancel work order ${workOrder.work_order_number}? This action cannot be undone.`)) {
    try {
      await manufacturingService.updateWorkOrder(workOrder.id, { status: 'cancelled' })
      await fetchWorkOrders()
    } catch (error) {
      console.error('Failed to cancel work order:', error)
      alert('Failed to cancel work order. Please try again.')
    }
  }
}

const getCheckpointStatusClass = (status) => {
  const colors = {
    pending: 'badge-gray',
    passed: 'badge-green',
    failed: 'badge-red'
  }
  return colors[status] || 'badge-gray'
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatDateTime = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatDuration = (minutes) => {
  if (!minutes) return '0m'
  const hours = Math.floor(minutes / 60)
  const mins = minutes % 60
  return hours > 0 ? `${hours}h ${mins}m` : `${mins}m`
}

const formatStatus = (status) => {
  return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const getStatusColor = (status) => {
  const colors = {
    pending: 'gray',
    in_progress: 'blue',
    completed: 'green',
    cancelled: 'red'
  }
  return colors[status] || 'gray'
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
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.search-input {
  flex: 1;
  min-width: 300px;
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

.filter-group {
  display: flex;
  gap: 12px;
}

.filter-select {
  padding: 10px 16px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 14px;
  background: white;
}

.filter-select:focus {
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

.btn-secondary {
  background: #6b7280;
  color: white;
}

.btn-secondary:hover {
  background: #4b5563;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 13px;
}

.icon {
  width: 20px;
  height: 20px;
}

.icon-sm {
  width: 16px;
  height: 16px;
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

.btn-icon.btn-success:hover {
  color: #10b981;
  background: #d1fae5;
}

.btn-icon.btn-warning:hover {
  color: #f59e0b;
  background: #fef3c7;
}

.btn-icon.btn-danger:hover {
  color: #ef4444;
  background: #fef2f2;
}

.duration-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.duration-planned {
  font-weight: 500;
  color: #1f2937;
}

.duration-actual {
  font-size: 12px;
  color: #6b7280;
}

.efficiency-cell {
  display: flex;
  justify-content: center;
}

.efficiency-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
}

.efficiency-excellent {
  background: #dcfce7;
  color: #166534;
}

.efficiency-good {
  background: #dbeafe;
  color: #1e40af;
}

.efficiency-fair {
  background: #fef3c7;
  color: #92400e;
}

.efficiency-poor {
  background: #fee2e2;
  color: #991b1b;
}

.text-muted {
  color: #9ca3af;
}

.badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
  text-transform: capitalize;
}

.badge-lg {
  padding: 6px 16px;
  font-size: 14px;
}

.badge-blue {
  background: #dbeafe;
  color: #1e40af;
}

.badge-green {
  background: #dcfce7;
  color: #166534;
}

.badge-red {
  background: #fee2e2;
  color: #991b1b;
}

.badge-gray {
  background: #f3f4f6;
  color: #374151;
}

.font-semibold {
  font-weight: 600;
}

.text-blue-600 {
  color: #2563eb;
}

.form-section {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}

.section-header h4 {
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
}

.time-tracking-section,
.quality-section,
.materials-section {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 16px;
}

.form-group {
  margin-bottom: 20px;
}

.form-label {
  display: block;
  margin-bottom: 6px;
  font-size: 14px;
  font-weight: 500;
  color: #374151;
}

.read-only-field {
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  background: #f9fafb;
  color: #6b7280;
  font-size: 14px;
}

.checkpoint-row,
.material-row {
  display: grid;
  grid-template-columns: 2fr 1fr 2fr auto;
  gap: 12px;
  align-items: end;
  margin-bottom: 16px;
  padding-bottom: 16px;
  border-bottom: 1px solid #f3f4f6;
}

.checkpoint-row:last-child,
.material-row:last-child {
  border-bottom: none;
  margin-bottom: 0;
  padding-bottom: 0;
}

.material-row {
  grid-template-columns: 2fr 1fr 0.8fr 1fr auto;
}

.view-details {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.detail-header {
  display: flex;
  justify-content: space-between;
  align-items: start;
  padding-bottom: 16px;
  border-bottom: 2px solid #e5e7eb;
}

.detail-header h3 {
  font-size: 24px;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 4px 0;
}

.detail-subtitle {
  font-size: 14px;
  color: #6b7280;
  margin: 0;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.detail-label {
  font-size: 13px;
  font-weight: 500;
  color: #6b7280;
}

.detail-value {
  font-size: 14px;
  color: #1f2937;
}

.time-metrics h4,
.quality-detail h4,
.materials-detail h4,
.detail-section h4 {
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 12px;
}

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
  margin-bottom: 16px;
}

.metric-card {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 16px;
  background: #f9fafb;
  border-radius: 8px;
  text-align: center;
}

.metric-label {
  font-size: 12px;
  color: #6b7280;
}

.metric-value {
  font-size: 18px;
  font-weight: 600;
  color: #1f2937;
}

.efficiency-display {
  margin-bottom: 16px;
}

.efficiency-bar {
  position: relative;
  height: 32px;
  background: #e5e7eb;
  border-radius: 16px;
  overflow: hidden;
}

.efficiency-fill {
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: width 0.3s;
}

.efficiency-text {
  font-size: 13px;
  font-weight: 600;
  color: white;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.time-details {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.time-item {
  display: flex;
  justify-content: space-between;
}

.checkpoint-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.checkpoint-card {
  padding: 16px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
}

.checkpoint-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.checkpoint-name {
  font-weight: 500;
  color: #1f2937;
}

.checkpoint-notes {
  font-size: 14px;
  color: #6b7280;
  margin: 0;
}

.detail-table {
  width: 100%;
  border-collapse: collapse;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
}

.detail-table thead {
  background: #f9fafb;
}

.detail-table th,
.detail-table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #e5e7eb;
  font-size: 14px;
}

.detail-table th {
  font-weight: 600;
  color: #374151;
}

.detail-table td {
  color: #1f2937;
}

.detail-table tbody tr:last-child td {
  border-bottom: none;
}

.instruction-text {
  white-space: pre-wrap;
  color: #4b5563;
  line-height: 1.6;
  margin: 0;
}

.detail-section p {
  color: #4b5563;
  line-height: 1.6;
  margin: 0;
}
</style>
