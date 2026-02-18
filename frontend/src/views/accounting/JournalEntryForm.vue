<template>
  <div class="journal-entry-form">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          {{ isEdit ? 'Edit Journal Entry' : 'Create Journal Entry' }}
        </h1>
        <p class="mt-1 text-sm text-gray-600">
          {{ isEdit ? 'Update journal entry information' : 'Create a new journal entry with balanced debits and credits' }}
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
          Entry Information
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <!-- Date -->
          <div>
            <label
              for="date"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Date <span class="text-red-500">*</span>
            </label>
            <input
              id="date"
              v-model="form.date"
              type="date"
              required
              class="input"
              :class="{ 'border-red-500': errors.date }"
            >
            <p
              v-if="errors.date"
              class="mt-1 text-sm text-red-600"
            >
              {{ errors.date }}
            </p>
          </div>

          <!-- Reference -->
          <div>
            <label
              for="reference"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Reference
            </label>
            <input
              id="reference"
              v-model="form.reference"
              type="text"
              class="input"
              placeholder="Optional reference"
            >
          </div>

          <!-- Empty column for spacing -->
          <div />

          <!-- Description -->
          <div class="md:col-span-3">
            <label
              for="description"
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Description <span class="text-red-500">*</span>
            </label>
            <textarea
              id="description"
              v-model="form.description"
              required
              rows="3"
              class="input"
              :class="{ 'border-red-500': errors.description }"
              placeholder="Enter a description for this journal entry"
            />
            <p
              v-if="errors.description"
              class="mt-1 text-sm text-red-600"
            >
              {{ errors.description }}
            </p>
          </div>
        </div>
      </div>

      <!-- Entry Lines -->
      <div class="bg-white shadow-sm rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-gray-900">
            Entry Lines
          </h2>
          <button
            type="button"
            class="btn-secondary"
            @click="addLine"
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
            Add Line
          </button>
        </div>

        <!-- Line Items Table -->
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Account <span class="text-red-500">*</span>
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Description
                </th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                  Debit
                </th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                  Credit
                </th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr
                v-for="(line, index) in form.lines"
                :key="index"
              >
                <td class="px-4 py-3">
                  <select
                    v-model="line.account_id"
                    required
                    class="input"
                    :class="{ 'border-red-500': errors[`lines.${index}.account_id`] }"
                    @change="updateLineAccount(index)"
                  >
                    <option :value="undefined">
                      Select account
                    </option>
                    <option
                      v-for="account in activeAccounts"
                      :key="account.id"
                      :value="account.id"
                    >
                      {{ account.code }} - {{ account.name }}
                    </option>
                  </select>
                  <p
                    v-if="errors[`lines.${index}.account_id`]"
                    class="mt-1 text-xs text-red-600"
                  >
                    {{ errors[`lines.${index}.account_id`] }}
                  </p>
                </td>
                <td class="px-4 py-3">
                  <input
                    v-model="line.description"
                    type="text"
                    class="input"
                    placeholder="Optional"
                  >
                </td>
                <td class="px-4 py-3">
                  <input
                    v-model.number="line.debit"
                    type="number"
                    step="0.01"
                    min="0"
                    class="input text-right"
                    :class="{ 'border-red-500': errors[`lines.${index}.debit`] }"
                    @input="onDebitChange(index)"
                  >
                  <p
                    v-if="errors[`lines.${index}.debit`]"
                    class="mt-1 text-xs text-red-600"
                  >
                    {{ errors[`lines.${index}.debit`] }}
                  </p>
                </td>
                <td class="px-4 py-3">
                  <input
                    v-model.number="line.credit"
                    type="number"
                    step="0.01"
                    min="0"
                    class="input text-right"
                    :class="{ 'border-red-500': errors[`lines.${index}.credit`] }"
                    @input="onCreditChange(index)"
                  >
                  <p
                    v-if="errors[`lines.${index}.credit`]"
                    class="mt-1 text-xs text-red-600"
                  >
                    {{ errors[`lines.${index}.credit`] }}
                  </p>
                </td>
                <td class="px-4 py-3 text-center">
                  <button
                    type="button"
                    class="text-red-600 hover:text-red-900"
                    :disabled="form.lines.length <= 2"
                    @click="removeLine(index)"
                  >
                    <svg
                      class="h-5 w-5"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                      />
                    </svg>
                  </button>
                </td>
              </tr>
              <tr class="bg-gray-50 font-semibold">
                <td
                  colspan="2"
                  class="px-4 py-3 text-sm text-gray-900"
                >
                  Total
                </td>
                <td class="px-4 py-3 text-sm text-right text-gray-900">
                  {{ formatCurrency(totalDebit) }}
                </td>
                <td class="px-4 py-3 text-sm text-right text-gray-900">
                  {{ formatCurrency(totalCredit) }}
                </td>
                <td class="px-4 py-3" />
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Balance Status -->
        <div class="mt-4 flex items-center justify-between">
          <div>
            <span
              :class="isBalanced ? 'text-green-700' : 'text-red-700'"
              class="text-sm font-medium"
            >
              {{ isBalanced ? '✓ Balanced' : '✗ Not Balanced' }}
            </span>
            <span
              v-if="!isBalanced"
              class="ml-2 text-sm text-gray-600"
            >
              Difference: {{ formatCurrency(Math.abs(totalDebit - totalCredit)) }}
            </span>
          </div>
          <div class="text-sm text-gray-600">
            Minimum 2 lines required
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
          :disabled="loading || !isBalanced || form.lines.length < 2"
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
            {{ isEdit ? 'Update Journal Entry' : 'Create Journal Entry' }}
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
import type { Account, JournalEntry } from '@/types/accounting'

const router = useRouter()
const route = useRoute()

const loading = ref(false)
const loadingAccounts = ref(false)
const error = ref<string | null>(null)
const errors = reactive<Record<string, string>>({})
const activeAccounts = ref<Account[]>([])

const isEdit = computed(() => !!route.params.id)
const entryId = computed(() => route.params.id as string)

interface LineItem {
  account_id: number | undefined
  debit: number
  credit: number
  description?: string
}

const form = reactive<{
  date: string
  reference?: string
  description: string
  lines: LineItem[]
}>({
  date: new Date().toISOString().split('T')[0],
  reference: '',
  description: '',
  lines: [
    { account_id: undefined, debit: 0, credit: 0, description: '' },
    { account_id: undefined, debit: 0, credit: 0, description: '' }
  ]
})

const totalDebit = computed(() => {
  return form.lines.reduce((sum, line) => sum + (line.debit || 0), 0)
})

const totalCredit = computed(() => {
  return form.lines.reduce((sum, line) => sum + (line.credit || 0), 0)
})

const isBalanced = computed(() => {
  const diff = Math.abs(totalDebit.value - totalCredit.value)
  return diff < 0.01 && totalDebit.value > 0 && totalCredit.value > 0
})

const fetchAccounts = async () => {
  loadingAccounts.value = true
  
  try {
    const response = await accountingApi.getAccounts({
      is_active: true,
      per_page: 1000
    })
    
    // Filter out header accounts (cannot post transactions to header accounts)
    activeAccounts.value = response.data.filter(acc => !acc.is_header)
  } catch (err: any) {
    console.error('Failed to fetch accounts:', err)
    error.value = 'Failed to load accounts. Please try again.'
  } finally {
    loadingAccounts.value = false
  }
}

const fetchJournalEntry = async () => {
  if (!isEdit.value) return

  loading.value = true
  error.value = null
  
  try {
    const entry = await accountingApi.getJournalEntry(entryId.value)
    
    // Populate form with entry data
    form.date = entry.date || new Date().toISOString().split('T')[0]
    form.reference = entry.reference || ''
    form.description = entry.description || ''
    
    // Populate lines
    if (entry.lines && entry.lines.length > 0) {
      form.lines = entry.lines.map(line => ({
        account_id: line.account_id,
        debit: line.debit || 0,
        credit: line.credit || 0,
        description: line.description || ''
      }))
    }
  } catch (err: any) {
    console.error('Failed to fetch journal entry:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to load journal entry data.'
  } finally {
    loading.value = false
  }
}

const addLine = () => {
  form.lines.push({
    account_id: undefined,
    debit: 0,
    credit: 0,
    description: ''
  })
}

const removeLine = (index: number) => {
  if (form.lines.length > 2) {
    form.lines.splice(index, 1)
  }
}

const updateLineAccount = (index: number) => {
  // Clear any errors for this line
  delete errors[`lines.${index}.account_id`]
}

const onDebitChange = (index: number) => {
  // If debit has a value, clear credit
  if (form.lines[index].debit > 0) {
    form.lines[index].credit = 0
  }
  delete errors[`lines.${index}.debit`]
  delete errors[`lines.${index}.credit`]
}

const onCreditChange = (index: number) => {
  // If credit has a value, clear debit
  if (form.lines[index].credit > 0) {
    form.lines[index].debit = 0
  }
  delete errors[`lines.${index}.debit`]
  delete errors[`lines.${index}.credit`]
}

const validateForm = (): boolean => {
  // Clear previous errors
  Object.keys(errors).forEach(key => delete errors[key])
  let isValid = true

  // Validate basic fields
  if (!form.date) {
    errors.date = 'Date is required'
    isValid = false
  }

  if (!form.description) {
    errors.description = 'Description is required'
    isValid = false
  }

  // Validate lines
  if (form.lines.length < 2) {
    error.value = 'At least 2 lines are required'
    isValid = false
  }

  form.lines.forEach((line, index) => {
    if (!line.account_id) {
      errors[`lines.${index}.account_id`] = 'Required'
      isValid = false
    }

    if (line.debit === 0 && line.credit === 0) {
      errors[`lines.${index}.debit`] = 'Must have debit or credit'
      errors[`lines.${index}.credit`] = 'Must have debit or credit'
      isValid = false
    }

    if (line.debit > 0 && line.credit > 0) {
      errors[`lines.${index}.debit`] = 'Cannot have both'
      errors[`lines.${index}.credit`] = 'Cannot have both'
      isValid = false
    }
  })

  // Validate balance
  if (!isBalanced.value) {
    error.value = 'Total debits must equal total credits'
    isValid = false
  }

  return isValid
}

const handleSubmit = async () => {
  // Clear previous errors
  error.value = null

  // Validate form
  if (!validateForm()) {
    return
  }

  loading.value = true

  try {
    const payload = {
      date: form.date,
      reference: form.reference || undefined,
      description: form.description,
      lines: form.lines.map(line => ({
        account_id: line.account_id!,
        debit: line.debit || 0,
        credit: line.credit || 0,
        description: line.description || undefined
      }))
    }

    if (isEdit.value) {
      await accountingApi.updateJournalEntry(entryId.value, payload)
    } else {
      await accountingApi.createJournalEntry(payload)
    }

    // Navigate back to journal entries list
    router.push({ name: 'accounting-journal-entries' })
  } catch (err: any) {
    console.error('Failed to save journal entry:', err)
    
    // Handle validation errors
    if (err.response?.data?.errors) {
      Object.assign(errors, err.response.data.errors)
    }
    
    error.value = err.response?.data?.message || err.message || 'Failed to save journal entry. Please try again.'
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.push({ name: 'accounting-journal-entries' })
}

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount)
}

onMounted(async () => {
  await fetchAccounts()
  if (isEdit.value) {
    await fetchJournalEntry()
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
