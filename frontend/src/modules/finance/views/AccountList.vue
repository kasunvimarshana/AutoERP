<template>
  <div class="page-container">
    <div class="page-header">
      <h1 class="page-title">Chart of Accounts</h1>
      <div class="header-actions">
        <button @click="toggleViewMode" class="btn btn-secondary">
          <component :is="viewMode === 'list' ? Squares2X2Icon : ListBulletIcon" class="icon" />
          {{ viewMode === 'list' ? 'Tree View' : 'List View' }}
        </button>
        <button @click="openCreateModal" class="btn btn-primary">
          <PlusIcon class="icon" />
          Add Account
        </button>
      </div>
    </div>
    
    <div class="filters-section">
      <input
        v-model="filters.search"
        type="text"
        placeholder="Search by account code, name..."
        class="search-input"
        @input="fetchAccounts"
      />
      <div class="filter-group">
        <select v-model="filters.account_type" @change="fetchAccounts" class="filter-select">
          <option value="">All Types</option>
          <option value="asset">Asset</option>
          <option value="liability">Liability</option>
          <option value="equity">Equity</option>
          <option value="revenue">Revenue</option>
          <option value="expense">Expense</option>
        </select>
        <select v-model="filters.status" @change="fetchAccounts" class="filter-select">
          <option value="">All Status</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>
    </div>
    
    <DataTable
      v-if="viewMode === 'list'"
      :columns="columns"
      :data="accounts"
      :loading="loading"
      :current-page="currentPage"
      :total-pages="totalPages"
      @page-change="handlePageChange"
    >
      <template #cell-account_code="{ row }">
        <span class="font-semibold text-blue-600">{{ row.account_code }}</span>
      </template>
      
      <template #cell-account_name="{ row }">
        <span :style="{ paddingLeft: (row.level || 0) * 20 + 'px' }">
          {{ row.account_name }}
        </span>
      </template>
      
      <template #cell-account_type="{ row }">
        <span :class="['badge', `badge-${getTypeColor(row.account_type)}`]">
          {{ row.account_type }}
        </span>
      </template>
      
      <template #cell-balance="{ row }">
        <span :class="getBalanceClass(row.balance)">
          {{ formatCurrency(row.balance || 0) }}
        </span>
      </template>
      
      <template #cell-status="{ row }">
        <span :class="['badge', `badge-${row.status === 'active' ? 'green' : 'gray'}`]">
          {{ row.status }}
        </span>
      </template>
      
      <template #actions="{ row }">
        <div class="action-buttons">
          <button @click="viewAccount(row)" class="btn-icon" title="View">
            <EyeIcon class="icon" />
          </button>
          <button @click="editAccount(row)" class="btn-icon" title="Edit">
            <PencilIcon class="icon" />
          </button>
          <button @click="viewTransactions(row)" class="btn-icon" title="View Transactions">
            <DocumentTextIcon class="icon" />
          </button>
          <button 
            @click="toggleAccountStatus(row)" 
            class="btn-icon" 
            :title="row.status === 'active' ? 'Deactivate' : 'Activate'"
          >
            <component :is="row.status === 'active' ? LockClosedIcon : LockOpenIcon" class="icon" />
          </button>
        </div>
      </template>
    </DataTable>
    
    <div v-else class="tree-view">
      <div v-for="account in treeAccounts" :key="account.id" class="tree-item">
        <AccountTreeNode 
          :account="account" 
          @view="viewAccount"
          @edit="editAccount"
          @view-transactions="viewTransactions"
          @toggle-status="toggleAccountStatus"
        />
      </div>
    </div>
    
    <Modal
      :show="showModal"
      :title="isEditMode ? 'Edit Account' : 'Create Account'"
      size="large"
      @close="closeModal"
      @confirm="handleSaveAccount"
    >
      <div class="form-section">
        <div class="form-grid">
          <FormInput
            v-model="form.account_code"
            label="Account Code"
            placeholder="e.g., 1000, 2000"
            required
          />
          <FormInput
            v-model="form.account_name"
            label="Account Name"
            placeholder="e.g., Cash, Accounts Receivable"
            required
          />
          <FormSelect
            v-model="form.account_type"
            label="Account Type"
            :options="accountTypeOptions"
            required
          />
          <FormSelect
            v-model="form.parent_account_id"
            label="Parent Account"
            :options="parentAccountOptions"
            placeholder="Select parent account (optional)"
          />
          <FormInput
            v-model="form.sub_type"
            label="Sub Type / Category"
            placeholder="e.g., Current Asset, Fixed Asset"
          />
          <FormSelect
            v-model="form.currency"
            label="Currency"
            :options="currencyOptions"
          />
          <FormInput
            v-model.number="form.opening_balance"
            label="Opening Balance"
            type="number"
            step="0.01"
          />
          <FormInput
            v-model="form.tax_code"
            label="Tax Code"
            placeholder="Optional"
          />
          <FormSelect
            v-model="form.status"
            label="Status"
            :options="statusOptions"
          />
        </div>
        
        <FormTextarea
          v-model="form.description"
          label="Description / Notes"
          rows="3"
          placeholder="Additional information about this account"
        />
      </div>
    </Modal>
    
    <Modal
      :show="showViewModal"
      title="Account Details"
      size="large"
      hide-footer
      @close="showViewModal = false"
    >
      <div v-if="selectedAccount" class="view-details">
        <div class="detail-grid">
          <div class="detail-item">
            <span class="detail-label">Account Code:</span>
            <span class="detail-value font-semibold">{{ selectedAccount.account_code }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Account Name:</span>
            <span class="detail-value">{{ selectedAccount.account_name }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Type:</span>
            <span :class="['badge', `badge-${getTypeColor(selectedAccount.account_type)}`]">
              {{ selectedAccount.account_type }}
            </span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Sub Type:</span>
            <span class="detail-value">{{ selectedAccount.sub_type || 'N/A' }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Parent Account:</span>
            <span class="detail-value">{{ selectedAccount.parent_account_name || 'None' }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Currency:</span>
            <span class="detail-value">{{ selectedAccount.currency }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Opening Balance:</span>
            <span class="detail-value">{{ formatCurrency(selectedAccount.opening_balance || 0) }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Current Balance:</span>
            <span class="detail-value font-semibold" :class="getBalanceClass(selectedAccount.balance)">
              {{ formatCurrency(selectedAccount.balance || 0) }}
            </span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Tax Code:</span>
            <span class="detail-value">{{ selectedAccount.tax_code || 'N/A' }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Status:</span>
            <span :class="['badge', `badge-${selectedAccount.status === 'active' ? 'green' : 'gray'}`]">
              {{ selectedAccount.status }}
            </span>
          </div>
        </div>
        
        <div v-if="selectedAccount.description" class="detail-section">
          <h4>Description</h4>
          <p>{{ selectedAccount.description }}</p>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { 
  PlusIcon, PencilIcon, EyeIcon, DocumentTextIcon, 
  LockClosedIcon, LockOpenIcon, Squares2X2Icon, ListBulletIcon
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
const accounts = ref([])
const selectedAccount = ref(null)
const currentPage = ref(1)
const totalPages = ref(1)
const viewMode = ref('list')

const filters = ref({
  search: '',
  account_type: '',
  status: ''
})

const form = ref({
  account_code: '',
  account_name: '',
  account_type: '',
  sub_type: '',
  parent_account_id: '',
  currency: 'USD',
  opening_balance: 0,
  tax_code: '',
  description: '',
  status: 'active'
})

const columns = [
  { key: 'account_code', label: 'Account Code', sortable: true },
  { key: 'account_name', label: 'Account Name', sortable: true },
  { key: 'account_type', label: 'Type', sortable: true },
  { key: 'parent_account_name', label: 'Parent Account' },
  { key: 'balance', label: 'Balance', sortable: true },
  { key: 'status', label: 'Status' }
]

const accountTypeOptions = [
  { value: 'asset', label: 'Asset' },
  { value: 'liability', label: 'Liability' },
  { value: 'equity', label: 'Equity' },
  { value: 'revenue', label: 'Revenue' },
  { value: 'expense', label: 'Expense' }
]

const statusOptions = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' }
]

const currencyOptions = [
  { value: 'USD', label: 'USD - US Dollar' },
  { value: 'EUR', label: 'EUR - Euro' },
  { value: 'GBP', label: 'GBP - British Pound' },
  { value: 'JPY', label: 'JPY - Japanese Yen' }
]

const parentAccountOptions = computed(() => {
  return [
    { value: '', label: 'None (Top Level)' },
    ...accounts.value
      .filter(a => !isEditMode.value || a.id !== form.value.id)
      .map(a => ({ value: a.id, label: `${a.account_code} - ${a.account_name}` }))
  ]
})

const treeAccounts = computed(() => {
  return buildAccountTree(accounts.value)
})

const buildAccountTree = (accountsList) => {
  const map = {}
  const roots = []
  
  accountsList.forEach(account => {
    map[account.id] = { ...account, children: [] }
  })
  
  accountsList.forEach(account => {
    if (account.parent_account_id && map[account.parent_account_id]) {
      map[account.parent_account_id].children.push(map[account.id])
    } else {
      roots.push(map[account.id])
    }
  })
  
  return roots
}

onMounted(() => {
  fetchAccounts()
})

const fetchAccounts = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      search: filters.value.search,
      account_type: filters.value.account_type,
      status: filters.value.status
    }
    const response = await financeService.getAccounts(params)
    accounts.value = response.data.data || []
    totalPages.value = response.data.meta?.last_page || 1
  } catch (error) {
    console.error('Failed to fetch accounts:', error)
    accounts.value = []
  } finally {
    loading.value = false
  }
}

const handlePageChange = (page) => {
  currentPage.value = page
  fetchAccounts()
}

const toggleViewMode = () => {
  viewMode.value = viewMode.value === 'list' ? 'tree' : 'list'
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
    account_code: '',
    account_name: '',
    account_type: '',
    sub_type: '',
    parent_account_id: '',
    currency: 'USD',
    opening_balance: 0,
    tax_code: '',
    description: '',
    status: 'active'
  }
}

const handleSaveAccount = async () => {
  try {
    if (isEditMode.value) {
      await financeService.updateAccount(form.value.id, form.value)
    } else {
      await financeService.createAccount(form.value)
    }
    
    closeModal()
    await fetchAccounts()
  } catch (error) {
    console.error('Failed to save account:', error)
    alert('Failed to save account. Please try again.')
  }
}

const viewAccount = async (account) => {
  try {
    const response = await financeService.getAccount(account.id)
    selectedAccount.value = response.data.data
    showViewModal.value = true
  } catch (error) {
    console.error('Failed to fetch account details:', error)
  }
}

const editAccount = async (account) => {
  try {
    const response = await financeService.getAccount(account.id)
    const data = response.data.data
    form.value = {
      id: data.id,
      account_code: data.account_code,
      account_name: data.account_name,
      account_type: data.account_type,
      sub_type: data.sub_type || '',
      parent_account_id: data.parent_account_id || '',
      currency: data.currency || 'USD',
      opening_balance: data.opening_balance || 0,
      tax_code: data.tax_code || '',
      description: data.description || '',
      status: data.status
    }
    isEditMode.value = true
    showModal.value = true
  } catch (error) {
    console.error('Failed to fetch account:', error)
  }
}

const viewTransactions = (account) => {
  alert(`View transactions for account ${account.account_code} - ${account.account_name}`)
}

const toggleAccountStatus = async (account) => {
  const newStatus = account.status === 'active' ? 'inactive' : 'active'
  if (confirm(`Are you sure you want to ${newStatus === 'inactive' ? 'deactivate' : 'activate'} this account?`)) {
    try {
      await financeService.updateAccount(account.id, { status: newStatus })
      await fetchAccounts()
    } catch (error) {
      console.error('Failed to update account status:', error)
      alert('Failed to update account status. Please try again.')
    }
  }
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount || 0)
}

const getTypeColor = (type) => {
  const colors = {
    asset: 'blue',
    liability: 'red',
    equity: 'purple',
    revenue: 'green',
    expense: 'orange'
  }
  return colors[type] || 'gray'
}

const getBalanceClass = (balance) => {
  if (!balance || balance === 0) return 'text-gray-600'
  return balance > 0 ? 'text-green-600' : 'text-red-600'
}

const AccountTreeNode = {
  name: 'AccountTreeNode',
  props: ['account'],
  emits: ['view', 'edit', 'view-transactions', 'toggle-status'],
  template: `
    <div class="tree-node">
      <div class="node-content">
        <span class="node-code">{{ account.account_code }}</span>
        <span class="node-name">{{ account.account_name }}</span>
        <span :class="['badge', 'badge-sm', 'badge-' + getTypeColor(account.account_type)]">
          {{ account.account_type }}
        </span>
        <span class="node-balance">{{ formatCurrency(account.balance || 0) }}</span>
        <div class="node-actions">
          <button @click="$emit('view', account)" class="btn-icon-sm" title="View">
            <svg class="icon-sm" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
          </button>
          <button @click="$emit('edit', account)" class="btn-icon-sm" title="Edit">
            <svg class="icon-sm" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
          </button>
        </div>
      </div>
      <div v-if="account.children && account.children.length" class="node-children">
        <AccountTreeNode 
          v-for="child in account.children" 
          :key="child.id" 
          :account="child"
          @view="$emit('view', $event)"
          @edit="$emit('edit', $event)"
          @view-transactions="$emit('view-transactions', $event)"
          @toggle-status="$emit('toggle-status', $event)"
        />
      </div>
    </div>
  `,
  methods: {
    formatCurrency: (amount) => {
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
      }).format(amount || 0)
    },
    getTypeColor: (type) => {
      const colors = {
        asset: 'blue',
        liability: 'red',
        equity: 'purple',
        revenue: 'green',
        expense: 'orange'
      }
      return colors[type] || 'gray'
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

.header-actions {
  display: flex;
  gap: 12px;
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

.icon {
  width: 20px;
  height: 20px;
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
.badge-purple { background: #e9d5ff; color: #6b21a8; }
.badge-gray { background: #f3f4f6; color: #374151; }

.font-semibold {
  font-weight: 600;
}

.text-blue-600 { color: #2563eb; }
.text-green-600 { color: #16a34a; }
.text-red-600 { color: #dc2626; }
.text-gray-600 { color: #4b5563; }

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
  margin-bottom: 8px;
}

.detail-section p {
  font-size: 14px;
  color: #6b7280;
  line-height: 1.6;
}

.tree-view {
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  padding: 16px;
}

.tree-node {
  margin-bottom: 4px;
}

.node-content {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px;
  background: #f9fafb;
  border-radius: 6px;
  transition: background 0.2s;
}

.node-content:hover {
  background: #f3f4f6;
}

.node-code {
  font-weight: 600;
  color: #3b82f6;
  min-width: 80px;
}

.node-name {
  flex: 1;
  color: #1f2937;
}

.node-balance {
  font-weight: 500;
  color: #4b5563;
  min-width: 120px;
  text-align: right;
}

.node-actions {
  display: flex;
  gap: 4px;
}

.btn-icon-sm {
  padding: 4px;
  background: none;
  border: none;
  cursor: pointer;
  color: #6b7280;
  transition: color 0.2s;
}

.btn-icon-sm:hover {
  color: #3b82f6;
}

.icon-sm {
  width: 16px;
  height: 16px;
}

.badge-sm {
  padding: 2px 8px;
  font-size: 11px;
}

.node-children {
  margin-left: 40px;
  margin-top: 4px;
}
</style>
