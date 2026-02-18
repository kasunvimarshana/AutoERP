<template>
  <div class="account-detail">
    <!-- Loading State -->
    <div
      v-if="loading"
      class="text-center py-12"
    >
      <div class="inline-block h-12 w-12 animate-spin rounded-full border-4 border-solid border-indigo-600 border-r-transparent" />
      <p class="mt-4 text-gray-600">
        Loading account details...
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

    <!-- Account Details -->
    <div v-else-if="account">
      <!-- Header -->
      <div class="mb-6 flex items-center justify-between">
        <div class="flex-1">
          <div class="flex items-center space-x-4">
            <h1 class="text-2xl font-bold text-gray-900">
              {{ account.name }}
            </h1>
            <span
              :class="getStatusClass(account.is_active)"
              class="rounded-full px-3 py-1 text-sm font-medium"
            >
              {{ account.is_active ? 'Active' : 'Inactive' }}
            </span>
            <span
              :class="getTypeClass(account.type)"
              class="rounded-full px-3 py-1 text-sm font-medium"
            >
              {{ account.type.toUpperCase() }}
            </span>
            <span
              v-if="account.is_header"
              class="rounded-full bg-purple-100 text-purple-800 px-3 py-1 text-sm font-medium"
            >
              HEADER
            </span>
          </div>
          <p class="mt-1 text-sm text-gray-600">
            Account Code: {{ account.code }}
          </p>
        </div>
        <div class="flex items-center space-x-3">
          <button
            class="btn-secondary"
            @click="goBack"
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
                d="M10 19l-7-7m0 0l7-7m-7 7h18"
              />
            </svg>
            Back
          </button>
          <button
            v-if="canEdit"
            class="btn-primary"
            @click="editAccount"
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
                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
              />
            </svg>
            Edit
          </button>
        </div>
      </div>

      <!-- Main Content -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Main Information -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Account Information Card -->
          <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Account Information
            </h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Account Code
                </dt>
                <dd class="mt-1 text-sm text-gray-900 font-mono">
                  {{ account.code }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Account Type
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatAccountType(account.type) }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Parent Account
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ account.parent_code ? `${account.parent_code} - ${account.parent_name}` : 'N/A' }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Account Level
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  Level {{ account.level }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Currency
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ account.currency }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Header Account
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ account.is_header ? 'Yes' : 'No' }}
                </dd>
              </div>
            </dl>
          </div>

          <!-- Balance Information Card -->
          <div
            v-if="!account.is_header"
            class="bg-white shadow-sm rounded-lg p-6"
          >
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Balance Information
            </h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Current Balance
                </dt>
                <dd class="mt-1 text-lg font-semibold text-gray-900">
                  {{ formatCurrency(account.balance) }}
                </dd>
              </div>
              <div v-if="account.debit_balance !== undefined">
                <dt class="text-sm font-medium text-gray-500">
                  Debit Balance
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatCurrency(account.debit_balance) }}
                </dd>
              </div>
              <div v-if="account.credit_balance !== undefined">
                <dt class="text-sm font-medium text-gray-500">
                  Credit Balance
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatCurrency(account.credit_balance) }}
                </dd>
              </div>
            </dl>
          </div>

          <!-- Description Card -->
          <div
            v-if="account.description"
            class="bg-white shadow-sm rounded-lg p-6"
          >
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Description
            </h2>
            <p class="text-sm text-gray-700 whitespace-pre-wrap">
              {{ account.description }}
            </p>
          </div>

          <!-- Child Accounts Card -->
          <div
            v-if="account.is_header && account.children && account.children.length > 0"
            class="bg-white shadow-sm rounded-lg p-6"
          >
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Sub-Accounts ({{ account.children.length }})
            </h2>
            <div class="space-y-2">
              <div
                v-for="child in account.children"
                :key="child.id"
                class="flex items-center justify-between p-3 border rounded-md hover:bg-gray-50"
              >
                <div class="flex-1">
                  <p class="text-sm font-medium text-gray-900">
                    {{ child.code }} - {{ child.name }}
                  </p>
                  <p class="text-xs text-gray-500">
                    {{ formatAccountType(child.type) }}
                  </p>
                </div>
                <div class="text-right">
                  <p class="text-sm font-medium text-gray-900">
                    {{ formatCurrency(child.balance) }}
                  </p>
                  <span
                    :class="getStatusClass(child.is_active)"
                    class="inline-block text-xs px-2 py-1 rounded-full"
                  >
                    {{ child.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column - Actions & Stats -->
        <div class="space-y-6">
          <!-- Quick Actions Card -->
          <div
            v-if="canEdit || canDelete"
            class="bg-white shadow-sm rounded-lg p-6"
          >
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Quick Actions
            </h2>
            <div class="space-y-2">
              <button
                v-if="!account.is_active && canEdit"
                class="w-full btn-success justify-center"
                @click="activateAccount"
              >
                Activate Account
              </button>
              <button
                v-else-if="canEdit"
                class="w-full btn-warning justify-center"
                @click="deactivateAccount"
              >
                Deactivate Account
              </button>
              <button
                v-if="canDelete"
                class="w-full btn-danger justify-center"
                @click="deleteAccount"
              >
                Delete Account
              </button>
            </div>
          </div>

          <!-- Timeline Card -->
          <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Timeline
            </h2>
            <dl class="space-y-3">
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Created
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatDate(account.created_at) }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Last Updated
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatDate(account.updated_at) }}
                </dd>
              </div>
            </dl>
          </div>
        </div>
      </div>
    </div>

    <!-- Confirm Dialogs -->
    <ConfirmDialog
      v-if="showActivateDialog"
      title="Activate Account"
      message="Are you sure you want to activate this account?"
      confirm-text="Activate"
      cancel-text="Cancel"
      @confirm="confirmActivate"
      @cancel="showActivateDialog = false"
    />

    <ConfirmDialog
      v-if="showDeactivateDialog"
      title="Deactivate Account"
      message="Are you sure you want to deactivate this account? This may affect related transactions."
      confirm-text="Deactivate"
      cancel-text="Cancel"
      @confirm="confirmDeactivate"
      @cancel="showDeactivateDialog = false"
    />

    <ConfirmDialog
      v-if="showDeleteDialog"
      title="Delete Account"
      message="Are you sure you want to delete this account? This action cannot be undone."
      confirm-text="Delete"
      cancel-text="Cancel"
      @confirm="confirmDelete"
      @cancel="showDeleteDialog = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { accountingApi } from '@/api/accounting'
import type { Account } from '@/types/accounting'
import { usePermissions } from '@/composables/usePermissions'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'

const router = useRouter()
const route = useRoute()
const { hasPermission } = usePermissions()

const loading = ref(false)
const error = ref<string | null>(null)
const account = ref<Account | null>(null)
const showActivateDialog = ref(false)
const showDeactivateDialog = ref(false)
const showDeleteDialog = ref(false)

const accountId = route.params.id as string

const canEdit = hasPermission('accounting.accounts.update')
const canDelete = hasPermission('accounting.accounts.delete')

const fetchAccount = async () => {
  loading.value = true
  error.value = null
  
  try {
    account.value = await accountingApi.getAccount(accountId)
  } catch (err: any) {
    console.error('Failed to fetch account:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to load account details.'
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.push({ name: 'accounting-accounts' })
}

const editAccount = () => {
  router.push({ name: 'accounting-account-edit', params: { id: accountId } })
}

const activateAccount = () => {
  showActivateDialog.value = true
}

const confirmActivate = async () => {
  showActivateDialog.value = false
  try {
    await accountingApi.updateAccount(accountId, { is_active: true })
    await fetchAccount()
  } catch (err: any) {
    console.error('Failed to activate account:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to activate account.'
  }
}

const deactivateAccount = () => {
  showDeactivateDialog.value = true
}

const confirmDeactivate = async () => {
  showDeactivateDialog.value = false
  try {
    await accountingApi.updateAccount(accountId, { is_active: false })
    await fetchAccount()
  } catch (err: any) {
    console.error('Failed to deactivate account:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to deactivate account.'
  }
}

const deleteAccount = () => {
  showDeleteDialog.value = true
}

const confirmDelete = async () => {
  showDeleteDialog.value = false
  try {
    await accountingApi.deleteAccount(accountId)
    router.push({ name: 'accounting-accounts' })
  } catch (err: any) {
    console.error('Failed to delete account:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to delete account.'
  }
}

const getStatusClass = (isActive: boolean) => {
  return isActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
}

const getTypeClass = (type: string) => {
  const classes: Record<string, string> = {
    asset: 'bg-blue-100 text-blue-800',
    liability: 'bg-red-100 text-red-800',
    equity: 'bg-purple-100 text-purple-800',
    revenue: 'bg-green-100 text-green-800',
    expense: 'bg-orange-100 text-orange-800',
    contra: 'bg-gray-100 text-gray-800',
  }
  return classes[type] || 'bg-gray-100 text-gray-800'
}

const formatAccountType = (type: string) => {
  return type.charAt(0).toUpperCase() + type.slice(1)
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: account.value?.currency || 'USD'
  }).format(amount)
}

onMounted(() => {
  fetchAccount()
})
</script>

<style scoped>
.btn-primary {
  @apply inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2;
}

.btn-secondary {
  @apply inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2;
}

.btn-success {
  @apply inline-flex items-center rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2;
}

.btn-warning {
  @apply inline-flex items-center rounded-md bg-yellow-600 px-4 py-2 text-sm font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2;
}

.btn-danger {
  @apply inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2;
}
</style>
