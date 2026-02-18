<template>
  <div class="page-container">
    <div class="page-header">
      <h1 class="page-title">Production Orders</h1>
      <button @click="openCreateModal" class="btn btn-primary">
        <PlusIcon class="icon" />
        Create Production Order
      </button>
    </div>
    
    <div class="filters-section">
      <input
        v-model="filters.search"
        type="text"
        placeholder="Search by order number or product..."
        class="search-input"
        @input="fetchProductionOrders"
      />
      <div class="filter-group">
        <select v-model="filters.status" @change="fetchProductionOrders" class="filter-select">
          <option value="">All Status</option>
          <option value="planned">Planned</option>
          <option value="in_progress">In Progress</option>
          <option value="completed">Completed</option>
          <option value="on_hold">On Hold</option>
          <option value="cancelled">Cancelled</option>
        </select>
        <select v-model="filters.product_id" @change="fetchProductionOrders" class="filter-select">
          <option value="">All Products</option>
          <option v-for="product in products" :key="product.id" :value="product.id">
            {{ product.name }}
          </option>
        </select>
        <input
          v-model="filters.dateFrom"
          type="date"
          class="filter-input"
          @change="fetchProductionOrders"
          placeholder="From Date"
        />
        <input
          v-model="filters.dateTo"
          type="date"
          class="filter-input"
          @change="fetchProductionOrders"
          placeholder="To Date"
        />
      </div>
    </div>
    
    <DataTable
      :columns="columns"
      :data="productionOrders"
      :loading="loading"
      :current-page="currentPage"
      :total-pages="totalPages"
      @page-change="handlePageChange"
    >
      <template #cell-order_number="{ row }">
        <span class="font-semibold text-blue-600">{{ row.order_number }}</span>
      </template>
      
      <template #cell-start_date="{ row }">
        {{ formatDate(row.start_date) }}
      </template>
      
      <template #cell-expected_completion_date="{ row }">
        {{ formatDate(row.expected_completion_date) }}
      </template>
      
      <template #cell-progress="{ row }">
        <div class="progress-container">
          <div class="progress-bar">
            <div class="progress-fill" :style="{ width: `${row.progress || 0}%` }"></div>
          </div>
          <span class="progress-text">{{ row.progress || 0 }}%</span>
        </div>
      </template>
      
      <template #cell-status="{ row }">
        <span :class="['badge', `badge-${getStatusColor(row.status)}`]">
          {{ formatStatus(row.status) }}
        </span>
      </template>
      
      <template #actions="{ row }">
        <div class="action-buttons">
          <button @click="viewProductionOrder(row)" class="btn-icon" title="View">
            <EyeIcon class="icon" />
          </button>
          <button 
            v-if="row.status === 'planned'"
            @click="editProductionOrder(row)" 
            class="btn-icon" 
            title="Edit"
          >
            <PencilIcon class="icon" />
          </button>
          <button 
            v-if="row.status === 'planned'"
            @click="startProductionOrder(row)" 
            class="btn-icon btn-success" 
            title="Start"
          >
            <PlayIcon class="icon" />
          </button>
          <button 
            v-if="row.status === 'in_progress'"
            @click="holdProductionOrder(row)" 
            class="btn-icon btn-warning" 
            title="Put on Hold"
          >
            <PauseIcon class="icon" />
          </button>
          <button 
            v-if="row.status === 'on_hold'"
            @click="resumeProductionOrder(row)" 
            class="btn-icon btn-success" 
            title="Resume"
          >
            <PlayIcon class="icon" />
          </button>
          <button 
            v-if="row.status === 'in_progress'"
            @click="completeProductionOrder(row)" 
            class="btn-icon btn-success" 
            title="Complete"
          >
            <CheckCircleIcon class="icon" />
          </button>
          <button 
            v-if="['planned', 'in_progress', 'on_hold'].includes(row.status)"
            @click="cancelProductionOrder(row)" 
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
      :title="isEditMode ? 'Edit Production Order' : 'Create Production Order'"
      size="large"
      @close="closeModal"
      @confirm="handleSaveProductionOrder"
    >
      <div class="form-section">
        <div class="form-grid">
          <FormSelect
            v-model="form.product_id"
            label="Product"
            :options="productOptions"
            required
            @update:modelValue="onProductChange"
          />
          <FormSelect
            v-model="form.bom_id"
            label="BOM"
            :options="bomOptions"
            required
          />
          <FormInput
            v-model.number="form.quantity_to_produce"
            label="Quantity to Produce"
            type="number"
            min="1"
            required
          />
          <FormSelect
            v-model="form.priority"
            label="Priority"
            :options="priorityOptions"
            required
          />
          <FormInput
            v-model="form.start_date"
            label="Start Date"
            type="date"
            required
          />
          <FormInput
            v-model="form.expected_completion_date"
            label="Expected Completion Date"
            type="date"
            required
          />
          <FormSelect
            v-model="form.warehouse_id"
            label="Warehouse"
            :options="warehouseOptions"
            required
          />
          <FormSelect
            v-model="form.status"
            label="Status"
            :options="statusOptions"
            required
          />
        </div>
        
        <div v-if="form.bom_id" class="material-check-section">
          <div class="section-header">
            <h4>Material Availability</h4>
            <button 
              @click="checkMaterialAvailability" 
              type="button" 
              class="btn btn-sm btn-secondary"
            >
              <ArrowPathIcon class="icon-sm" />
              Check Availability
            </button>
          </div>
          
          <div v-if="materialAvailability.length > 0" class="material-list">
            <div v-for="material in materialAvailability" :key="material.product_id" class="material-item">
              <div class="material-info">
                <span class="material-name">{{ material.product_name }}</span>
                <span class="material-qty">
                  Required: {{ material.required_quantity }} {{ material.unit }} | 
                  Available: {{ material.available_quantity }} {{ material.unit }}
                </span>
              </div>
              <span :class="['badge', material.is_available ? 'badge-green' : 'badge-red']">
                {{ material.is_available ? 'Available' : `Short: ${material.shortage}` }}
              </span>
            </div>
          </div>
        </div>
        
        <div class="form-section">
          <div class="section-header">
            <h4>Cost Estimates</h4>
          </div>
          <div class="form-grid">
            <FormInput
              v-model.number="form.estimated_cost"
              label="Estimated Cost"
              type="number"
              step="0.01"
              min="0"
            />
            <div class="form-group">
              <label class="form-label">Actual Cost (Read-only)</label>
              <div class="read-only-field">{{ formatCurrency(form.actual_cost || 0) }}</div>
            </div>
          </div>
        </div>
        
        <FormTextarea
          v-model="form.notes"
          label="Notes"
          rows="3"
          placeholder="Production notes and instructions"
        />
      </div>
    </Modal>
    
    <Modal
      :show="showViewModal"
      title="Production Order Details"
      size="large"
      hide-footer
      @close="showViewModal = false"
    >
      <div v-if="selectedOrder" class="view-details">
        <div class="detail-header">
          <div>
            <h3>{{ selectedOrder.order_number }}</h3>
            <p class="detail-subtitle">Product: {{ selectedOrder.product_name }} ({{ selectedOrder.quantity_to_produce }} units)</p>
          </div>
          <span :class="['badge badge-lg', `badge-${getStatusColor(selectedOrder.status)}`]">
            {{ formatStatus(selectedOrder.status) }}
          </span>
        </div>
        
        <div class="progress-detail">
          <label class="detail-label">Production Progress</label>
          <div class="progress-bar-large">
            <div class="progress-fill" :style="{ width: `${selectedOrder.progress || 0}%` }"></div>
            <span class="progress-label">{{ selectedOrder.progress || 0 }}%</span>
          </div>
        </div>
        
        <div class="detail-grid">
          <div class="detail-item">
            <span class="detail-label">BOM:</span>
            <span class="detail-value">{{ selectedOrder.bom_code || 'N/A' }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Priority:</span>
            <span class="detail-value">{{ selectedOrder.priority || 'Normal' }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Start Date:</span>
            <span class="detail-value">{{ formatDate(selectedOrder.start_date) }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Expected Completion:</span>
            <span class="detail-value">{{ formatDate(selectedOrder.expected_completion_date) }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Actual Completion:</span>
            <span class="detail-value">{{ formatDate(selectedOrder.actual_completion_date) }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Warehouse:</span>
            <span class="detail-value">{{ selectedOrder.warehouse_name || 'N/A' }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Created By:</span>
            <span class="detail-value">{{ selectedOrder.created_by_name || 'N/A' }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Created At:</span>
            <span class="detail-value">{{ formatDateTime(selectedOrder.created_at) }}</span>
          </div>
        </div>
        
        <div class="costs-detail">
          <h4>Costs</h4>
          <div class="cost-comparison">
            <div class="cost-item">
              <span class="cost-label">Estimated Cost:</span>
              <span class="cost-value">{{ formatCurrency(selectedOrder.estimated_cost) }}</span>
            </div>
            <div class="cost-item">
              <span class="cost-label">Actual Cost:</span>
              <span class="cost-value">{{ formatCurrency(selectedOrder.actual_cost) }}</span>
            </div>
            <div class="cost-item" v-if="selectedOrder.actual_cost && selectedOrder.estimated_cost">
              <span class="cost-label">Variance:</span>
              <span :class="['cost-value', getCostVarianceClass(selectedOrder)]">
                {{ formatCurrency(selectedOrder.actual_cost - selectedOrder.estimated_cost) }}
                ({{ calculateVariancePercentage(selectedOrder) }}%)
              </span>
            </div>
          </div>
        </div>
        
        <div v-if="selectedOrder.materials" class="materials-detail">
          <h4>Material Consumption</h4>
          <table class="detail-table">
            <thead>
              <tr>
                <th>Material</th>
                <th>Planned Qty</th>
                <th>Consumed Qty</th>
                <th>Unit</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="material in selectedOrder.materials" :key="material.id">
                <td>{{ material.product_name }}</td>
                <td>{{ material.planned_quantity }}</td>
                <td>{{ material.consumed_quantity || 0 }}</td>
                <td>{{ material.unit }}</td>
                <td>
                  <span :class="['badge', getMaterialStatusClass(material)]">
                    {{ getMaterialStatus(material) }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <div v-if="selectedOrder.work_orders && selectedOrder.work_orders.length > 0" class="work-orders-detail">
          <h4>Work Orders</h4>
          <div class="work-order-list">
            <div v-for="wo in selectedOrder.work_orders" :key="wo.id" class="work-order-card">
              <div class="wo-header">
                <span class="wo-number">{{ wo.work_order_number }}</span>
                <span :class="['badge', `badge-${getStatusColor(wo.status)}`]">{{ formatStatus(wo.status) }}</span>
              </div>
              <div class="wo-details">
                <span>{{ wo.operation_name }}</span>
                <span class="wo-assigned">Assigned to: {{ wo.assigned_to_name || 'Unassigned' }}</span>
              </div>
            </div>
          </div>
        </div>
        
        <div v-if="selectedOrder.notes" class="detail-section">
          <h4>Notes</h4>
          <p>{{ selectedOrder.notes }}</p>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { 
  PlusIcon, PencilIcon, EyeIcon, TrashIcon,
  PlayIcon, PauseIcon, CheckCircleIcon, XCircleIcon, ArrowPathIcon
} from '@heroicons/vue/24/outline'
import DataTable from '../../../components/common/DataTable.vue'
import Modal from '../../../components/common/Modal.vue'
import FormInput from '../../../components/forms/FormInput.vue'
import FormSelect from '../../../components/forms/FormSelect.vue'
import FormTextarea from '../../../components/forms/FormTextarea.vue'
import manufacturingService from '../../../services/manufacturingService'
import inventoryService from '../../../services/inventoryService'

const loading = ref(false)
const showModal = ref(false)
const showViewModal = ref(false)
const isEditMode = ref(false)
const productionOrders = ref([])
const selectedOrder = ref(null)
const currentPage = ref(1)
const totalPages = ref(1)
const products = ref([])
const boms = ref([])
const warehouses = ref([])
const materialAvailability = ref([])

const filters = ref({
  search: '',
  status: '',
  product_id: '',
  dateFrom: '',
  dateTo: ''
})

const form = ref({
  product_id: '',
  bom_id: '',
  quantity_to_produce: 1,
  priority: 'normal',
  start_date: new Date().toISOString().split('T')[0],
  expected_completion_date: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
  warehouse_id: '',
  status: 'planned',
  estimated_cost: 0,
  actual_cost: 0,
  notes: ''
})

const columns = [
  { key: 'order_number', label: 'Order Number', sortable: true },
  { key: 'product_name', label: 'Product', sortable: true },
  { key: 'quantity_to_produce', label: 'Quantity', sortable: true },
  { key: 'start_date', label: 'Start Date', sortable: true },
  { key: 'expected_completion_date', label: 'Expected Completion', sortable: true },
  { key: 'progress', label: 'Progress' },
  { key: 'status', label: 'Status' }
]

const statusOptions = [
  { value: 'planned', label: 'Planned' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'completed', label: 'Completed' },
  { value: 'on_hold', label: 'On Hold' },
  { value: 'cancelled', label: 'Cancelled' }
]

const priorityOptions = [
  { value: 'low', label: 'Low' },
  { value: 'normal', label: 'Normal' },
  { value: 'high', label: 'High' },
  { value: 'urgent', label: 'Urgent' }
]

const productOptions = computed(() => 
  products.value
    .filter(p => p.type === 'inventory' || p.type === 'finished_goods')
    .map(p => ({ value: p.id, label: `${p.name} (${p.sku || 'N/A'})` }))
)

const bomOptions = computed(() => {
  if (!form.value.product_id) return []
  return boms.value
    .filter(b => b.product_id == form.value.product_id && b.status === 'active')
    .map(b => ({ value: b.id, label: `${b.bom_code} v${b.version}` }))
})

const warehouseOptions = computed(() => 
  warehouses.value.map(w => ({ value: w.id, label: w.name }))
)

onMounted(async () => {
  await Promise.all([
    fetchProductionOrders(),
    fetchProducts(),
    fetchBOMs(),
    fetchWarehouses()
  ])
})

const fetchProductionOrders = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      search: filters.value.search,
      status: filters.value.status,
      product_id: filters.value.product_id,
      date_from: filters.value.dateFrom,
      date_to: filters.value.dateTo
    }
    const response = await manufacturingService.getProductionOrders(params)
    productionOrders.value = response.data.data || []
    totalPages.value = response.data.meta?.last_page || 1
  } catch (error) {
    console.error('Failed to fetch production orders:', error)
    productionOrders.value = []
  } finally {
    loading.value = false
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

const fetchBOMs = async () => {
  try {
    const response = await manufacturingService.getBOMs({ status: 'active' })
    boms.value = response.data.data || []
  } catch (error) {
    console.error('Failed to fetch BOMs:', error)
  }
}

const fetchWarehouses = async () => {
  try {
    const response = await inventoryService.getWarehouses()
    warehouses.value = response.data.data || []
  } catch (error) {
    console.error('Failed to fetch warehouses:', error)
  }
}

const handlePageChange = (page) => {
  currentPage.value = page
  fetchProductionOrders()
}

const openCreateModal = () => {
  isEditMode.value = false
  resetForm()
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  resetForm()
  materialAvailability.value = []
}

const resetForm = () => {
  form.value = {
    product_id: '',
    bom_id: '',
    quantity_to_produce: 1,
    priority: 'normal',
    start_date: new Date().toISOString().split('T')[0],
    expected_completion_date: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    warehouse_id: '',
    status: 'planned',
    estimated_cost: 0,
    actual_cost: 0,
    notes: ''
  }
}

const onProductChange = () => {
  form.value.bom_id = ''
  materialAvailability.value = []
}

const checkMaterialAvailability = async () => {
  if (!form.value.bom_id || !form.value.quantity_to_produce) {
    alert('Please select a BOM and enter quantity to produce.')
    return
  }
  
  try {
    const bom = boms.value.find(b => b.id == form.value.bom_id)
    if (!bom || !bom.components) return
    
    materialAvailability.value = bom.components.map(component => {
      const product = products.value.find(p => p.id == component.product_id)
      const requiredQty = component.quantity * form.value.quantity_to_produce
      const availableQty = product?.stock_quantity || 0
      const shortage = Math.max(0, requiredQty - availableQty)
      
      return {
        product_id: component.product_id,
        product_name: product?.name || 'Unknown',
        required_quantity: requiredQty.toFixed(2),
        available_quantity: availableQty.toFixed(2),
        unit: component.unit,
        is_available: availableQty >= requiredQty,
        shortage: shortage.toFixed(2)
      }
    })
  } catch (error) {
    console.error('Failed to check material availability:', error)
  }
}

const handleSaveProductionOrder = async () => {
  if (!form.value.product_id || !form.value.bom_id) {
    alert('Please select a product and BOM.')
    return
  }
  
  try {
    const data = { ...form.value }
    
    if (isEditMode.value) {
      await manufacturingService.updateProductionOrder(form.value.id, data)
    } else {
      await manufacturingService.createProductionOrder(data)
    }
    
    closeModal()
    await fetchProductionOrders()
  } catch (error) {
    console.error('Failed to save production order:', error)
    alert('Failed to save production order. Please try again.')
  }
}

const viewProductionOrder = async (order) => {
  try {
    const response = await manufacturingService.getProductionOrder(order.id)
    selectedOrder.value = response.data.data
    showViewModal.value = true
  } catch (error) {
    console.error('Failed to fetch production order details:', error)
  }
}

const editProductionOrder = async (order) => {
  try {
    const response = await manufacturingService.getProductionOrder(order.id)
    const data = response.data.data
    form.value = {
      id: data.id,
      product_id: data.product_id,
      bom_id: data.bom_id,
      quantity_to_produce: data.quantity_to_produce,
      priority: data.priority || 'normal',
      start_date: data.start_date,
      expected_completion_date: data.expected_completion_date,
      warehouse_id: data.warehouse_id,
      status: data.status,
      estimated_cost: data.estimated_cost || 0,
      actual_cost: data.actual_cost || 0,
      notes: data.notes || ''
    }
    isEditMode.value = true
    showModal.value = true
  } catch (error) {
    console.error('Failed to fetch production order:', error)
  }
}

const startProductionOrder = async (order) => {
  if (confirm(`Start production order ${order.order_number}?`)) {
    try {
      await manufacturingService.updateProductionOrder(order.id, { status: 'in_progress' })
      await fetchProductionOrders()
    } catch (error) {
      console.error('Failed to start production order:', error)
      alert('Failed to start production order. Please try again.')
    }
  }
}

const holdProductionOrder = async (order) => {
  if (confirm(`Put production order ${order.order_number} on hold?`)) {
    try {
      await manufacturingService.updateProductionOrder(order.id, { status: 'on_hold' })
      await fetchProductionOrders()
    } catch (error) {
      console.error('Failed to put production order on hold:', error)
      alert('Failed to put production order on hold. Please try again.')
    }
  }
}

const resumeProductionOrder = async (order) => {
  if (confirm(`Resume production order ${order.order_number}?`)) {
    try {
      await manufacturingService.updateProductionOrder(order.id, { status: 'in_progress' })
      await fetchProductionOrders()
    } catch (error) {
      console.error('Failed to resume production order:', error)
      alert('Failed to resume production order. Please try again.')
    }
  }
}

const completeProductionOrder = async (order) => {
  if (confirm(`Complete production order ${order.order_number}?`)) {
    try {
      await manufacturingService.updateProductionOrder(order.id, { 
        status: 'completed',
        progress: 100,
        actual_completion_date: new Date().toISOString().split('T')[0]
      })
      await fetchProductionOrders()
    } catch (error) {
      console.error('Failed to complete production order:', error)
      alert('Failed to complete production order. Please try again.')
    }
  }
}

const cancelProductionOrder = async (order) => {
  if (confirm(`Cancel production order ${order.order_number}? This action cannot be undone.`)) {
    try {
      await manufacturingService.updateProductionOrder(order.id, { status: 'cancelled' })
      await fetchProductionOrders()
    } catch (error) {
      console.error('Failed to cancel production order:', error)
      alert('Failed to cancel production order. Please try again.')
    }
  }
}

const getMaterialStatus = (material) => {
  const consumed = material.consumed_quantity || 0
  const planned = material.planned_quantity || 0
  if (consumed === 0) return 'Not Started'
  if (consumed < planned) return 'Partial'
  if (consumed >= planned) return 'Complete'
  return 'Unknown'
}

const getMaterialStatusClass = (material) => {
  const consumed = material.consumed_quantity || 0
  const planned = material.planned_quantity || 0
  if (consumed === 0) return 'badge-gray'
  if (consumed < planned) return 'badge-blue'
  return 'badge-green'
}

const getCostVarianceClass = (order) => {
  const variance = (order.actual_cost || 0) - (order.estimated_cost || 0)
  return variance > 0 ? 'cost-over' : 'cost-under'
}

const calculateVariancePercentage = (order) => {
  if (!order.estimated_cost) return '0'
  const variance = ((order.actual_cost - order.estimated_cost) / order.estimated_cost) * 100
  return variance.toFixed(1)
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

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount || 0)
}

const formatStatus = (status) => {
  return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const getStatusColor = (status) => {
  const colors = {
    planned: 'gray',
    in_progress: 'blue',
    completed: 'green',
    on_hold: 'yellow',
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

.filter-select,
.filter-input {
  padding: 10px 16px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 14px;
  background: white;
}

.filter-select:focus,
.filter-input:focus {
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

.progress-container {
  display: flex;
  align-items: center;
  gap: 8px;
}

.progress-bar {
  flex: 1;
  height: 8px;
  background: #e5e7eb;
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: #3b82f6;
  transition: width 0.3s;
}

.progress-text {
  font-size: 12px;
  font-weight: 500;
  color: #6b7280;
  min-width: 35px;
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

.badge-yellow {
  background: #fef3c7;
  color: #92400e;
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

.material-check-section {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 16px;
}

.material-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.material-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  background: #f9fafb;
  border-radius: 6px;
}

.material-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.material-name {
  font-weight: 500;
  color: #1f2937;
}

.material-qty {
  font-size: 13px;
  color: #6b7280;
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

.progress-detail {
  padding: 16px;
  background: #f9fafb;
  border-radius: 8px;
}

.detail-label {
  display: block;
  font-size: 13px;
  font-weight: 500;
  color: #6b7280;
  margin-bottom: 8px;
}

.progress-bar-large {
  position: relative;
  height: 24px;
  background: #e5e7eb;
  border-radius: 12px;
  overflow: hidden;
}

.progress-bar-large .progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #3b82f6, #2563eb);
}

.progress-label {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 12px;
  font-weight: 600;
  color: white;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
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

.detail-value {
  font-size: 14px;
  color: #1f2937;
}

.costs-detail h4,
.materials-detail h4,
.work-orders-detail h4,
.detail-section h4 {
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 12px;
}

.cost-comparison {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 16px;
  background: #f9fafb;
  border-radius: 8px;
}

.cost-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.cost-label {
  font-weight: 500;
  color: #6b7280;
}

.cost-value {
  font-weight: 600;
  color: #1f2937;
}

.cost-over {
  color: #ef4444 !important;
}

.cost-under {
  color: #10b981 !important;
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

.work-order-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.work-order-card {
  padding: 16px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
}

.wo-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.wo-number {
  font-weight: 600;
  color: #2563eb;
}

.wo-details {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 14px;
  color: #6b7280;
}

.wo-assigned {
  font-size: 13px;
}

.detail-section p {
  color: #4b5563;
  line-height: 1.6;
  margin: 0;
}
</style>
