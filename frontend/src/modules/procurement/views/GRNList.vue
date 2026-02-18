<template>
  <div class="page-container">
    <div class="page-header">
      <h1 class="page-title">Goods Receipt Notes (GRN)</h1>
      <button @click="openCreateModal" class="btn btn-primary">
        <PlusIcon class="icon" />
        Create GRN
      </button>
    </div>
    
    <div class="filters-section">
      <input
        v-model="filters.search"
        type="text"
        placeholder="Search by GRN number, PO number, or supplier..."
        class="search-input"
        @input="fetchGRNs"
      />
      <div class="filter-group">
        <select v-model="filters.status" @change="fetchGRNs" class="filter-select">
          <option value="">All Status</option>
          <option value="draft">Draft</option>
          <option value="completed">Completed</option>
        </select>
        <select v-model="filters.supplier_id" @change="fetchGRNs" class="filter-select">
          <option value="">All Suppliers</option>
          <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
            {{ supplier.name }}
          </option>
        </select>
        <input
          v-model="filters.dateFrom"
          type="date"
          class="filter-input"
          @change="fetchGRNs"
          placeholder="From Date"
        />
        <input
          v-model="filters.dateTo"
          type="date"
          class="filter-input"
          @change="fetchGRNs"
          placeholder="To Date"
        />
      </div>
    </div>
    
    <DataTable
      :columns="columns"
      :data="grns"
      :loading="loading"
      :current-page="currentPage"
      :total-pages="totalPages"
      @page-change="handlePageChange"
    >
      <template #cell-grn_number="{ row }">
        <span class="font-semibold text-blue-600">{{ row.grn_number }}</span>
      </template>
      
      <template #cell-received_date="{ row }">
        {{ formatDate(row.received_date) }}
      </template>
      
      <template #cell-status="{ row }">
        <span :class="['badge', `badge-${getStatusColor(row.status)}`]">
          {{ row.status }}
        </span>
      </template>
      
      <template #actions="{ row }">
        <div class="action-buttons">
          <button @click="viewGRN(row)" class="btn-icon" title="View">
            <EyeIcon class="icon" />
          </button>
          <button 
            v-if="row.status === 'draft'"
            @click="editGRN(row)" 
            class="btn-icon" 
            title="Edit"
          >
            <PencilIcon class="icon" />
          </button>
          <button 
            v-if="row.status === 'draft'"
            @click="completeGRN(row)" 
            class="btn-icon btn-success" 
            title="Complete & Update Inventory"
          >
            <CheckCircleIcon class="icon" />
          </button>
          <button 
            @click="printGRN(row)" 
            class="btn-icon" 
            title="Print"
          >
            <PrinterIcon class="icon" />
          </button>
        </div>
      </template>
    </DataTable>
    
    <Modal
      :show="showModal"
      :title="isEditMode ? 'Edit GRN' : 'Create GRN'"
      size="large"
      @close="closeModal"
      @confirm="handleSaveGRN"
    >
      <div class="form-section">
        <div class="form-grid">
          <FormSelect
            v-model="form.purchase_order_id"
            label="Purchase Order"
            :options="purchaseOrderOptions"
            required
            @change="loadPurchaseOrderItems"
          />
          <FormInput
            v-model="form.received_date"
            label="Received Date"
            type="date"
            required
          />
          <FormInput
            v-model="form.received_by"
            label="Received By"
            placeholder="Name of person receiving"
          />
          <FormSelect
            v-model="form.status"
            label="Status"
            :options="statusOptions"
          />
        </div>
        
        <div v-if="form.purchase_order_id && purchaseOrderInfo" class="po-info">
          <h4>Purchase Order Information</h4>
          <div class="info-grid">
            <div class="info-item">
              <span class="info-label">PO Number:</span>
              <span class="info-value">{{ purchaseOrderInfo.po_number }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Supplier:</span>
              <span class="info-value">{{ purchaseOrderInfo.supplier_name }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Order Date:</span>
              <span class="info-value">{{ formatDate(purchaseOrderInfo.order_date) }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Expected Delivery:</span>
              <span class="info-value">{{ formatDate(purchaseOrderInfo.expected_delivery_date) }}</span>
            </div>
          </div>
        </div>
        
        <div v-if="form.items.length > 0" class="items-section">
          <div class="section-header">
            <h4>Items to Receive</h4>
          </div>
          
          <div v-for="(item, index) in form.items" :key="index" class="receive-item-row">
            <div class="item-info">
              <div class="item-name">{{ item.product_name }}</div>
              <div class="item-meta">SKU: {{ item.product_sku }}</div>
            </div>
            
            <div class="quantity-section">
              <div class="quantity-info">
                <label class="form-label">Ordered</label>
                <div class="quantity-value">{{ item.ordered_quantity }}</div>
              </div>
              
              <FormInput
                v-model.number="item.received_quantity"
                label="Received"
                type="number"
                min="0"
                :max="item.ordered_quantity"
                required
              />
              
              <FormInput
                v-model.number="item.accepted_quantity"
                label="Accepted"
                type="number"
                min="0"
                :max="item.received_quantity || 0"
              />
              
              <FormInput
                v-model.number="item.rejected_quantity"
                label="Rejected"
                type="number"
                min="0"
                :max="item.received_quantity || 0"
              />
            </div>
            
            <div class="variance-indicator" v-if="item.received_quantity !== item.ordered_quantity">
              <span :class="item.received_quantity < item.ordered_quantity ? 'variance-negative' : 'variance-positive'">
                {{ item.received_quantity < item.ordered_quantity ? '▼' : '▲' }}
                {{ Math.abs(item.ordered_quantity - item.received_quantity) }}
              </span>
            </div>
            
            <div class="quality-section">
              <FormInput
                v-model="item.batch_number"
                label="Batch/Lot Number"
                placeholder="Optional"
              />
              <FormInput
                v-model="item.serial_number"
                label="Serial Number"
                placeholder="Optional"
              />
              <FormInput
                v-model="item.expiry_date"
                label="Expiry Date"
                type="date"
                placeholder="Optional"
              />
            </div>
            
            <FormTextarea
              v-model="item.notes"
              label="Notes / Quality Issues"
              rows="2"
              placeholder="Any quality concerns or notes..."
            />
          </div>
        </div>
        
        <div class="alert alert-warning" v-if="hasVariances">
          <strong>⚠️ Variance Detected:</strong> Some items have quantities that differ from the purchase order.
          Please ensure this is correct before completing the GRN.
        </div>
        
        <div class="alert alert-info" v-if="form.status === 'draft'">
          <strong>ℹ️ Draft Mode:</strong> This GRN will not update inventory until marked as completed.
        </div>
        
        <FormTextarea
          v-model="form.notes"
          label="General Notes"
          rows="3"
          placeholder="Overall notes about this receipt..."
        />
      </div>
    </Modal>
    
    <Modal
      :show="showViewModal"
      title="GRN Details"
      size="large"
      hide-footer
      @close="showViewModal = false"
    >
      <div v-if="selectedGRN" class="view-details">
        <div class="detail-header">
          <div>
            <h3>{{ selectedGRN.grn_number }}</h3>
            <p class="detail-subtitle">
              PO: {{ selectedGRN.po_number }} | 
              Supplier: {{ selectedGRN.supplier_name }}
            </p>
          </div>
          <span :class="['badge badge-lg', `badge-${getStatusColor(selectedGRN.status)}`]">
            {{ selectedGRN.status }}
          </span>
        </div>
        
        <div class="detail-grid">
          <div class="detail-item">
            <span class="detail-label">Received Date:</span>
            <span class="detail-value">{{ formatDate(selectedGRN.received_date) }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Received By:</span>
            <span class="detail-value">{{ selectedGRN.received_by || 'N/A' }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Created By:</span>
            <span class="detail-value">{{ selectedGRN.created_by_name || 'N/A' }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Created At:</span>
            <span class="detail-value">{{ formatDateTime(selectedGRN.created_at) }}</span>
          </div>
        </div>
        
        <div class="items-detail">
          <h4>Received Items</h4>
          <table class="detail-table">
            <thead>
              <tr>
                <th>Product</th>
                <th>Ordered</th>
                <th>Received</th>
                <th>Accepted</th>
                <th>Rejected</th>
                <th>Variance</th>
                <th>Batch/Serial</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in selectedGRN.items" :key="item.id">
                <td>
                  <div class="product-cell">
                    <div>{{ item.product_name }}</div>
                    <div class="text-sm text-gray">{{ item.product_sku }}</div>
                  </div>
                </td>
                <td>{{ item.ordered_quantity }}</td>
                <td>{{ item.received_quantity }}</td>
                <td>{{ item.accepted_quantity }}</td>
                <td>{{ item.rejected_quantity }}</td>
                <td>
                  <span 
                    v-if="item.received_quantity !== item.ordered_quantity"
                    :class="item.received_quantity < item.ordered_quantity ? 'variance-negative' : 'variance-positive'"
                  >
                    {{ item.received_quantity < item.ordered_quantity ? '-' : '+' }}
                    {{ Math.abs(item.ordered_quantity - item.received_quantity) }}
                  </span>
                  <span v-else class="text-gray">—</span>
                </td>
                <td>
                  <div class="text-sm">
                    <div v-if="item.batch_number">Batch: {{ item.batch_number }}</div>
                    <div v-if="item.serial_number">Serial: {{ item.serial_number }}</div>
                    <div v-if="item.expiry_date">Exp: {{ formatDate(item.expiry_date) }}</div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <div v-if="selectedGRN.notes" class="detail-section">
          <h4>Notes</h4>
          <p>{{ selectedGRN.notes }}</p>
        </div>
        
        <div class="action-section">
          <button 
            v-if="selectedGRN.status === 'draft'"
            @click="completeGRNFromView" 
            class="btn btn-success"
          >
            <CheckCircleIcon class="icon" />
            Complete & Update Inventory
          </button>
          <button @click="printGRN(selectedGRN)" class="btn btn-secondary">
            <PrinterIcon class="icon" />
            Print GRN
          </button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { 
  PlusIcon, PencilIcon, EyeIcon, 
  CheckCircleIcon, PrinterIcon 
} from '@heroicons/vue/24/outline'
import DataTable from '../../../components/common/DataTable.vue'
import Modal from '../../../components/common/Modal.vue'
import FormInput from '../../../components/forms/FormInput.vue'
import FormSelect from '../../../components/forms/FormSelect.vue'
import FormTextarea from '../../../components/forms/FormTextarea.vue'
import procurementService from '../../../services/procurementService'

const route = useRoute()
const loading = ref(false)
const showModal = ref(false)
const showViewModal = ref(false)
const isEditMode = ref(false)
const grns = ref([])
const selectedGRN = ref(null)
const currentPage = ref(1)
const totalPages = ref(1)
const suppliers = ref([])
const purchaseOrders = ref([])
const purchaseOrderInfo = ref(null)

const filters = ref({
  search: '',
  status: '',
  supplier_id: '',
  dateFrom: '',
  dateTo: ''
})

const form = ref({
  purchase_order_id: '',
  received_date: new Date().toISOString().split('T')[0],
  received_by: '',
  status: 'draft',
  notes: '',
  items: []
})

const columns = [
  { key: 'grn_number', label: 'GRN Number', sortable: true },
  { key: 'po_number', label: 'PO Number', sortable: true },
  { key: 'supplier_name', label: 'Supplier', sortable: true },
  { key: 'received_date', label: 'Received Date', sortable: true },
  { key: 'received_by', label: 'Received By' },
  { key: 'status', label: 'Status' }
]

const statusOptions = [
  { value: 'draft', label: 'Draft' },
  { value: 'completed', label: 'Completed' }
]

const purchaseOrderOptions = computed(() => 
  purchaseOrders.value.map(po => ({ 
    value: po.id, 
    label: `${po.po_number} - ${po.supplier_name}` 
  }))
)

const hasVariances = computed(() => {
  return form.value.items.some(item => 
    item.received_quantity !== item.ordered_quantity
  )
})

onMounted(async () => {
  await Promise.all([
    fetchGRNs(),
    fetchSuppliers(),
    fetchPurchaseOrders()
  ])
  
  if (route.query.action === 'create' && route.query.po_id) {
    form.value.purchase_order_id = parseInt(route.query.po_id)
    await loadPurchaseOrderItems()
    openCreateModal()
  }
})

watch(() => form.value.items, (items) => {
  items.forEach(item => {
    if (item.received_quantity !== undefined && item.rejected_quantity !== undefined) {
      item.accepted_quantity = Math.max(0, item.received_quantity - item.rejected_quantity)
    }
  })
}, { deep: true })

const fetchGRNs = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      search: filters.value.search,
      status: filters.value.status,
      supplier_id: filters.value.supplier_id,
      date_from: filters.value.dateFrom,
      date_to: filters.value.dateTo
    }
    const response = await procurementService.getGRNs(params)
    grns.value = response.data.data || []
    totalPages.value = response.data.meta?.last_page || 1
  } catch (error) {
    console.error('Failed to fetch GRNs:', error)
    grns.value = []
  } finally {
    loading.value = false
  }
}

const fetchSuppliers = async () => {
  try {
    const response = await procurementService.getSuppliers({ status: 'active' })
    suppliers.value = response.data.data || []
  } catch (error) {
    console.error('Failed to fetch suppliers:', error)
  }
}

const fetchPurchaseOrders = async () => {
  try {
    const response = await procurementService.getPurchaseOrders({ status: 'approved' })
    purchaseOrders.value = response.data.data || []
  } catch (error) {
    console.error('Failed to fetch purchase orders:', error)
  }
}

const loadPurchaseOrderItems = async () => {
  if (!form.value.purchase_order_id) {
    form.value.items = []
    purchaseOrderInfo.value = null
    return
  }
  
  try {
    const response = await procurementService.getPurchaseOrder(form.value.purchase_order_id)
    const po = response.data.data
    purchaseOrderInfo.value = po
    
    form.value.items = (po.items || []).map(item => ({
      product_id: item.product_id,
      product_name: item.product_name,
      product_sku: item.product_sku || item.sku || 'N/A',
      ordered_quantity: item.quantity,
      received_quantity: item.quantity,
      accepted_quantity: item.quantity,
      rejected_quantity: 0,
      batch_number: '',
      serial_number: '',
      expiry_date: '',
      notes: ''
    }))
  } catch (error) {
    console.error('Failed to load purchase order items:', error)
    alert('Failed to load purchase order items. Please try again.')
  }
}

const handlePageChange = (page) => {
  currentPage.value = page
  fetchGRNs()
}

const openCreateModal = () => {
  isEditMode.value = false
  if (!route.query.po_id) {
    resetForm()
  }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  resetForm()
}

const resetForm = () => {
  form.value = {
    purchase_order_id: '',
    received_date: new Date().toISOString().split('T')[0],
    received_by: '',
    status: 'draft',
    notes: '',
    items: []
  }
  purchaseOrderInfo.value = null
}

const handleSaveGRN = async () => {
  if (!form.value.purchase_order_id) {
    alert('Please select a purchase order.')
    return
  }
  
  if (form.value.items.length === 0) {
    alert('No items to receive.')
    return
  }
  
  const invalidItems = form.value.items.filter(item => 
    item.received_quantity > item.ordered_quantity
  )
  
  if (invalidItems.length > 0) {
    alert('Some items have received quantity greater than ordered quantity. Please correct.')
    return
  }
  
  try {
    const data = {
      ...form.value,
      supplier_id: purchaseOrderInfo.value?.supplier_id
    }
    
    if (isEditMode.value) {
      await procurementService.updateGRN(form.value.id, data)
    } else {
      await procurementService.createGRN(data)
    }
    
    closeModal()
    await fetchGRNs()
  } catch (error) {
    console.error('Failed to save GRN:', error)
    alert('Failed to save GRN. Please try again.')
  }
}

const viewGRN = async (grn) => {
  try {
    const response = await procurementService.getGRN(grn.id)
    selectedGRN.value = response.data.data
    showViewModal.value = true
  } catch (error) {
    console.error('Failed to fetch GRN details:', error)
  }
}

const editGRN = async (grn) => {
  try {
    const response = await procurementService.getGRN(grn.id)
    const data = response.data.data
    
    const poResponse = await procurementService.getPurchaseOrder(data.purchase_order_id)
    purchaseOrderInfo.value = poResponse.data.data
    
    form.value = {
      id: data.id,
      purchase_order_id: data.purchase_order_id,
      received_date: data.received_date,
      received_by: data.received_by || '',
      status: data.status,
      notes: data.notes || '',
      items: data.items || []
    }
    isEditMode.value = true
    showModal.value = true
  } catch (error) {
    console.error('Failed to fetch GRN:', error)
  }
}

const completeGRN = async (grn) => {
  if (confirm(`Complete GRN ${grn.grn_number}? This will update inventory and cannot be undone.`)) {
    try {
      await procurementService.completeGRN(grn.id)
      await fetchGRNs()
      alert('GRN completed successfully. Inventory has been updated.')
    } catch (error) {
      console.error('Failed to complete GRN:', error)
      alert('Failed to complete GRN. Please try again.')
    }
  }
}

const completeGRNFromView = async () => {
  if (selectedGRN.value) {
    await completeGRN(selectedGRN.value)
    showViewModal.value = false
  }
}

const printGRN = async (grn) => {
  try {
    const response = await procurementService.printGRN(grn.id)
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `GRN-${grn.grn_number}.pdf`
    link.click()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Failed to print GRN:', error)
    alert('Failed to generate GRN report. Please try again.')
  }
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatDateTime = (datetime) => {
  if (!datetime) return 'N/A'
  return new Date(datetime).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getStatusColor = (status) => {
  const colors = {
    draft: 'gray',
    completed: 'green'
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

.btn-success {
  background: #10b981;
  color: white;
}

.btn-success:hover {
  background: #059669;
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

.btn-icon.btn-success:hover {
  color: #10b981;
  background: #d1fae5;
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

.badge-green {
  background: #dcfce7;
  color: #166534;
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

.text-sm {
  font-size: 13px;
}

.text-gray {
  color: #6b7280;
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

.po-info {
  background: #f9fafb;
  padding: 16px;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.po-info h4 {
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 12px 0;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.info-item {
  display: flex;
  gap: 8px;
}

.info-label {
  font-size: 13px;
  font-weight: 500;
  color: #6b7280;
}

.info-value {
  font-size: 13px;
  color: #1f2937;
}

.items-section {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 16px;
}

.receive-item-row {
  display: flex;
  flex-direction: column;
  gap: 16px;
  padding: 16px;
  margin-bottom: 16px;
  background: #f9fafb;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.receive-item-row:last-child {
  margin-bottom: 0;
}

.item-info {
  padding-bottom: 12px;
  border-bottom: 1px solid #e5e7eb;
}

.item-name {
  font-size: 15px;
  font-weight: 600;
  color: #1f2937;
}

.item-meta {
  font-size: 13px;
  color: #6b7280;
  margin-top: 2px;
}

.quantity-section {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr 1fr;
  gap: 12px;
  align-items: end;
}

.quantity-info {
  display: flex;
  flex-direction: column;
}

.form-label {
  display: block;
  margin-bottom: 6px;
  font-size: 14px;
  font-weight: 500;
  color: #374151;
}

.quantity-value {
  padding: 10px 12px;
  background: white;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
}

.variance-indicator {
  margin-top: 8px;
}

.variance-negative {
  color: #dc2626;
  font-weight: 600;
}

.variance-positive {
  color: #059669;
  font-weight: 600;
}

.quality-section {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}

.alert {
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 14px;
}

.alert-warning {
  background: #fef3c7;
  border: 1px solid #fbbf24;
  color: #92400e;
}

.alert-info {
  background: #dbeafe;
  border: 1px solid #3b82f6;
  color: #1e40af;
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

.items-detail h4,
.detail-section h4 {
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 12px;
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

.product-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.detail-section p {
  color: #4b5563;
  line-height: 1.6;
  margin: 0;
}

.action-section {
  display: flex;
  gap: 12px;
  padding-top: 16px;
  border-top: 1px solid #e5e7eb;
}
</style>
