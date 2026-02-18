<template>
  <div class="page-container">
    <div class="page-header">
      <h1 class="page-title">Quotations</h1>
      <button @click="openCreateModal" class="btn btn-primary">
        <PlusIcon class="icon" />
        Add Quotation
      </button>
    </div>
    
    <div class="filters-section">
      <input
        v-model="filters.search"
        type="text"
        placeholder="Search by customer or quotation number..."
        class="search-input"
        @input="fetchQuotations"
      />
      <div class="filter-group">
        <select v-model="filters.status" @change="fetchQuotations" class="filter-select">
          <option value="">All Status</option>
          <option value="draft">Draft</option>
          <option value="sent">Sent</option>
          <option value="accepted">Accepted</option>
          <option value="rejected">Rejected</option>
          <option value="expired">Expired</option>
        </select>
        <input
          v-model="filters.dateFrom"
          type="date"
          class="filter-input"
          @change="fetchQuotations"
          placeholder="From Date"
        />
        <input
          v-model="filters.dateTo"
          type="date"
          class="filter-input"
          @change="fetchQuotations"
          placeholder="To Date"
        />
      </div>
    </div>
    
    <DataTable
      :columns="columns"
      :data="quotations"
      :loading="loading"
      :current-page="currentPage"
      :total-pages="totalPages"
      @page-change="handlePageChange"
    >
      <template #cell-quotation_number="{ row }">
        <span class="font-semibold text-blue-600">{{ row.quotation_number }}</span>
      </template>
      
      <template #cell-quotation_date="{ row }">
        {{ formatDate(row.quotation_date) }}
      </template>
      
      <template #cell-valid_until="{ row }">
        {{ formatDate(row.valid_until) }}
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
          <button @click="viewQuotation(row)" class="btn-icon" title="View">
            <EyeIcon class="icon" />
          </button>
          <button @click="editQuotation(row)" class="btn-icon" title="Edit">
            <PencilIcon class="icon" />
          </button>
          <button 
            v-if="row.status === 'sent' || row.status === 'draft'"
            @click="convertToSalesOrder(row)" 
            class="btn-icon" 
            title="Convert to Sales Order"
          >
            <ArrowPathIcon class="icon" />
          </button>
          <button @click="deleteQuotationConfirm(row)" class="btn-icon btn-danger" title="Delete">
            <TrashIcon class="icon" />
          </button>
        </div>
      </template>
    </DataTable>
    
    <Modal
      :show="showModal"
      :title="isEditMode ? 'Edit Quotation' : 'Create Quotation'"
      size="large"
      @close="closeModal"
      @confirm="handleSaveQuotation"
    >
      <div class="form-section">
        <div class="form-grid">
          <FormSelect
            v-model="form.customer_id"
            label="Customer"
            :options="customerOptions"
            required
          />
          <FormInput
            v-model="form.quotation_date"
            label="Quotation Date"
            type="date"
            required
          />
          <FormInput
            v-model="form.valid_until"
            label="Valid Until"
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
            <span class="total-label">Tax:</span>
            <span class="total-value">{{ formatCurrency(calculateTotalTax()) }}</span>
          </div>
          <div class="total-row grand-total">
            <span class="total-label">Grand Total:</span>
            <span class="total-value">{{ formatCurrency(calculateGrandTotal()) }}</span>
          </div>
        </div>
        
        <div class="form-grid">
          <FormTextarea
            v-model="form.notes"
            label="Notes"
            rows="3"
          />
          <FormTextarea
            v-model="form.terms"
            label="Terms & Conditions"
            rows="3"
          />
        </div>
      </div>
    </Modal>
    
    <Modal
      :show="showViewModal"
      title="Quotation Details"
      size="large"
      hide-footer
      @close="showViewModal = false"
    >
      <div v-if="selectedQuotation" class="view-details">
        <div class="detail-grid">
          <div class="detail-item">
            <span class="detail-label">Quotation Number:</span>
            <span class="detail-value">{{ selectedQuotation.quotation_number }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Customer:</span>
            <span class="detail-value">{{ selectedQuotation.customer_name }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Date:</span>
            <span class="detail-value">{{ formatDate(selectedQuotation.quotation_date) }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Valid Until:</span>
            <span class="detail-value">{{ formatDate(selectedQuotation.valid_until) }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Status:</span>
            <span :class="['badge', `badge-${getStatusColor(selectedQuotation.status)}`]">
              {{ selectedQuotation.status }}
            </span>
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
              <tr v-for="item in selectedQuotation.items" :key="item.id">
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
            <span>Total Amount:</span>
            <span class="font-semibold">{{ formatCurrency(selectedQuotation.total_amount) }}</span>
          </div>
        </div>
        
        <div v-if="selectedQuotation.notes" class="detail-section">
          <h4>Notes</h4>
          <p>{{ selectedQuotation.notes }}</p>
        </div>
        
        <div v-if="selectedQuotation.terms" class="detail-section">
          <h4>Terms & Conditions</h4>
          <p>{{ selectedQuotation.terms }}</p>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { PlusIcon, PencilIcon, TrashIcon, EyeIcon, ArrowPathIcon } from '@heroicons/vue/24/outline'
import DataTable from '../../../components/common/DataTable.vue'
import Modal from '../../../components/common/Modal.vue'
import FormInput from '../../../components/forms/FormInput.vue'
import FormSelect from '../../../components/forms/FormSelect.vue'
import FormTextarea from '../../../components/forms/FormTextarea.vue'
import posService from '../../../services/posService'
import customerService from '../../../services/customerService'
import inventoryService from '../../../services/inventoryService'

const loading = ref(false)
const showModal = ref(false)
const showViewModal = ref(false)
const isEditMode = ref(false)
const quotations = ref([])
const selectedQuotation = ref(null)
const currentPage = ref(1)
const totalPages = ref(1)
const customers = ref([])
const products = ref([])

// Constants
const DEFAULT_QUOTATION_VALIDITY_DAYS = 30

const filters = ref({
  search: '',
  status: '',
  dateFrom: '',
  dateTo: ''
})

const form = ref({
  customer_id: '',
  quotation_date: new Date().toISOString().split('T')[0],
  valid_until: new Date(Date.now() + DEFAULT_QUOTATION_VALIDITY_DAYS * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
  status: 'draft',
  notes: '',
  terms: '',
  items: []
})

const columns = [
  { key: 'quotation_number', label: 'Quotation #', sortable: true },
  { key: 'customer_name', label: 'Customer', sortable: true },
  { key: 'quotation_date', label: 'Date', sortable: true },
  { key: 'valid_until', label: 'Valid Until', sortable: true },
  { key: 'total_amount', label: 'Total Amount', sortable: true },
  { key: 'status', label: 'Status' }
]

const statusOptions = [
  { value: 'draft', label: 'Draft' },
  { value: 'sent', label: 'Sent' },
  { value: 'accepted', label: 'Accepted' },
  { value: 'rejected', label: 'Rejected' },
  { value: 'expired', label: 'Expired' }
]

const customerOptions = computed(() => 
  customers.value.map(c => ({ value: c.id, label: c.name }))
)

const productOptions = computed(() => 
  products.value.map(p => ({ value: p.id, label: `${p.name} (${p.sku})` }))
)

onMounted(async () => {
  await Promise.all([
    fetchQuotations(),
    fetchCustomers(),
    fetchProducts()
  ])
})

const fetchQuotations = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      search: filters.value.search,
      status: filters.value.status,
      date_from: filters.value.dateFrom,
      date_to: filters.value.dateTo
    }
    const response = await posService.getQuotations(params)
    quotations.value = response.data.data || []
    totalPages.value = response.data.meta?.last_page || 1
  } catch (error) {
    console.error('Failed to fetch quotations:', error)
    quotations.value = []
  } finally {
    loading.value = false
  }
}

const fetchCustomers = async () => {
  try {
    const response = await customerService.getAll()
    customers.value = response.data.data || []
  } catch (error) {
    console.error('Failed to fetch customers:', error)
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
  fetchQuotations()
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
    customer_id: '',
    quotation_date: new Date().toISOString().split('T')[0],
    valid_until: new Date(Date.now() + DEFAULT_QUOTATION_VALIDITY_DAYS * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    status: 'draft',
    notes: '',
    terms: '',
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

const handleSaveQuotation = async () => {
  try {
    const data = {
      ...form.value,
      total_amount: calculateGrandTotal()
    }
    
    if (isEditMode.value) {
      await posService.updateQuotation(form.value.id, data)
    } else {
      await posService.createQuotation(data)
    }
    
    closeModal()
    await fetchQuotations()
  } catch (error) {
    console.error('Failed to save quotation:', error)
    alert('Failed to save quotation. Please try again.')
  }
}

const viewQuotation = async (quotation) => {
  try {
    const response = await posService.getQuotation(quotation.id)
    selectedQuotation.value = response.data.data
    showViewModal.value = true
  } catch (error) {
    console.error('Failed to fetch quotation details:', error)
  }
}

const editQuotation = async (quotation) => {
  try {
    const response = await posService.getQuotation(quotation.id)
    const data = response.data.data
    form.value = {
      id: data.id,
      customer_id: data.customer_id,
      quotation_date: data.quotation_date,
      valid_until: data.valid_until,
      status: data.status,
      notes: data.notes || '',
      terms: data.terms || '',
      items: data.items || []
    }
    isEditMode.value = true
    showModal.value = true
  } catch (error) {
    console.error('Failed to fetch quotation:', error)
  }
}

const deleteQuotationConfirm = async (quotation) => {
  if (confirm(`Are you sure you want to delete quotation ${quotation.quotation_number}?`)) {
    try {
      await posService.deleteQuotation(quotation.id)
      await fetchQuotations()
    } catch (error) {
      console.error('Failed to delete quotation:', error)
      alert('Failed to delete quotation. Please try again.')
    }
  }
}

const convertToSalesOrder = async (quotation) => {
  if (confirm(`Convert quotation ${quotation.quotation_number} to a sales order?`)) {
    try {
      await posService.createSalesOrder({
        quotation_id: quotation.id,
        customer_id: quotation.customer_id,
        order_date: new Date().toISOString().split('T')[0],
        items: quotation.items
      })
      alert('Sales order created successfully!')
      await fetchQuotations()
    } catch (error) {
      console.error('Failed to convert to sales order:', error)
      alert('Failed to convert to sales order. Please try again.')
    }
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

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount || 0)
}

const getStatusColor = (status) => {
  const colors = {
    draft: 'gray',
    sent: 'blue',
    accepted: 'green',
    rejected: 'red',
    expired: 'orange'
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

.badge-orange {
  background: #ffedd5;
  color: #9a3412;
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
