<template>
  <div class="page-container">
    <div class="page-header">
      <h1 class="page-title">Purchase Orders</h1>
      <button @click="openCreateModal" class="btn btn-primary">
        <PlusIcon class="icon" />
        Create Purchase Order
      </button>
    </div>
    
    <div class="filters-section">
      <input
        v-model="filters.search"
        type="text"
        placeholder="Search by PO number or supplier..."
        class="search-input"
        @input="fetchPurchaseOrders"
      />
      <div class="filter-group">
        <select v-model="filters.status" @change="fetchPurchaseOrders" class="filter-select">
          <option value="">All Status</option>
          <option value="draft">Draft</option>
          <option value="submitted">Submitted</option>
          <option value="approved">Approved</option>
          <option value="received">Received</option>
          <option value="cancelled">Cancelled</option>
        </select>
        <select v-model="filters.supplier_id" @change="fetchPurchaseOrders" class="filter-select">
          <option value="">All Suppliers</option>
          <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
            {{ supplier.name }}
          </option>
        </select>
        <input
          v-model="filters.dateFrom"
          type="date"
          class="filter-input"
          @change="fetchPurchaseOrders"
          placeholder="From Date"
        />
        <input
          v-model="filters.dateTo"
          type="date"
          class="filter-input"
          @change="fetchPurchaseOrders"
          placeholder="To Date"
        />
      </div>
    </div>
    
    <DataTable
      :columns="columns"
      :data="purchaseOrders"
      :loading="loading"
      :current-page="currentPage"
      :total-pages="totalPages"
      @page-change="handlePageChange"
    >
      <template #cell-po_number="{ row }">
        <span class="font-semibold text-blue-600">{{ row.po_number }}</span>
      </template>
      
      <template #cell-order_date="{ row }">
        {{ formatDate(row.order_date) }}
      </template>
      
      <template #cell-expected_delivery_date="{ row }">
        {{ formatDate(row.expected_delivery_date) }}
      </template>
      
      <template #cell-total_amount="{ row }">
        {{ formatCurrency(row.total_amount) }}
      </template>
      
      <template #cell-status="{ row }">
        <span :class="['badge', `badge-${getStatusColor(row.status)}`]">
          {{ row.status }}
        </span>
      </template>
      
      <template #actions="{ row }">
        <div class="action-buttons">
          <button @click="viewPurchaseOrder(row)" class="btn-icon" title="View">
            <EyeIcon class="icon" />
          </button>
          <button 
            v-if="row.status === 'draft'"
            @click="editPurchaseOrder(row)" 
            class="btn-icon" 
            title="Edit"
          >
            <PencilIcon class="icon" />
          </button>
          <button 
            v-if="row.status === 'draft'"
            @click="submitPurchaseOrder(row)" 
            class="btn-icon btn-success" 
            title="Submit"
          >
            <PaperAirplaneIcon class="icon" />
          </button>
          <button 
            v-if="row.status === 'submitted'"
            @click="approvePurchaseOrder(row)" 
            class="btn-icon btn-success" 
            title="Approve"
          >
            <CheckCircleIcon class="icon" />
          </button>
          <button 
            v-if="row.status === 'approved'"
            @click="generateGRN(row)" 
            class="btn-icon" 
            title="Generate GRN"
          >
            <DocumentPlusIcon class="icon" />
          </button>
          <button 
            v-if="['draft', 'submitted'].includes(row.status)"
            @click="cancelPurchaseOrder(row)" 
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
      :title="isEditMode ? 'Edit Purchase Order' : 'Create Purchase Order'"
      size="large"
      @close="closeModal"
      @confirm="handleSavePurchaseOrder"
    >
      <div class="form-section">
        <div class="form-grid">
          <FormSelect
            v-model="form.supplier_id"
            label="Supplier"
            :options="supplierOptions"
            required
          />
          <FormInput
            v-model="form.order_date"
            label="Order Date"
            type="date"
            required
          />
          <FormInput
            v-model="form.expected_delivery_date"
            label="Expected Delivery Date"
            type="date"
            required
          />
          <FormSelect
            v-model="form.status"
            label="Status"
            :options="statusOptions"
            required
          />
        </div>
        
        <div class="items-section">
          <div class="section-header">
            <h4>Items</h4>
            <button @click="addItem" type="button" class="btn btn-sm btn-secondary">
              <PlusIcon class="icon-sm" />
              Add Item
            </button>
          </div>
          
          <div v-for="(item, index) in form.items" :key="index" class="item-row">
            <FormSelect
              v-model="item.product_id"
              label="Product"
              :options="productOptions"
              required
            />
            <FormInput
              v-model.number="item.quantity"
              label="Quantity"
              type="number"
              min="1"
              required
            />
            <FormInput
              v-model.number="item.unit_price"
              label="Unit Price"
              type="number"
              step="0.01"
              min="0"
              required
            />
            <FormInput
              v-model.number="item.discount"
              label="Discount (%)"
              type="number"
              min="0"
              max="100"
            />
            <FormInput
              v-model.number="item.tax"
              label="Tax (%)"
              type="number"
              min="0"
            />
            <div class="item-total">
              <label class="form-label">Total</label>
              <div class="total-value">{{ formatCurrency(calculateItemTotal(item)) }}</div>
            </div>
            <button @click="removeItem(index)" type="button" class="btn-icon btn-danger" title="Remove">
              <TrashIcon class="icon" />
            </button>
          </div>
        </div>
        
        <div class="totals-section">
          <div class="total-row">
            <span class="total-label">Subtotal:</span>
            <span class="total-value">{{ formatCurrency(calculateSubtotal()) }}</span>
          </div>
          <div class="total-row">
            <span class="total-label">Discount:</span>
            <span class="total-value">{{ formatCurrency(calculateTotalDiscount()) }}</span>
          </div>
          <div class="total-row">
            <span class="total-label">Tax:</span>
            <span class="total-value">{{ formatCurrency(calculateTotalTax()) }}</span>
          </div>
          <div class="total-row grand-total">
            <span class="total-label">Grand Total:</span>
            <span class="total-value">{{ formatCurrency(calculateGrandTotal()) }}</span>
          </div>
        </div>
        
        <div class="form-section">
          <div class="section-header">
            <h4>Delivery Details</h4>
          </div>
          <div class="form-grid">
            <FormTextarea
              v-model="form.delivery_address"
              label="Delivery Address"
              rows="3"
            />
            <FormTextarea
              v-model="form.shipping_instructions"
              label="Shipping Instructions"
              rows="3"
            />
          </div>
        </div>
        
        <FormTextarea
          v-model="form.terms_conditions"
          label="Terms & Conditions"
          rows="3"
          placeholder="Payment terms, warranties, etc."
        />
        
        <FormTextarea
          v-model="form.notes"
          label="Notes"
          rows="3"
          placeholder="Additional notes or remarks"
        />
      </div>
    </Modal>
    
    <Modal
      :show="showViewModal"
      title="Purchase Order Details"
      size="large"
      hide-footer
      @close="showViewModal = false"
    >
      <div v-if="selectedPurchaseOrder" class="view-details">
        <div class="detail-header">
          <div>
            <h3>{{ selectedPurchaseOrder.po_number }}</h3>
            <p class="detail-subtitle">Supplier: {{ selectedPurchaseOrder.supplier_name }}</p>
          </div>
          <span :class="['badge badge-lg', `badge-${getStatusColor(selectedPurchaseOrder.status)}`]">
            {{ selectedPurchaseOrder.status }}
          </span>
        </div>
        
        <div class="detail-grid">
          <div class="detail-item">
            <span class="detail-label">Order Date:</span>
            <span class="detail-value">{{ formatDate(selectedPurchaseOrder.order_date) }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Expected Delivery:</span>
            <span class="detail-value">{{ formatDate(selectedPurchaseOrder.expected_delivery_date) }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Created By:</span>
            <span class="detail-value">{{ selectedPurchaseOrder.created_by_name || 'N/A' }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Approved By:</span>
            <span class="detail-value">{{ selectedPurchaseOrder.approved_by_name || 'N/A' }}</span>
          </div>
        </div>
        
        <div class="items-detail">
          <h4>Items</h4>
          <table class="detail-table">
            <thead>
              <tr>
                <th>Product</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Discount</th>
                <th>Tax</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in selectedPurchaseOrder.items" :key="item.id">
                <td>{{ item.product_name }}</td>
                <td>{{ item.quantity }}</td>
                <td>{{ formatCurrency(item.unit_price) }}</td>
                <td>{{ item.discount }}%</td>
                <td>{{ item.tax }}%</td>
                <td>{{ formatCurrency(item.total) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <div class="detail-totals">
          <div class="total-row">
            <span>Subtotal:</span>
            <span>{{ formatCurrency(selectedPurchaseOrder.subtotal) }}</span>
          </div>
          <div class="total-row">
            <span>Discount:</span>
            <span>{{ formatCurrency(selectedPurchaseOrder.total_discount) }}</span>
          </div>
          <div class="total-row">
            <span>Tax:</span>
            <span>{{ formatCurrency(selectedPurchaseOrder.total_tax) }}</span>
          </div>
          <div class="total-row">
            <span class="font-semibold">Total Amount:</span>
            <span class="font-semibold">{{ formatCurrency(selectedPurchaseOrder.total_amount) }}</span>
          </div>
        </div>
        
        <div v-if="selectedPurchaseOrder.delivery_address" class="detail-section">
          <h4>Delivery Address</h4>
          <p>{{ selectedPurchaseOrder.delivery_address }}</p>
        </div>
        
        <div v-if="selectedPurchaseOrder.shipping_instructions" class="detail-section">
          <h4>Shipping Instructions</h4>
          <p>{{ selectedPurchaseOrder.shipping_instructions }}</p>
        </div>
        
        <div v-if="selectedPurchaseOrder.terms_conditions" class="detail-section">
          <h4>Terms & Conditions</h4>
          <p>{{ selectedPurchaseOrder.terms_conditions }}</p>
        </div>
        
        <div v-if="selectedPurchaseOrder.notes" class="detail-section">
          <h4>Notes</h4>
          <p>{{ selectedPurchaseOrder.notes }}</p>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { 
  PlusIcon, PencilIcon, TrashIcon, EyeIcon, 
  PaperAirplaneIcon, CheckCircleIcon, XCircleIcon, DocumentPlusIcon 
} from '@heroicons/vue/24/outline'
import DataTable from '../../../components/common/DataTable.vue'
import Modal from '../../../components/common/Modal.vue'
import FormInput from '../../../components/forms/FormInput.vue'
import FormSelect from '../../../components/forms/FormSelect.vue'
import FormTextarea from '../../../components/forms/FormTextarea.vue'
import procurementService from '../../../services/procurementService'
import inventoryService from '../../../services/inventoryService'

const router = useRouter()
const route = useRoute()
const loading = ref(false)
const showModal = ref(false)
const showViewModal = ref(false)
const isEditMode = ref(false)
const purchaseOrders = ref([])
const selectedPurchaseOrder = ref(null)
const currentPage = ref(1)
const totalPages = ref(1)
const suppliers = ref([])
const products = ref([])

const filters = ref({
  search: '',
  status: '',
  supplier_id: route.query.supplier_id || '',
  dateFrom: '',
  dateTo: ''
})

const form = ref({
  supplier_id: '',
  order_date: new Date().toISOString().split('T')[0],
  expected_delivery_date: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
  status: 'draft',
  delivery_address: '',
  shipping_instructions: '',
  terms_conditions: '',
  notes: '',
  items: []
})

const columns = [
  { key: 'po_number', label: 'PO Number', sortable: true },
  { key: 'supplier_name', label: 'Supplier', sortable: true },
  { key: 'order_date', label: 'Order Date', sortable: true },
  { key: 'expected_delivery_date', label: 'Expected Delivery', sortable: true },
  { key: 'total_amount', label: 'Total Amount', sortable: true },
  { key: 'status', label: 'Status' }
]

const statusOptions = [
  { value: 'draft', label: 'Draft' },
  { value: 'submitted', label: 'Submitted' },
  { value: 'approved', label: 'Approved' },
  { value: 'received', label: 'Received' },
  { value: 'cancelled', label: 'Cancelled' }
]

const supplierOptions = computed(() => 
  suppliers.value.map(s => ({ value: s.id, label: s.name }))
)

const productOptions = computed(() => 
  products.value.map(p => ({ value: p.id, label: `${p.name} (${p.sku})` }))
)

onMounted(async () => {
  await Promise.all([
    fetchPurchaseOrders(),
    fetchSuppliers(),
    fetchProducts()
  ])
})

const fetchPurchaseOrders = async () => {
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
    const response = await procurementService.getPurchaseOrders(params)
    purchaseOrders.value = response.data.data || []
    totalPages.value = response.data.meta?.last_page || 1
  } catch (error) {
    console.error('Failed to fetch purchase orders:', error)
    purchaseOrders.value = []
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

const fetchProducts = async () => {
  try {
    const response = await inventoryService.getProducts()
    products.value = response.data.data || []
  } catch (error) {
    console.error('Failed to fetch products:', error)
  }
}

const handlePageChange = (page) => {
  currentPage.value = page
  fetchPurchaseOrders()
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
    supplier_id: filters.value.supplier_id || '',
    order_date: new Date().toISOString().split('T')[0],
    expected_delivery_date: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    status: 'draft',
    delivery_address: '',
    shipping_instructions: '',
    terms_conditions: '',
    notes: '',
    items: []
  }
}

const addItem = () => {
  form.value.items.push({
    product_id: '',
    quantity: 1,
    unit_price: 0,
    discount: 0,
    tax: 0
  })
}

const removeItem = (index) => {
  form.value.items.splice(index, 1)
}

const calculateItemTotal = (item) => {
  const subtotal = (item.quantity || 0) * (item.unit_price || 0)
  const discountAmount = subtotal * ((item.discount || 0) / 100)
  const afterDiscount = subtotal - discountAmount
  const taxAmount = afterDiscount * ((item.tax || 0) / 100)
  return afterDiscount + taxAmount
}

const calculateSubtotal = () => {
  return form.value.items.reduce((sum, item) => {
    return sum + ((item.quantity || 0) * (item.unit_price || 0))
  }, 0)
}

const calculateTotalDiscount = () => {
  return form.value.items.reduce((sum, item) => {
    const subtotal = (item.quantity || 0) * (item.unit_price || 0)
    return sum + (subtotal * ((item.discount || 0) / 100))
  }, 0)
}

const calculateTotalTax = () => {
  return form.value.items.reduce((sum, item) => {
    const subtotal = (item.quantity || 0) * (item.unit_price || 0)
    const discountAmount = subtotal * ((item.discount || 0) / 100)
    const afterDiscount = subtotal - discountAmount
    return sum + (afterDiscount * ((item.tax || 0) / 100))
  }, 0)
}

const calculateGrandTotal = () => {
  return form.value.items.reduce((sum, item) => sum + calculateItemTotal(item), 0)
}

const handleSavePurchaseOrder = async () => {
  if (form.value.items.length === 0) {
    alert('Please add at least one item to the purchase order.')
    return
  }
  
  try {
    const data = {
      ...form.value,
      total_amount: calculateGrandTotal(),
      subtotal: calculateSubtotal(),
      total_discount: calculateTotalDiscount(),
      total_tax: calculateTotalTax()
    }
    
    if (isEditMode.value) {
      await procurementService.updatePurchaseOrder(form.value.id, data)
    } else {
      await procurementService.createPurchaseOrder(data)
    }
    
    closeModal()
    await fetchPurchaseOrders()
  } catch (error) {
    console.error('Failed to save purchase order:', error)
    alert('Failed to save purchase order. Please try again.')
  }
}

const viewPurchaseOrder = async (po) => {
  try {
    const response = await procurementService.getPurchaseOrder(po.id)
    selectedPurchaseOrder.value = response.data.data
    showViewModal.value = true
  } catch (error) {
    console.error('Failed to fetch purchase order details:', error)
  }
}

const editPurchaseOrder = async (po) => {
  try {
    const response = await procurementService.getPurchaseOrder(po.id)
    const data = response.data.data
    form.value = {
      id: data.id,
      supplier_id: data.supplier_id,
      order_date: data.order_date,
      expected_delivery_date: data.expected_delivery_date,
      status: data.status,
      delivery_address: data.delivery_address || '',
      shipping_instructions: data.shipping_instructions || '',
      terms_conditions: data.terms_conditions || '',
      notes: data.notes || '',
      items: data.items || []
    }
    isEditMode.value = true
    showModal.value = true
  } catch (error) {
    console.error('Failed to fetch purchase order:', error)
  }
}

const submitPurchaseOrder = async (po) => {
  if (confirm(`Submit purchase order ${po.po_number}? This will send it for approval.`)) {
    try {
      await procurementService.submitPurchaseOrder(po.id)
      await fetchPurchaseOrders()
    } catch (error) {
      console.error('Failed to submit purchase order:', error)
      alert('Failed to submit purchase order. Please try again.')
    }
  }
}

const approvePurchaseOrder = async (po) => {
  if (confirm(`Approve purchase order ${po.po_number}?`)) {
    try {
      await procurementService.approvePurchaseOrder(po.id)
      await fetchPurchaseOrders()
    } catch (error) {
      console.error('Failed to approve purchase order:', error)
      alert('Failed to approve purchase order. Please try again.')
    }
  }
}

const cancelPurchaseOrder = async (po) => {
  if (confirm(`Cancel purchase order ${po.po_number}? This action cannot be undone.`)) {
    try {
      await procurementService.cancelPurchaseOrder(po.id)
      await fetchPurchaseOrders()
    } catch (error) {
      console.error('Failed to cancel purchase order:', error)
      alert('Failed to cancel purchase order. Please try again.')
    }
  }
}

const generateGRN = (po) => {
  router.push({ name: 'GRNList', query: { po_id: po.id, action: 'create' } })
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount || 0)
}

const getStatusColor = (status) => {
  const colors = {
    draft: 'gray',
    submitted: 'blue',
    approved: 'green',
    received: 'purple',
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

.btn-icon.btn-danger:hover {
  color: #ef4444;
  background: #fef2f2;
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

.badge-purple {
  background: #e9d5ff;
  color: #6b21a8;
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

.items-section {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 16px;
}

.item-row {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr auto;
  gap: 12px;
  align-items: end;
  margin-bottom: 16px;
  padding-bottom: 16px;
  border-bottom: 1px solid #f3f4f6;
}

.item-row:last-child {
  border-bottom: none;
  margin-bottom: 0;
  padding-bottom: 0;
}

.item-total {
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

.total-value {
  font-weight: 600;
  color: #1f2937;
}

.totals-section {
  border-top: 2px solid #e5e7eb;
  padding-top: 16px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  align-items: flex-end;
}

.total-row {
  display: flex;
  gap: 24px;
  justify-content: space-between;
  min-width: 300px;
}

.total-label {
  font-weight: 500;
  color: #6b7280;
}

.grand-total {
  font-size: 18px;
  font-weight: 700;
  padding-top: 8px;
  border-top: 2px solid #e5e7eb;
}

.grand-total .total-label,
.grand-total .total-value {
  color: #1f2937;
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

.detail-totals {
  display: flex;
  flex-direction: column;
  gap: 8px;
  align-items: flex-end;
  padding: 16px;
  background: #f9fafb;
  border-radius: 8px;
}

.detail-section p {
  color: #4b5563;
  line-height: 1.6;
  margin: 0;
}
</style>
