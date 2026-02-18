<template>
  <div class="page-container">
    <div class="page-header">
      <h1 class="page-title">Customers</h1>
      <button @click="openCreateModal" class="btn btn-primary">
        <PlusIcon class="icon" />
        Add Customer
      </button>
    </div>
    
    <div class="filters-section">
      <input
        v-model="filters.search"
        type="text"
        placeholder="Search by name, email, phone, or company..."
        class="search-input"
        @input="fetchCustomers"
      />
      <div class="filter-group">
        <select v-model="filters.type" @change="fetchCustomers" class="filter-select">
          <option value="">All Types</option>
          <option value="individual">Individual</option>
          <option value="corporate">Corporate</option>
        </select>
        <select v-model="filters.status" @change="fetchCustomers" class="filter-select">
          <option value="">All Status</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>
    </div>
    
    <DataTable
      :columns="columns"
      :data="customers"
      :loading="loading"
      :current-page="currentPage"
      :total-pages="totalPages"
      @page-change="handlePageChange"
    >
      <template #cell-type="{ row }">
        <span class="badge badge-blue">{{ row.type }}</span>
      </template>
      
      <template #cell-status="{ row }">
        <span :class="['badge', row.status === 'active' ? 'badge-green' : 'badge-gray']">
          {{ row.status }}
        </span>
      </template>
      
      <template #actions="{ row }">
        <div class="action-buttons">
          <button @click="viewCustomer(row)" class="btn-icon" title="View">
            <EyeIcon class="icon" />
          </button>
          <button @click="editCustomer(row)" class="btn-icon" title="Edit">
            <PencilIcon class="icon" />
          </button>
          <button @click="viewOrders(row)" class="btn-icon" title="View Orders">
            <DocumentTextIcon class="icon" />
          </button>
          <button @click="deleteCustomer(row)" class="btn-icon btn-danger" title="Delete">
            <TrashIcon class="icon" />
          </button>
        </div>
      </template>
    </DataTable>
    
    <Modal
      :show="showModal"
      :title="isEditMode ? 'Edit Customer' : 'Create Customer'"
      size="large"
      @close="closeModal"
      @confirm="handleSaveCustomer"
    >
      <div class="form-section">
        <div class="form-grid">
          <FormInput
            v-model="form.name"
            label="Customer Name"
            placeholder="Enter customer name"
            required
          />
          <FormInput
            v-model="form.email"
            label="Email"
            type="email"
            placeholder="customer@example.com"
            required
          />
          <FormInput
            v-model="form.phone"
            label="Phone"
            placeholder="+1 (555) 000-0000"
            required
          />
          <FormInput
            v-model="form.company"
            label="Company"
            placeholder="Company name"
          />
          <FormSelect
            v-model="form.type"
            label="Customer Type"
            :options="typeOptions"
            required
          />
          <FormSelect
            v-model="form.status"
            label="Status"
            :options="statusOptions"
            required
          />
          <FormInput
            v-model.number="form.credit_limit"
            label="Credit Limit"
            type="number"
            step="0.01"
            min="0"
          />
          <FormInput
            v-model.number="form.payment_terms"
            label="Payment Terms (Days)"
            type="number"
            min="0"
          />
        </div>
        
        <FormTextarea
          v-model="form.address"
          label="Address"
          rows="3"
          placeholder="Enter full address"
        />
        
        <FormTextarea
          v-model="form.notes"
          label="Notes"
          rows="3"
          placeholder="Additional notes about the customer"
        />
      </div>
    </Modal>
    
    <Modal
      :show="showViewModal"
      title="Customer Details"
      size="large"
      hide-footer
      @close="showViewModal = false"
    >
      <div v-if="selectedCustomer" class="view-details">
        <div class="detail-grid">
          <div class="detail-item">
            <span class="detail-label">Name:</span>
            <span class="detail-value">{{ selectedCustomer.name }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Email:</span>
            <span class="detail-value">{{ selectedCustomer.email }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Phone:</span>
            <span class="detail-value">{{ selectedCustomer.phone }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Company:</span>
            <span class="detail-value">{{ selectedCustomer.company || 'N/A' }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Type:</span>
            <span class="badge badge-blue">{{ selectedCustomer.type }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Status:</span>
            <span :class="['badge', selectedCustomer.status === 'active' ? 'badge-green' : 'badge-gray']">
              {{ selectedCustomer.status }}
            </span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Credit Limit:</span>
            <span class="detail-value">{{ formatCurrency(selectedCustomer.credit_limit) }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Payment Terms:</span>
            <span class="detail-value">{{ selectedCustomer.payment_terms }} days</span>
          </div>
        </div>
        
        <div v-if="selectedCustomer.address" class="detail-section">
          <h4>Address</h4>
          <p>{{ selectedCustomer.address }}</p>
        </div>
        
        <div v-if="selectedCustomer.notes" class="detail-section">
          <h4>Notes</h4>
          <p>{{ selectedCustomer.notes }}</p>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { PlusIcon, PencilIcon, TrashIcon, EyeIcon, DocumentTextIcon } from '@heroicons/vue/24/outline'
import DataTable from '../../../components/common/DataTable.vue'
import Modal from '../../../components/common/Modal.vue'
import FormInput from '../../../components/forms/FormInput.vue'
import FormSelect from '../../../components/forms/FormSelect.vue'
import FormTextarea from '../../../components/forms/FormTextarea.vue'
import customerService from '../../../services/customerService'

const router = useRouter()
const loading = ref(false)
const showModal = ref(false)
const showViewModal = ref(false)
const isEditMode = ref(false)
const customers = ref([])
const selectedCustomer = ref(null)
const currentPage = ref(1)
const totalPages = ref(1)

const filters = ref({
  search: '',
  type: '',
  status: ''
})

const form = ref({
  name: '',
  email: '',
  phone: '',
  company: '',
  type: 'individual',
  status: 'active',
  credit_limit: 0,
  payment_terms: 30,
  address: '',
  notes: ''
})

const columns = [
  { key: 'name', label: 'Name', sortable: true },
  { key: 'email', label: 'Email', sortable: true },
  { key: 'phone', label: 'Phone' },
  { key: 'company', label: 'Company' },
  { key: 'type', label: 'Type' },
  { key: 'status', label: 'Status' }
]

const typeOptions = [
  { value: 'individual', label: 'Individual' },
  { value: 'corporate', label: 'Corporate' }
]

const statusOptions = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' }
]

onMounted(() => {
  fetchCustomers()
})

const fetchCustomers = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      search: filters.value.search,
      type: filters.value.type,
      status: filters.value.status
    }
    const response = await customerService.getAll(params)
    customers.value = response.data.data || []
    totalPages.value = response.data.meta?.last_page || 1
  } catch (error) {
    console.error('Failed to fetch customers:', error)
    customers.value = []
  } finally {
    loading.value = false
  }
}

const handlePageChange = (page) => {
  currentPage.value = page
  fetchCustomers()
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
    email: '',
    phone: '',
    company: '',
    type: 'individual',
    status: 'active',
    credit_limit: 0,
    payment_terms: 30,
    address: '',
    notes: ''
  }
}

const handleSaveCustomer = async () => {
  try {
    if (isEditMode.value) {
      await customerService.update(form.value.id, form.value)
    } else {
      await customerService.create(form.value)
    }
    closeModal()
    await fetchCustomers()
  } catch (error) {
    console.error('Failed to save customer:', error)
    alert('Failed to save customer. Please try again.')
  }
}

const viewCustomer = async (customer) => {
  try {
    const response = await customerService.getById(customer.id)
    selectedCustomer.value = response.data.data
    showViewModal.value = true
  } catch (error) {
    console.error('Failed to fetch customer details:', error)
  }
}

const editCustomer = async (customer) => {
  try {
    const response = await customerService.getById(customer.id)
    const data = response.data.data
    form.value = { ...data }
    isEditMode.value = true
    showModal.value = true
  } catch (error) {
    console.error('Failed to fetch customer:', error)
  }
}

const viewOrders = (customer) => {
  router.push({ name: 'SalesOrderList', query: { customer_id: customer.id } })
}

const deleteCustomer = async (customer) => {
  if (confirm(`Are you sure you want to delete ${customer.name}?`)) {
    try {
      await customerService.delete(customer.id)
      await fetchCustomers()
    } catch (error) {
      console.error('Failed to delete customer:', error)
      alert('Failed to delete customer. Please try again.')
    }
  }
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

.text-muted {
  color: #9ca3af;
  font-size: 14px;
}
</style>

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

.badge-blue {
  background: #dbeafe;
  color: #1e40af;
}

.badge-green {
  background: #dcfce7;
  color: #166534;
}

.badge-gray {
  background: #f3f4f6;
  color: #374151;
}

.form-section {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
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

.detail-section h4 {
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 12px;
}

.detail-section p {
  color: #4b5563;
  line-height: 1.6;
  margin: 0;
}
</style>
