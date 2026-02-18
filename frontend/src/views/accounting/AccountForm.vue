<template>
  <div class="account-form">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          {{ isEdit ? 'Edit Account' : 'Create Account' }}
        </h1>
        <p class="mt-1 text-sm text-gray-600">
          {{ isEdit ? 'Update account information' : 'Add a new account to your chart of accounts' }}
        </p>
      </div>
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
    </div>

    <!-- Error Alert -->
    <div
      v-if="error"
      class="mb-4 rounded-md bg-red-50 p-4"
    >
      <div class="flex">
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">
            {{ error }}
          </h3>
        </div>
      </div>
    </div>

    <!-- Form -->
    <form
      class="space-y-6"
      @submit.prevent="handleSubmit"
    >
      <!-- Basic Information -->
      <div class="bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
          Basic Information
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Account Code -->
          <div>
            <label
              for="code"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Account Code <span class="text-red-500">*</span>
            </label>
            <input
              id="code"
              v-model="form.code"
              type="text"
              required
              class="input"
              :class="{ 'border-red-500': errors.code }"
              placeholder="e.g., 1000, 2000, 4000"
            >
            <p
              v-if="errors.code"
              class="mt-1 text-sm text-red-600"
            >
              {{ errors.code }}
            </p>
          </div>

          <!-- Account Name -->
          <div>
            <label
              for="name"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Account Name <span class="text-red-500">*</span>
            </label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              required
              class="input"
              :class="{ 'border-red-500': errors.name }"
              placeholder="Enter account name"
            >
            <p
              v-if="errors.name"
              class="mt-1 text-sm text-red-600"
            >
              {{ errors.name }}
            </p>
          </div>

          <!-- Account Type -->
          <div>
            <label
              for="type"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Account Type <span class="text-red-500">*</span>
            </label>
            <select
              id="type"
              v-model="form.type"
              required
              class="input"
              :class="{ 'border-red-500': errors.type }"
            >
              <option value="">
                Select account type
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
            <p
              v-if="errors.type"
              class="mt-1 text-sm text-red-600"
            >
              {{ errors.type }}
            </p>
          </div>

          <!-- Parent Account -->
          <div>
            <label
              for="parent_id"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Parent Account
            </label>
            <select
              id="parent_id"
              v-model="form.parent_id"
              class="input"
              :disabled="loadingParents"
            >
              <option :value="undefined">
                None (Top Level)
              </option>
              <option
                v-for="parent in parentAccounts"
                :key="parent.id"
                :value="parent.id"
              >
                {{ parent.code }} - {{ parent.name }}
              </option>
            </select>
            <p
              v-if="loadingParents"
              class="mt-1 text-xs text-gray-500"
            >
              Loading parent accounts...
            </p>
          </div>

          <!-- Is Active -->
          <div class="flex items-center">
            <input
              id="is_active"
              v-model="form.is_active"
              type="checkbox"
              class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
            >
            <label
              for="is_active"
              class="ml-2 block text-sm text-gray-700"
            >
              Active
            </label>
          </div>

          <!-- Is Header -->
          <div class="flex items-center">
            <input
              id="is_header"
              v-model="form.is_header"
              type="checkbox"
              class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
            >
            <label
              for="is_header"
              class="ml-2 block text-sm text-gray-700"
            >
              Header Account (Cannot post transactions)
            </label>
          </div>

          <!-- Description -->
          <div class="md:col-span-2">
            <label
              for="description"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Description
            </label>
            <textarea
              id="description"
              v-model="form.description"
              rows="4"
              class="input"
              placeholder="Enter a description for this account"
            />
          </div>
        </div>
      </div>

      <!-- Form Actions -->
      <div class="flex items-center justify-end space-x-4">
        <button
          type="button"
          class="btn-secondary"
          @click="goBack"
        >
          Cancel
        </button>
        <button
          type="submit"
          class="btn-primary"
          :disabled="loading"
        >
          <span
            v-if="loading"
            class="flex items-center"
          >
            <svg
              class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              />
              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
              />
            </svg>
            Saving...
          </span>
          <span v-else>
            {{ isEdit ? 'Update Account' : 'Create Account' }}
          </span>
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { accountingApi } from '@/api/accounting'
import type { Account, AccountType } from '@/types/accounting'

const router = useRouter()
const route = useRoute()

const loading = ref(false)
const loadingParents = ref(false)
const error = ref<string | null>(null)
const errors = reactive<Record<string, string>>({})
const parentAccounts = ref<Account[]>([])

const isEdit = computed(() => !!route.params.id)
const accountId = computed(() => route.params.id as string)

const form = reactive({
  code: '',
  name: '',
  type: '' as AccountType | '',
  parent_id: undefined as number | undefined,
  is_active: true,
  is_header: false,
  description: '',
})

const fetchParentAccounts = async () => {
  loadingParents.value = true
  
  try {
    const response = await accountingApi.getAccounts({
      is_active: true,
      per_page: 1000
    })
    
    // Filter out the current account if editing to prevent circular references
    if (isEdit.value) {
      parentAccounts.value = response.data.filter(
        (acc) => acc.id !== parseInt(accountId.value)
      )
    } else {
      parentAccounts.value = response.data
    }
  } catch (err: any) {
    console.error('Failed to fetch parent accounts:', err)
  } finally {
    loadingParents.value = false
  }
}

const fetchAccount = async () => {
  if (!isEdit.value) return

  loading.value = true
  error.value = null
  
  try {
    const account = await accountingApi.getAccount(accountId.value)
    
    // Populate form with account data
    Object.assign(form, {
      code: account.code || '',
      name: account.name || '',
      type: account.type || '',
      parent_id: account.parent_id || undefined,
      is_active: account.is_active ?? true,
      is_header: account.is_header ?? false,
      description: account.description || '',
    })
  } catch (err: any) {
    console.error('Failed to fetch account:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to load account data.'
  } finally {
    loading.value = false
  }
}

const handleSubmit = async () => {
  // Clear previous errors
  Object.keys(errors).forEach(key => delete errors[key])
  error.value = null
  loading.value = true

  try {
    const payload = {
      code: form.code,
      name: form.name,
      type: form.type as AccountType,
      parent_id: form.parent_id || undefined,
      is_active: form.is_active,
      is_header: form.is_header,
      description: form.description || undefined,
    }

    if (isEdit.value) {
      await accountingApi.updateAccount(accountId.value, payload)
    } else {
      await accountingApi.createAccount(payload)
    }

    // Navigate back to accounts list
    router.push({ name: 'accounting-accounts' })
  } catch (err: any) {
    console.error('Failed to save account:', err)
    
    // Handle validation errors
    if (err.response?.data?.errors) {
      Object.assign(errors, err.response.data.errors)
    }
    
    error.value = err.response?.data?.message || err.message || 'Failed to save account. Please try again.'
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.push({ name: 'accounting-accounts' })
}

onMounted(async () => {
  await fetchParentAccounts()
  if (isEdit.value) {
    await fetchAccount()
  }
})
</script>

<style scoped>
.input {
  @apply block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm;
}

.btn-primary {
  @apply inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
  @apply inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2;
}
</style>
