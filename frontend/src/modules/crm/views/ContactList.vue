<template>
  <div class="page-container">
    <div class="page-header">
      <h1 class="page-title">Contacts</h1>
      <button @click="openCreateModal" class="btn btn-primary">
        <PlusIcon class="icon" />
        Add Contact
      </button>
    </div>
    
    <div class="filters-section">
      <input
        v-model="filters.search"
        type="text"
        placeholder="Search by name, email, or phone..."
        class="search-input"
        @input="fetchContacts"
      />
      <div class="filter-group">
        <select v-model="filters.customer_id" @change="fetchContacts" class="filter-select">
          <option value="">All Customers</option>
          <option v-for="customer in customers" :key="customer.id" :value="customer.id">
            {{ customer.name }}
          </option>
        </select>
        <select v-model="filters.status" @change="fetchContacts" class="filter-select">
          <option value="">All Status</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>
    </div>
    
    <DataTable
      :columns="columns"
      :data="contacts"
      :loading="loading"
      :current-page="currentPage"
      :total-pages="totalPages"
      @page-change="handlePageChange"
    >
      <template #cell-status="{ row }">
        <span :class="['badge', row.status === 'active' ? 'badge-green' : 'badge-gray']">
          {{ row.status }}
        </span>
      </template>
      
      <template #actions="{ row }">
        <div class="action-buttons">
          <button @click="viewContact(row)" class="btn-icon" title="View">
            <EyeIcon class="icon" />
          </button>
          <button @click="editContact(row)" class="btn-icon" title="Edit">
            <PencilIcon class="icon" />
          </button>
          <button @click="deleteContact(row)" class="btn-icon btn-danger" title="Delete">
            <TrashIcon class="icon" />
          </button>
        </div>
      </template>
    </DataTable>
    
    <Modal
      :show="showModal"
      :title="isEditMode ? 'Edit Contact' : 'Create Contact'"
      @close="closeModal"
      @confirm="handleSaveContact"
    >
      <div class="form-section">
        <div class="form-grid">
          <FormInput
            v-model="form.name"
            label="Contact Name"
            placeholder="Enter contact name"
            required
          />
          <FormInput
            v-model="form.email"
            label="Email"
            type="email"
            placeholder="contact@example.com"
            required
          />
          <FormInput
            v-model="form.phone"
            label="Phone"
            placeholder="+1 (555) 000-0000"
            required
          />
          <FormSelect
            v-model="form.customer_id"
            label="Customer"
            :options="customerOptions"
            required
          />
          <FormInput
            v-model="form.position"
            label="Position"
            placeholder="e.g., Sales Manager"
          />
          <FormInput
            v-model="form.department"
            label="Department"
            placeholder="e.g., Sales"
          />
          <FormSelect
            v-model="form.status"
            label="Status"
            :options="statusOptions"
            required
          />
        </div>
        
        <FormTextarea
          v-model="form.notes"
          label="Notes"
          rows="3"
          placeholder="Additional notes about the contact"
        />
      </div>
    </Modal>
    
    <Modal
      :show="showViewModal"
      title="Contact Details"
      hide-footer
      @close="showViewModal = false"
    >
      <div v-if="selectedContact" class="view-details">
        <div class="detail-grid">
          <div class="detail-item">
            <span class="detail-label">Name:</span>
            <span class="detail-value">{{ selectedContact.name }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Email:</span>
            <span class="detail-value">{{ selectedContact.email }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Phone:</span>
            <span class="detail-value">{{ selectedContact.phone }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Customer:</span>
            <span class="detail-value">{{ selectedContact.customer_name }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Position:</span>
            <span class="detail-value">{{ selectedContact.position || 'N/A' }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Department:</span>
            <span class="detail-value">{{ selectedContact.department || 'N/A' }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Status:</span>
            <span :class="['badge', selectedContact.status === 'active' ? 'badge-green' : 'badge-gray']">
              {{ selectedContact.status }}
            </span>
          </div>
        </div>
        
        <div v-if="selectedContact.notes" class="detail-section">
          <h4>Notes</h4>
          <p>{{ selectedContact.notes }}</p>
        </div>
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
import crmService from '../../../services/crmService'
import customerService from '../../../services/customerService'

const loading = ref(false)
const showModal = ref(false)
const showViewModal = ref(false)
const isEditMode = ref(false)
const contacts = ref([])
const customers = ref([])
const selectedContact = ref(null)
const currentPage = ref(1)
const totalPages = ref(1)

const filters = ref({
  search: '',
  customer_id: '',
  status: ''
})

const form = ref({
  name: '',
  email: '',
  phone: '',
  customer_id: '',
  position: '',
  department: '',
  status: 'active',
  notes: ''
})

const columns = [
  { key: 'name', label: 'Name', sortable: true },
  { key: 'email', label: 'Email', sortable: true },
  { key: 'phone', label: 'Phone' },
  { key: 'customer_name', label: 'Customer', sortable: true },
  { key: 'position', label: 'Position' },
  { key: 'status', label: 'Status' }
]

const statusOptions = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' }
]

const customerOptions = computed(() => 
  customers.value.map(c => ({ value: c.id, label: c.name }))
)

onMounted(async () => {
  await Promise.all([
    fetchContacts(),
    fetchCustomers()
  ])
})

const fetchContacts = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      search: filters.value.search,
      customer_id: filters.value.customer_id,
      status: filters.value.status
    }
    const response = await crmService.getContacts(params)
    contacts.value = response.data.data || []
    totalPages.value = response.data.meta?.last_page || 1
  } catch (error) {
    console.error('Failed to fetch contacts:', error)
    contacts.value = []
  } finally {
    loading.value = false
  }
}

const fetchCustomers = async () => {
  try {
    const response = await customerService.getAll({ status: 'active' })
    customers.value = response.data.data || []
  } catch (error) {
    console.error('Failed to fetch customers:', error)
  }
}

const handlePageChange = (page) => {
  currentPage.value = page
  fetchContacts()
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
    customer_id: '',
    position: '',
    department: '',
    status: 'active',
    notes: ''
  }
}

const handleSaveContact = async () => {
  try {
    if (isEditMode.value) {
      await crmService.updateContact(form.value.id, form.value)
    } else {
      await crmService.createContact(form.value)
    }
    closeModal()
    await fetchContacts()
  } catch (error) {
    console.error('Failed to save contact:', error)
    alert('Failed to save contact. Please try again.')
  }
}

const viewContact = async (contact) => {
  try {
    const response = await crmService.getContact(contact.id)
    selectedContact.value = response.data.data
    showViewModal.value = true
  } catch (error) {
    console.error('Failed to fetch contact details:', error)
  }
}

const editContact = async (contact) => {
  try {
    const response = await crmService.getContact(contact.id)
    const data = response.data.data
    form.value = { ...data }
    isEditMode.value = true
    showModal.value = true
  } catch (error) {
    console.error('Failed to fetch contact:', error)
  }
}

const deleteContact = async (contact) => {
  if (confirm(`Are you sure you want to delete ${contact.name}?`)) {
    try {
      await crmService.deleteContact(contact.id)
      await fetchContacts()
    } catch (error) {
      console.error('Failed to delete contact:', error)
      alert('Failed to delete contact. Please try again.')
    }
  }
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
