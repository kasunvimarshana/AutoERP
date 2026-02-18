<template>
  <div class="page-container">
    <div class="page-header">
      <h1 class="page-title">Journal Entries</h1>
      <button @click="openCreateModal" class="btn btn-primary">
        <PlusIcon class="icon" />
        Create Journal Entry
      </button>
    </div>
    
    <div class="filters-section">
      <input
        v-model="filters.search"
        type="text"
        placeholder="Search by entry number, reference, description..."
        class="search-input"
        @input="fetchJournalEntries"
      />
      <div class="filter-group">
        <select v-model="filters.status" @change="fetchJournalEntries" class="filter-select">
          <option value="">All Status</option>
          <option value="draft">Draft</option>
          <option value="posted">Posted</option>
          <option value="approved">Approved</option>
          <option value="reversed">Reversed</option>
        </select>
        <input
          v-model="filters.dateFrom"
          type="date"
          class="filter-input"
          @change="fetchJournalEntries"
          placeholder="From Date"
        />
        <input
          v-model="filters.dateTo"
          type="date"
          class="filter-input"
          @change="fetchJournalEntries"
          placeholder="To Date"
        />
      </div>
    </div>
    
    <DataTable
      :columns="columns"
      :data="journalEntries"
      :loading="loading"
      :current-page="currentPage"
      :total-pages="totalPages"
      @page-change="handlePageChange"
    >
      <template #cell-entry_number="{ row }">
        <span class="font-semibold text-blue-600">{{ row.entry_number }}</span>
      </template>
      
      <template #cell-entry_date="{ row }">
        {{ formatDate(row.entry_date) }}
      </template>
      
      <template #cell-total_debit="{ row }">
        {{ formatCurrency(row.total_debit) }}
      </template>
      
      <template #cell-total_credit="{ row }">
        {{ formatCurrency(row.total_credit) }}
      </template>
      
      <template #cell-balanced="{ row }">
        <span :class="['badge', isBalanced(row) ? 'badge-green' : 'badge-red']">
          {{ isBalanced(row) ? 'Balanced' : 'Unbalanced' }}
        </span>
      </template>
      
      <template #cell-status="{ row }">
        <span :class="['badge', `badge-${getStatusColor(row.status)}`]">
          {{ row.status }}
        </span>
      </template>
      
      <template #actions="{ row }">
        <div class="action-buttons">
          <button @click="viewJournalEntry(row)" class="btn-icon" title="View">
            <EyeIcon class="icon" />
          </button>
          <button 
            v-if="row.status === 'draft'"
            @click="editJournalEntry(row)" 
            class="btn-icon" 
            title="Edit"
          >
            <PencilIcon class="icon" />
          </button>
          <button 
            v-if="row.status === 'draft' && isBalanced(row)"
            @click="postJournalEntry(row)" 
            class="btn-icon btn-success" 
            title="Post"
          >
            <CheckCircleIcon class="icon" />
          </button>
          <button 
            v-if="row.status === 'posted'"
            @click="approveJournalEntry(row)" 
            class="btn-icon btn-success" 
            title="Approve"
          >
            <ShieldCheckIcon class="icon" />
          </button>
          <button 
            v-if="['posted', 'approved'].includes(row.status)"
            @click="reverseJournalEntry(row)" 
            class="btn-icon btn-warning" 
            title="Reverse"
          >
            <ArrowUturnLeftIcon class="icon" />
          </button>
          <button @click="printJournalEntry(row)" class="btn-icon" title="Print">
            <PrinterIcon class="icon" />
          </button>
        </div>
      </template>
    </DataTable>
    
    <Modal
      :show="showModal"
      :title="isEditMode ? 'Edit Journal Entry' : 'Create Journal Entry'"
      size="large"
      @close="closeModal"
      @confirm="handleSaveJournalEntry"
    >
      <div class="form-section">
        <div class="form-grid">
          <FormInput
            v-model="form.entry_date"
            label="Entry Date"
            type="date"
            required
          />
          <FormInput
            v-model="form.reference"
            label="Reference Number"
            placeholder="e.g., INV-001, PO-123"
          />
        </div>
        
        <FormTextarea
          v-model="form.description"
          label="Description"
          rows="2"
          placeholder="Brief description of this journal entry"
          required
        />
        
        <div class="lines-section">
          <div class="section-header">
            <h4>Journal Entry Lines</h4>
            <button @click="addLine" type="button" class="btn btn-sm btn-secondary">
              <PlusIcon class="icon-sm" />
              Add Line
            </button>
          </div>
          
          <div class="balance-indicator" :class="{ 'balanced': isFormBalanced, 'unbalanced': !isFormBalanced }">
            <div class="balance-item">
              <span class="balance-label">Total Debit:</span>
              <span class="balance-value">{{ formatCurrency(calculateTotalDebit()) }}</span>
            </div>
            <div class="balance-item">
              <span class="balance-label">Total Credit:</span>
              <span class="balance-value">{{ formatCurrency(calculateTotalCredit()) }}</span>
            </div>
            <div class="balance-item">
              <span class="balance-label">Difference:</span>
              <span class="balance-value" :class="{ 'text-red-600': !isFormBalanced }">
                {{ formatCurrency(Math.abs(calculateTotalDebit() - calculateTotalCredit())) }}
              </span>
            </div>
            <div class="balance-status">
              <span v-if="isFormBalanced" class="status-balanced">✓ Balanced</span>
              <span v-else class="status-unbalanced">⚠ Unbalanced</span>
            </div>
          </div>
          
          <div v-for="(line, index) in form.lines" :key="index" class="line-row">
            <div class="line-number">{{ index + 1 }}</div>
            <div class="line-fields">
              <FormSelect
                v-model="line.account_id"
                label="Account"
                :options="accountOptions"
                required
              />
              <FormInput
                v-model.number="line.debit"
                label="Debit"
                type="number"
                step="0.01"
                min="0"
                @input="handleDebitChange(line)"
              />
              <FormInput
                v-model.number="line.credit"
                label="Credit"
                type="number"
                step="0.01"
                min="0"
                @input="handleCreditChange(line)"
              />
              <FormInput
                v-model="line.description"
                label="Description"
                placeholder="Line description"
              />
              <FormInput
                v-model="line.cost_center"
                label="Cost Center"
                placeholder="Optional"
              />
            </div>
            <button @click="removeLine(index)" type="button" class="btn-icon btn-danger" title="Remove">
              <TrashIcon class="icon" />
            </button>
          </div>
          
          <div v-if="form.lines.length === 0" class="empty-lines">
            <p>No lines added yet. Click "Add Line" to add journal entry lines.</p>
          </div>
        </div>
        
        <FormTextarea
          v-model="form.notes"
          label="Notes"
          rows="3"
          placeholder="Additional notes or attachments information"
        />
      </div>
    </Modal>
    
    <Modal
      :show="showViewModal"
      title="Journal Entry Details"
      size="large"
      hide-footer
      @close="showViewModal = false"
    >
      <div v-if="selectedEntry" class="view-details">
        <div class="detail-grid">
          <div class="detail-item">
            <span class="detail-label">Entry Number:</span>
            <span class="detail-value font-semibold">{{ selectedEntry.entry_number }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Entry Date:</span>
            <span class="detail-value">{{ formatDate(selectedEntry.entry_date) }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Reference:</span>
            <span class="detail-value">{{ selectedEntry.reference || 'N/A' }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Status:</span>
            <span :class="['badge', `badge-${getStatusColor(selectedEntry.status)}`]">
              {{ selectedEntry.status }}
            </span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Posted By:</span>
            <span class="detail-value">{{ selectedEntry.posted_by || 'N/A' }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Posted At:</span>
            <span class="detail-value">{{ selectedEntry.posted_at ? formatDate(selectedEntry.posted_at) : 'N/A' }}</span>
          </div>
        </div>
        
        <div class="detail-section">
          <h4>Description</h4>
          <p>{{ selectedEntry.description }}</p>
        </div>
        
        <div class="detail-section">
          <h4>Journal Entry Lines</h4>
          <table class="detail-table">
            <thead>
              <tr>
                <th>Account</th>
                <th>Description</th>
                <th>Cost Center</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Credit</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="line in selectedEntry.lines" :key="line.id">
                <td>{{ line.account_code }} - {{ line.account_name }}</td>
                <td>{{ line.description || '-' }}</td>
                <td>{{ line.cost_center || '-' }}</td>
                <td class="text-right">{{ line.debit ? formatCurrency(line.debit) : '-' }}</td>
                <td class="text-right">{{ line.credit ? formatCurrency(line.credit) : '-' }}</td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="totals-row">
                <td colspan="3" class="text-right"><strong>Total:</strong></td>
                <td class="text-right"><strong>{{ formatCurrency(selectedEntry.total_debit) }}</strong></td>
                <td class="text-right"><strong>{{ formatCurrency(selectedEntry.total_credit) }}</strong></td>
              </tr>
            </tfoot>
          </table>
        </div>
        
        <div v-if="selectedEntry.notes" class="detail-section">
          <h4>Notes</h4>
          <p>{{ selectedEntry.notes }}</p>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { 
  PlusIcon, PencilIcon, TrashIcon, EyeIcon, CheckCircleIcon,
  ShieldCheckIcon, ArrowUturnLeftIcon, PrinterIcon
} from '@heroicons/vue/24/outline'
import DataTable from '../../../components/common/DataTable.vue'
import Modal from '../../../components/common/Modal.vue'
import FormInput from '../../../components/forms/FormInput.vue'
import FormSelect from '../../../components/forms/FormSelect.vue'
import FormTextarea from '../../../components/forms/FormTextarea.vue'
import financeService from '../../../services/financeService'

const loading = ref(false)
const showModal = ref(false)
const showViewModal = ref(false)
const isEditMode = ref(false)
const journalEntries = ref([])
const selectedEntry = ref(null)
const currentPage = ref(1)
const totalPages = ref(1)
const accounts = ref([])

const filters = ref({
  search: '',
  status: '',
  dateFrom: '',
  dateTo: ''
})

const form = ref({
  entry_date: new Date().toISOString().split('T')[0],
  reference: '',
  description: '',
  notes: '',
  lines: []
})

const columns = [
  { key: 'entry_number', label: 'Entry #', sortable: true },
  { key: 'entry_date', label: 'Entry Date', sortable: true },
  { key: 'reference', label: 'Reference' },
  { key: 'description', label: 'Description' },
  { key: 'total_debit', label: 'Total Debit', sortable: true },
  { key: 'total_credit', label: 'Total Credit', sortable: true },
  { key: 'balanced', label: 'Balance Status' },
  { key: 'status', label: 'Status' }
]

const accountOptions = computed(() => 
  accounts.value.map(a => ({ 
    value: a.id, 
    label: `${a.account_code} - ${a.account_name}` 
  }))
)

const isFormBalanced = computed(() => {
  const debit = calculateTotalDebit()
  const credit = calculateTotalCredit()
  return Math.abs(debit - credit) < 0.01
})

onMounted(async () => {
  await Promise.all([
    fetchJournalEntries(),
    fetchAccounts()
  ])
})

const fetchJournalEntries = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      search: filters.value.search,
      status: filters.value.status,
      date_from: filters.value.dateFrom,
      date_to: filters.value.dateTo
    }
    const response = await financeService.getJournalEntries(params)
    journalEntries.value = response.data.data || []
    totalPages.value = response.data.meta?.last_page || 1
  } catch (error) {
    console.error('Failed to fetch journal entries:', error)
    journalEntries.value = []
  } finally {
    loading.value = false
  }
}

const fetchAccounts = async () => {
  try {
    const response = await financeService.getAccounts({ status: 'active' })
    accounts.value = response.data.data || []
  } catch (error) {
    console.error('Failed to fetch accounts:', error)
  }
}

const handlePageChange = (page) => {
  currentPage.value = page
  fetchJournalEntries()
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
    entry_date: new Date().toISOString().split('T')[0],
    reference: '',
    description: '',
    notes: '',
    lines: []
  }
}

const addLine = () => {
  form.value.lines.push({
    account_id: '',
    debit: 0,
    credit: 0,
    description: '',
    cost_center: ''
  })
}

const removeLine = (index) => {
  form.value.lines.splice(index, 1)
}

const handleDebitChange = (line) => {
  if (line.debit > 0) {
    line.credit = 0
  }
}

const handleCreditChange = (line) => {
  if (line.credit > 0) {
    line.debit = 0
  }
}

const calculateTotalDebit = () => {
  return form.value.lines.reduce((sum, line) => sum + (parseFloat(line.debit) || 0), 0)
}

const calculateTotalCredit = () => {
  return form.value.lines.reduce((sum, line) => sum + (parseFloat(line.credit) || 0), 0)
}

const handleSaveJournalEntry = async () => {
  if (!isFormBalanced.value) {
    alert('Journal entry must be balanced. Total debit must equal total credit.')
    return
  }
  
  if (form.value.lines.length < 2) {
    alert('Journal entry must have at least 2 lines.')
    return
  }
  
  try {
    const data = {
      ...form.value,
      total_debit: calculateTotalDebit(),
      total_credit: calculateTotalCredit()
    }
    
    if (isEditMode.value) {
      await financeService.updateJournalEntry(form.value.id, data)
    } else {
      await financeService.createJournalEntry(data)
    }
    
    closeModal()
    await fetchJournalEntries()
  } catch (error) {
    console.error('Failed to save journal entry:', error)
    alert('Failed to save journal entry. Please try again.')
  }
}

const viewJournalEntry = async (entry) => {
  try {
    const response = await financeService.getJournalEntry(entry.id)
    selectedEntry.value = response.data.data
    showViewModal.value = true
  } catch (error) {
    console.error('Failed to fetch journal entry details:', error)
  }
}

const editJournalEntry = async (entry) => {
  try {
    const response = await financeService.getJournalEntry(entry.id)
    const data = response.data.data
    form.value = {
      id: data.id,
      entry_date: data.entry_date,
      reference: data.reference || '',
      description: data.description,
      notes: data.notes || '',
      lines: data.lines || []
    }
    isEditMode.value = true
    showModal.value = true
  } catch (error) {
    console.error('Failed to fetch journal entry:', error)
  }
}

const postJournalEntry = async (entry) => {
  if (confirm(`Post journal entry ${entry.entry_number}? This action cannot be undone.`)) {
    try {
      await financeService.updateJournalEntry(entry.id, { status: 'posted' })
      await fetchJournalEntries()
      alert('Journal entry posted successfully!')
    } catch (error) {
      console.error('Failed to post journal entry:', error)
      alert('Failed to post journal entry. Please try again.')
    }
  }
}

const approveJournalEntry = async (entry) => {
  if (confirm(`Approve journal entry ${entry.entry_number}?`)) {
    try {
      await financeService.updateJournalEntry(entry.id, { status: 'approved' })
      await fetchJournalEntries()
      alert('Journal entry approved successfully!')
    } catch (error) {
      console.error('Failed to approve journal entry:', error)
      alert('Failed to approve journal entry. Please try again.')
    }
  }
}

const reverseJournalEntry = async (entry) => {
  if (confirm(`Reverse journal entry ${entry.entry_number}? This will create a reversing entry.`)) {
    try {
      await financeService.createJournalEntry({
        entry_date: new Date().toISOString().split('T')[0],
        reference: `REV-${entry.entry_number}`,
        description: `Reversal of ${entry.entry_number}: ${entry.description}`,
        lines: entry.lines.map(line => ({
          account_id: line.account_id,
          debit: line.credit || 0,
          credit: line.debit || 0,
          description: `Reversal: ${line.description || ''}`,
          cost_center: line.cost_center
        }))
      })
      
      await financeService.updateJournalEntry(entry.id, { status: 'reversed' })
      await fetchJournalEntries()
      alert('Journal entry reversed successfully!')
    } catch (error) {
      console.error('Failed to reverse journal entry:', error)
      alert('Failed to reverse journal entry. Please try again.')
    }
  }
}

const printJournalEntry = (entry) => {
  alert(`Print functionality for journal entry ${entry.entry_number} would be implemented here.`)
}

const isBalanced = (entry) => {
  return Math.abs((entry.total_debit || 0) - (entry.total_credit || 0)) < 0.01
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
    posted: 'blue',
    approved: 'green',
    reversed: 'orange'
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
  padding: 6px;
  background: none;
  border: none;
  cursor: pointer;
  color: #6b7280;
  transition: color 0.2s;
}

.btn-icon:hover {
  color: #3b82f6;
}

.btn-icon.btn-success:hover {
  color: #16a34a;
}

.btn-icon.btn-warning:hover {
  color: #ea580c;
}

.btn-icon.btn-danger:hover {
  color: #dc2626;
}

.btn-icon .icon {
  width: 18px;
  height: 18px;
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

.lines-section {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 16px;
  background: #f9fafb;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.section-header h4 {
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
}

.balance-indicator {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
  padding: 12px;
  background: white;
  border-radius: 6px;
  margin-bottom: 16px;
  border: 2px solid #d1d5db;
}

.balance-indicator.balanced {
  border-color: #16a34a;
  background: #f0fdf4;
}

.balance-indicator.unbalanced {
  border-color: #dc2626;
  background: #fef2f2;
}

.balance-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.balance-label {
  font-size: 12px;
  color: #6b7280;
  font-weight: 500;
}

.balance-value {
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
}

.balance-status {
  display: flex;
  align-items: center;
  justify-content: center;
}

.status-balanced {
  color: #16a34a;
  font-weight: 600;
  font-size: 14px;
}

.status-unbalanced {
  color: #dc2626;
  font-weight: 600;
  font-size: 14px;
}

.line-row {
  display: flex;
  gap: 12px;
  background: white;
  padding: 12px;
  border-radius: 6px;
  margin-bottom: 12px;
  align-items: start;
}

.line-number {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  background: #3b82f6;
  color: white;
  border-radius: 50%;
  font-weight: 600;
  font-size: 14px;
  flex-shrink: 0;
  margin-top: 28px;
}

.line-fields {
  flex: 1;
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 2fr 1fr;
  gap: 12px;
}

.empty-lines {
  text-align: center;
  padding: 32px;
  color: #9ca3af;
  background: white;
  border-radius: 6px;
}

.badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
  text-transform: capitalize;
}

.badge-blue { background: #dbeafe; color: #1e40af; }
.badge-red { background: #fee2e2; color: #991b1b; }
.badge-green { background: #dcfce7; color: #166534; }
.badge-orange { background: #fed7aa; color: #9a3412; }
.badge-gray { background: #f3f4f6; color: #374151; }

.font-semibold {
  font-weight: 600;
}

.text-blue-600 { color: #2563eb; }
.text-red-600 { color: #dc2626; }

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
  font-size: 12px;
  color: #6b7280;
  font-weight: 500;
}

.detail-value {
  font-size: 14px;
  color: #1f2937;
}

.detail-section {
  border-top: 1px solid #e5e7eb;
  padding-top: 16px;
}

.detail-section h4 {
  font-size: 14px;
  font-weight: 600;
  color: #374151;
  margin-bottom: 12px;
}

.detail-section p {
  font-size: 14px;
  color: #6b7280;
  line-height: 1.6;
}

.detail-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

.detail-table th,
.detail-table td {
  padding: 10px;
  text-align: left;
  border-bottom: 1px solid #e5e7eb;
}

.detail-table th {
  background: #f9fafb;
  font-weight: 600;
  color: #374151;
}

.detail-table .text-right {
  text-align: right;
}

.detail-table tfoot {
  font-weight: 600;
  background: #f9fafb;
}

.totals-row td {
  padding-top: 12px;
}
</style>
