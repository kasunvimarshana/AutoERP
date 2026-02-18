<template>
  <div class="account-list-view">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          Chart of Accounts
        </h1>
        <p class="mt-1 text-sm text-gray-600">
          Manage your accounting structure and account hierarchy
        </p>
      </div>
      <button
        v-if="hasPermission('accounting.accounts.create')"
        class="btn-primary"
        @click="createAccount"
      >
        <svg
          class="h-5 w-5 mr-2"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 4v16m8-8H4"
          />
        </svg>
        New Account
      </button>
    </div>

    <!-- Filters -->
    <div class="mb-6 bg-white rounded-lg shadow-sm p-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Search -->
        <div class="md:col-span-2">
          <label
            for="search"
            class="block text-sm font-medium text-gray-700 mb-1"
          >
            Search
          </label>
          <input
            id="search"
            v-model="filters.search"
            type="text"
            class="input"
            placeholder="Search by code, name..."
            @input="debouncedSearch"
          >
        </div>

        <!-- Type Filter -->
        <div>
          <label
            for="type"
            class="block text-sm font-medium text-gray-700 mb-1"
          >
            Account Type
          </label>
          <select
            id="type"
            v-model="filters.type"
            class="input"
            @change="fetchAccounts"
          >
            <option value="">
              All Types
            </option>
            <option value="asset">
              Asset
            </option>
            <option value="liability">
              Liability
            </option>
            <option value="equity">
              Equity
            </option>
            <option value="revenue">
              Revenue
            </option>
            <option value="expense">
              Expense
            </option>
            <option value="contra">
              Contra
            </option>
          </select>
        </div>

        <!-- Status Filter -->
        <div>
          <label
            for="status"
            class="block text-sm font-medium text-gray-700 mb-1"
          >
            Status
          </label>
          <select
            id="status"
            v-model="filters.is_active"
            class="input"
            @change="fetchAccounts"
          >
            <option :value="undefined">
              All Statuses
            </option>
            <option :value="true">
              Active
            </option>
            <option :value="false">
              Inactive
            </option>
          </select>
        </div>
      </div>

      <!-- View Toggle -->
      <div class="mt-4 flex items-center space-x-2">
        <button
          :class="[
            'px-3 py-1 text-sm rounded',
            viewMode === 'table' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700'
          ]"
          @click="viewMode = 'table'"
        >
          Table View
        </button>
        <button
          :class="[
            'px-3 py-1 text-sm rounded',
            viewMode === 'tree' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700'
          ]"
          @click="viewMode = 'tree'; fetchAccountTree()"
        >
          Tree View
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div
      v-if="loading"
      class="text-center py-12"
    >
      <div class="inline-block h-12 w-12 animate-spin rounded-full border-4 border-solid border-indigo-600 border-r-transparent" />
      <p class="mt-4 text-gray-600">
        Loading accounts...
      </p>
    </div>

    <!-- Error State -->
    <div
      v-else-if="error"
      class="rounded-md bg-red-50 p-4"
    >
      <div class="flex">
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">
            {{ error }}
          </h3>
        </div>
      </div>
    </div>

    <!-- Table View -->
    <div
      v-else-if="viewMode === 'table'"
      class="bg-white shadow-sm rounded-lg overflow-hidden"
    >
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Code
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Name
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Type
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Parent
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              Balance
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Status
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr
            v-for="account in accounts"
            :key="account.id"
            class="hover:bg-gray-50 cursor-pointer"
            @click="viewAccount(account.id)"
          >
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
              {{ account.code }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              {{ account.name }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
              <span
                :class="getTypeClass(account.type)"
                class="px-2 py-1 text-xs rounded-full"
              >
                {{ formatType(account.type) }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
              {{ account.parent_name || '-' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
              {{ formatCurrency(account.balance, account.currency) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span
                :class="getStatusClass(account.is_active)"
                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
              >
                {{ account.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <button
                v-if="hasPermission('accounting.accounts.update')"
                class="text-indigo-600 hover:text-indigo-900 mr-3"
                @click.stop="editAccount(account.id)"
              >
                Edit
              </button>
              <button
                v-if="hasPermission('accounting.accounts.delete')"
                class="text-red-600 hover:text-red-900"
                @click.stop="confirmDelete(account.id)"
              >
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div
        v-if="pagination.total > pagination.per_page"
        class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6"
      >
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-700">
            Showing {{ (pagination.current_page - 1) * pagination.per_page + 1 }} to
            {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} of
            {{ pagination.total }} results
          </div>
          <div class="flex space-x-2">
            <button
              :disabled="pagination.current_page === 1"
              class="btn-secondary disabled:opacity-50 disabled:cursor-not-allowed"
              @click="changePage(pagination.current_page - 1)"
            >
              Previous
            </button>
            <button
              :disabled="pagination.current_page === pagination.last_page"
              class="btn-secondary disabled:opacity-50 disabled:cursor-not-allowed"
              @click="changePage(pagination.current_page + 1)"
            >
              Next
            </button>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div
        v-if="accounts.length === 0"
        class="text-center py-12"
      >
        <svg
          class="mx-auto h-12 w-12 text-gray-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
          />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">
          No accounts found
        </h3>
        <p class="mt-1 text-sm text-gray-500">
          Get started by creating a new account.
        </p>
      </div>
    </div>

    <!-- Tree View -->
    <div
      v-else-if="viewMode === 'tree'"
      class="bg-white shadow-sm rounded-lg p-6"
    >
      <AccountTreeNode
        v-for="account in accountTree"
        :key="account.id"
        :account="account"
        :level="0"
        @view="viewAccount"
        @edit="editAccount"
        @delete="confirmDelete"
      />

      <div
        v-if="accountTree.length === 0"
        class="text-center py-12"
      >
        <p class="text-gray-500">
          No accounts found
        </p>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <ConfirmDialog
      v-if="showDeleteDialog"
      title="Delete Account"
      message="Are you sure you want to delete this account? This action cannot be undone."
      confirm-text="Delete"
      cancel-text="Cancel"
      @confirm="deleteAccount"
      @cancel="showDeleteDialog = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { accountingApi } from '@/api/accounting'
import type { Account, AccountQueryParams } from '@/types/accounting'
import { usePermissions } from '@/composables/usePermissions'
import AccountTreeNode from '@/components/accounting/AccountTreeNode.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'

const router = useRouter()
const { hasPermission } = usePermissions()

const accounts = ref<Account[]>([])
const accountTree = ref<Account[]>([])
const loading = ref(false)
const error = ref('')
const viewMode = ref<'table' | 'tree'>('table')
const showDeleteDialog = ref(false)
const accountToDelete = ref<number | null>(null)

const filters = reactive<AccountQueryParams>({
  search: '',
  type: undefined,
  is_active: undefined,
  page: 1,
  per_page: 15
})

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0
})

// Debounced search
let searchTimeout: ReturnType<typeof setTimeout>
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    filters.page = 1
    fetchAccounts()
  }, 300)
}

// Fetch accounts
const fetchAccounts = async () => {
  loading.value = true
  error.value = ''
  
  try {
    const response = await accountingApi.getAccounts(filters)
    accounts.value = response.data
    Object.assign(pagination, response.meta)
  } catch (err: any) {
    error.value = err.message || 'Failed to load accounts'
    console.error('Error fetching accounts:', err)
  } finally {
    loading.value = false
  }
}

// Fetch account tree
const fetchAccountTree = async () => {
  loading.value = true
  error.value = ''
  
  try {
    accountTree.value = await accountingApi.getAccountTree()
  } catch (err: any) {
    error.value = err.message || 'Failed to load account tree'
    console.error('Error fetching account tree:', err)
  } finally {
    loading.value = false
  }
}

// Navigation
const createAccount = () => {
  router.push({ name: 'accounting-account-create' })
}

const viewAccount = (id: number) => {
  router.push({ name: 'accounting-account-detail', params: { id } })
}

const editAccount = (id: number) => {
  router.push({ name: 'accounting-account-edit', params: { id } })
}

// Delete
const confirmDelete = (id: number) => {
  accountToDelete.value = id
  showDeleteDialog.value = true
}

const deleteAccount = async () => {
  if (!accountToDelete.value) return
  
  try {
    await accountingApi.deleteAccount(accountToDelete.value)
    showDeleteDialog.value = false
    accountToDelete.value = null
    
    if (viewMode.value === 'table') {
      await fetchAccounts()
    } else {
      await fetchAccountTree()
    }
  } catch (err: any) {
    error.value = err.message || 'Failed to delete account'
    console.error('Error deleting account:', err)
  }
}

// Pagination
const changePage = (page: number) => {
  filters.page = page
  fetchAccounts()
}

// Formatting helpers
const formatType = (type: string): string => {
  return type.charAt(0).toUpperCase() + type.slice(1)
}

const formatCurrency = (amount: number, currency: string = 'USD'): string => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency
  }).format(amount)
}

const getTypeClass = (type: string): string => {
  const classes: Record<string, string> = {
    asset: 'bg-blue-100 text-blue-800',
    liability: 'bg-red-100 text-red-800',
    equity: 'bg-purple-100 text-purple-800',
    revenue: 'bg-green-100 text-green-800',
    expense: 'bg-orange-100 text-orange-800',
    contra: 'bg-gray-100 text-gray-800'
  }
  return classes[type] || 'bg-gray-100 text-gray-800'
}

const getStatusClass = (isActive: boolean): string => {
  return isActive
    ? 'bg-green-100 text-green-800'
    : 'bg-gray-100 text-gray-800'
}

// Initialize
onMounted(() => {
  fetchAccounts()
})
</script>

<style scoped>
.btn-primary {
  @apply inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500;
}

.btn-secondary {
  @apply inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500;
}

.input {
  @apply block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm;
}
</style>
