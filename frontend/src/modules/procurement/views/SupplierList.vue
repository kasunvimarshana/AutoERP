<template>
  <div class="page-container">
    <div class="page-header">
      <h1 class="page-title">Suppliers</h1>
      <button @click="openCreateModal" class="btn btn-primary">
        <PlusIcon class="icon" />
        Add Supplier
      </button>
    </div>
    
    <div class="filters-section">
      <input
        v-model="filters.search"
        type="text"
        placeholder="Search by name, code, or contact person..."
        class="search-input"
        @input="fetchSuppliers"
      />
      <div class="filter-group">
        <select v-model="filters.country" @change="fetchSuppliers" class="filter-select">
          <option value="">All Countries</option>
          <option v-for="country in countries" :key="country" :value="country">{{ country }}</option>
        </select>
        <select v-model="filters.status" @change="fetchSuppliers" class="filter-select">
          <option value="">All Status</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>
    </div>
    
    <DataTable
      :columns="columns"
      :data="suppliers"
      :loading="loading"
      :current-page="currentPage"
      :total-pages="totalPages"
      @page-change="handlePageChange"
    >
      <template #cell-supplier_code="{ row }">
        <span class="font-semibold text-blue-600">{{ row.supplier_code }}</span>
      </template>
      
      <template #cell-contact_info="{ row }">
        <div class="contact-info">
          <div>{{ row.contact_person }}</div>
          <div class="text-sm text-gray">{{ row.email }}</div>
          <div class="text-sm text-gray">{{ row.phone }}</div>
        </div>
      </template>
      
      <template #cell-status="{ row }">
        <span :class="['badge', row.status === 'active' ? 'badge-green' : 'badge-gray']">
          {{ row.status }}
        </span>
      </template>
      
      <template #cell-rating="{ row }">
        <div v-if="row.rating" class="rating">
          <span v-for="i in 5" :key="i" :class="i <= row.rating ? 'star-filled' : 'star-empty'">★</span>
        </div>
        <span v-else class="text-gray">N/A</span>
      </template>
      
      <template #actions="{ row }">
        <div class="action-buttons">
          <button @click="viewSupplier(row)" class="btn-icon" title="View Details">
            <EyeIcon class="icon" />
          </button>
          <button @click="editSupplier(row)" class="btn-icon" title="Edit">
            <PencilIcon class="icon" />
          </button>
          <button @click="viewPurchaseOrders(row)" class="btn-icon" title="View Purchase Orders">
            <DocumentTextIcon class="icon" />
          </button>
          <button @click="toggleStatus(row)" class="btn-icon" :title="row.status === 'active' ? 'Deactivate' : 'Activate'">
            <PowerIcon class="icon" />
          </button>
          <button @click="deleteSupplierConfirm(row)" class="btn-icon btn-danger" title="Delete">
            <TrashIcon class="icon" />
          </button>
        </div>
      </template>
    </DataTable>
    
    <Modal
      :show="showModal"
      :title="isEditMode ? 'Edit Supplier' : 'Create Supplier'"
      size="large"
      @close="closeModal"
      @confirm="handleSaveSupplier"
    >
      <div class="form-section">
        <div class="form-grid">
          <FormInput
            v-model="form.name"
            label="Supplier Name"
            required
            placeholder="Enter supplier name"
          />
          <FormInput
            v-model="form.supplier_code"
            label="Supplier Code"
            required
            placeholder="e.g., SUP-001"
          />
          <FormInput
            v-model="form.contact_person"
            label="Contact Person"
            placeholder="Primary contact name"
          />
          <FormInput
            v-model="form.email"
            label="Email"
            type="email"
            placeholder="supplier@example.com"
          />
          <FormInput
            v-model="form.phone"
            label="Phone"
            type="tel"
            placeholder="+1 (555) 000-0000"
          />
          <FormInput
            v-model="form.mobile"
            label="Mobile"
            type="tel"
            placeholder="+1 (555) 000-0000"
          />
        </div>
        
        <div class="section-header">
          <h4>Address Information</h4>
        </div>
        
        <div class="form-grid">
          <FormTextarea
            v-model="form.address"
            label="Address"
            rows="2"
            placeholder="Street address"
          />
          <div class="form-grid">
            <FormInput
              v-model="form.city"
              label="City"
              placeholder="City"
            />
            <FormInput
              v-model="form.state"
              label="State/Province"
              placeholder="State"
            />
          </div>
          <FormInput
            v-model="form.postal_code"
            label="Postal Code"
            placeholder="Postal code"
          />
          <FormInput
            v-model="form.country"
            label="Country"
            placeholder="Country"
          />
        </div>
        
        <div class="section-header">
          <h4>Business Information</h4>
        </div>
        
        <div class="form-grid">
          <FormInput
            v-model="form.tax_id"
            label="Tax ID / VAT Number"
            placeholder="Tax identification number"
          />
          <FormInput
            v-model="form.payment_terms"
            label="Payment Terms"
            placeholder="e.g., Net 30, Net 60"
          />
          <FormInput
            v-model="form.credit_limit"
            label="Credit Limit"
            type="number"
            step="0.01"
            min="0"
            placeholder="0.00"
          />
          <FormSelect
            v-model="form.status"
            label="Status"
            :options="statusOptions"
          />
        </div>
        
        <FormTextarea
          v-model="form.notes"
          label="Notes"
          rows="3"
          placeholder="Additional notes about this supplier"
        />
      </div>
    </Modal>
    
    <Modal
      :show="showViewModal"
      title="Supplier Details"
      size="large"
      hide-footer
      @close="showViewModal = false"
    >
      <div v-if="selectedSupplier" class="view-details">
        <div class="detail-header">
          <div>
            <h3>{{ selectedSupplier.name }}</h3>
            <span class="detail-code">{{ selectedSupplier.supplier_code }}</span>
          </div>
          <span :class="['badge badge-lg', selectedSupplier.status === 'active' ? 'badge-green' : 'badge-gray']">
            {{ selectedSupplier.status }}
          </span>
        </div>
        
        <div class="metrics-grid" v-if="supplierMetrics">
          <div class="metric-card">
            <div class="metric-label">Total Orders</div>
            <div class="metric-value">{{ supplierMetrics.total_orders || 0 }}</div>
          </div>
          <div class="metric-card">
            <div class="metric-label">Total Spent</div>
            <div class="metric-value">{{ formatCurrency(supplierMetrics.total_spent || 0) }}</div>
          </div>
          <div class="metric-card">
            <div class="metric-label">Avg. Delivery Time</div>
            <div class="metric-value">{{ supplierMetrics.avg_delivery_days || 0 }} days</div>
          </div>
          <div class="metric-card">
            <div class="metric-label">Supplier Rating</div>
            <div class="metric-value rating">
              <span v-for="i in 5" :key="i" :class="i <= (supplierMetrics.rating || 0) ? 'star-filled' : 'star-empty'">★</span>
            </div>
          </div>
        </div>
        
        <div class="detail-section">
          <h4>Contact Information</h4>
          <div class="detail-grid">
            <div class="detail-item">
              <span class="detail-label">Contact Person:</span>
              <span class="detail-value">{{ selectedSupplier.contact_person || 'N/A' }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Email:</span>
              <span class="detail-value">{{ selectedSupplier.email || 'N/A' }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Phone:</span>
              <span class="detail-value">{{ selectedSupplier.phone || 'N/A' }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Mobile:</span>
              <span class="detail-value">{{ selectedSupplier.mobile || 'N/A' }}</span>
            </div>
          </div>
        </div>
        
        <div class="detail-section">
          <h4>Address</h4>
          <div class="detail-address">
            <p>{{ selectedSupplier.address || 'N/A' }}</p>
            <p>{{ [selectedSupplier.city, selectedSupplier.state].filter(Boolean).join(', ') || 'N/A' }}</p>
            <p>{{ [selectedSupplier.postal_code, selectedSupplier.country].filter(Boolean).join(' ') || 'N/A' }}</p>
          </div>
        </div>
        
        <div class="detail-section">
          <h4>Business Information</h4>
          <div class="detail-grid">
            <div class="detail-item">
              <span class="detail-label">Tax ID:</span>
              <span class="detail-value">{{ selectedSupplier.tax_id || 'N/A' }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Payment Terms:</span>
              <span class="detail-value">{{ selectedSupplier.payment_terms || 'N/A' }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Credit Limit:</span>
              <span class="detail-value">{{ formatCurrency(selectedSupplier.credit_limit || 0) }}</span>
            </div>
          </div>
        </div>
        
        <div v-if="selectedSupplier.notes" class="detail-section">
          <h4>Notes</h4>
          <p class="detail-notes">{{ selectedSupplier.notes }}</p>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { PlusIcon, PencilIcon, TrashIcon, EyeIcon, DocumentTextIcon, PowerIcon } from '@heroicons/vue/24/outline'
import DataTable from '../../../components/common/DataTable.vue'
import Modal from '../../../components/common/Modal.vue'
import FormInput from '../../../components/forms/FormInput.vue'
import FormSelect from '../../../components/forms/FormSelect.vue'
import FormTextarea from '../../../components/forms/FormTextarea.vue'
import procurementService from '../../../services/procurementService'

const router = useRouter()
const loading = ref(false)
const showModal = ref(false)
const showViewModal = ref(false)
const isEditMode = ref(false)
const suppliers = ref([])
const selectedSupplier = ref(null)
const supplierMetrics = ref(null)
const currentPage = ref(1)
const totalPages = ref(1)

const countries = ['United States', 'Canada', 'United Kingdom', 'Germany', 'France', 'China', 'Japan', 'India', 'Australia', 'Mexico']

const filters = ref({
  search: '',
  country: '',
  status: ''
})

const form = ref({
  name: '',
  supplier_code: '',
  contact_person: '',
  email: '',
  phone: '',
  mobile: '',
  address: '',
  city: '',
  state: '',
  postal_code: '',
  country: '',
  tax_id: '',
  payment_terms: '',
  credit_limit: 0,
  status: 'active',
  notes: ''
})

const columns = [
  { key: 'supplier_code', label: 'Code', sortable: true },
  { key: 'name', label: 'Supplier Name', sortable: true },
  { key: 'contact_info', label: 'Contact Information' },
  { key: 'country', label: 'Country', sortable: true },
  { key: 'rating', label: 'Rating' },
  { key: 'status', label: 'Status' }
]

const statusOptions = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' }
]

onMounted(async () => {
  await fetchSuppliers()
})

const fetchSuppliers = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      search: filters.value.search,
      country: filters.value.country,
      status: filters.value.status
    }
    const response = await procurementService.getSuppliers(params)
    suppliers.value = response.data.data || []
    totalPages.value = response.data.meta?.last_page || 1
  } catch (error) {
    console.error('Failed to fetch suppliers:', error)
    suppliers.value = []
  } finally {
    loading.value = false
  }
}

const handlePageChange = (page) => {
  currentPage.value = page
  fetchSuppliers()
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
    name: '',
    supplier_code: '',
    contact_person: '',
    email: '',
    phone: '',
    mobile: '',
    address: '',
    city: '',
    state: '',
    postal_code: '',
    country: '',
    tax_id: '',
    payment_terms: '',
    credit_limit: 0,
    status: 'active',
    notes: ''
  }
}

const handleSaveSupplier = async () => {
  try {
    if (isEditMode.value) {
      await procurementService.updateSupplier(form.value.id, form.value)
    } else {
      await procurementService.createSupplier(form.value)
    }
    
    closeModal()
    await fetchSuppliers()
  } catch (error) {
    console.error('Failed to save supplier:', error)
    alert('Failed to save supplier. Please try again.')
  }
}

const viewSupplier = async (supplier) => {
  try {
    const response = await procurementService.getSupplier(supplier.id)
    selectedSupplier.value = response.data.data
    
    try {
      const metricsResponse = await procurementService.getSupplierMetrics(supplier.id)
      supplierMetrics.value = metricsResponse.data.data
    } catch (error) {
      console.warn('Failed to fetch supplier metrics:', error)
      supplierMetrics.value = null
    }
    
    showViewModal.value = true
  } catch (error) {
    console.error('Failed to fetch supplier details:', error)
  }
}

const editSupplier = async (supplier) => {
  try {
    const response = await procurementService.getSupplier(supplier.id)
    const data = response.data.data
    form.value = {
      id: data.id,
      name: data.name,
      supplier_code: data.supplier_code,
      contact_person: data.contact_person || '',
      email: data.email || '',
      phone: data.phone || '',
      mobile: data.mobile || '',
      address: data.address || '',
      city: data.city || '',
      state: data.state || '',
      postal_code: data.postal_code || '',
      country: data.country || '',
      tax_id: data.tax_id || '',
      payment_terms: data.payment_terms || '',
      credit_limit: data.credit_limit || 0,
      status: data.status,
      notes: data.notes || ''
    }
    isEditMode.value = true
    showModal.value = true
  } catch (error) {
    console.error('Failed to fetch supplier:', error)
  }
}

const deleteSupplierConfirm = async (supplier) => {
  if (confirm(`Are you sure you want to delete supplier ${supplier.name}? This action cannot be undone.`)) {
    try {
      await procurementService.deleteSupplier(supplier.id)
      await fetchSuppliers()
    } catch (error) {
      console.error('Failed to delete supplier:', error)
      alert('Failed to delete supplier. They may have associated purchase orders.')
    }
  }
}

const toggleStatus = async (supplier) => {
  const action = supplier.status === 'active' ? 'deactivate' : 'activate'
  if (confirm(`Are you sure you want to ${action} supplier ${supplier.name}?`)) {
    try {
      await procurementService.toggleSupplierStatus(supplier.id)
      await fetchSuppliers()
    } catch (error) {
      console.error('Failed to toggle supplier status:', error)
      alert('Failed to update supplier status. Please try again.')
    }
  }
}

const viewPurchaseOrders = (supplier) => {
  router.push({ name: 'PurchaseOrderList', query: { supplier_id: supplier.id } })
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount || 0)
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

.contact-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.rating {
  display: flex;
  gap: 2px;
}

.star-filled {
  color: #fbbf24;
}

.star-empty {
  color: #d1d5db;
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
  margin-top: 8px;
}

.section-header h4 {
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
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

.detail-code {
  font-size: 14px;
  color: #6b7280;
  font-family: monospace;
}

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}

.metric-card {
  background: #f9fafb;
  padding: 16px;
  border-radius: 8px;
  text-align: center;
}

.metric-label {
  font-size: 13px;
  color: #6b7280;
  margin-bottom: 8px;
}

.metric-value {
  font-size: 20px;
  font-weight: 700;
  color: #1f2937;
}

.detail-section {
  padding-top: 16px;
}

.detail-section h4 {
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 12px 0;
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

.detail-address p {
  margin: 4px 0;
  color: #1f2937;
}

.detail-notes {
  color: #4b5563;
  line-height: 1.6;
  margin: 0;
}
</style>
