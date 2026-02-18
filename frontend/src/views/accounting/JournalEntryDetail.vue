<template>
  <div class="journal-entry-detail">
    <!-- Loading State -->
    <div
      v-if="loading"
      class="text-center py-12"
    >
      <div class="inline-block h-12 w-12 animate-spin rounded-full border-4 border-solid border-indigo-600 border-r-transparent" />
      <p class="mt-4 text-gray-600">
        Loading journal entry details...
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

    <!-- Journal Entry Details -->
    <div v-else-if="journalEntry">
      <!-- Header -->
      <div class="mb-6 flex items-center justify-between">
        <div class="flex-1">
          <div class="flex items-center space-x-4">
            <h1 class="text-2xl font-bold text-gray-900">
              Journal Entry {{ journalEntry.entry_number }}
            </h1>
            <span
              :class="getStatusClass(journalEntry.status)"
              class="rounded-full px-3 py-1 text-sm font-medium"
            >
              {{ formatStatus(journalEntry.status) }}
            </span>
            <span
              :class="isBalanced ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
              class="rounded-full px-3 py-1 text-sm font-medium"
            >
              {{ isBalanced ? 'Balanced' : 'Unbalanced' }}
            </span>
          </div>
          <p class="mt-1 text-sm text-gray-600">
            Date: {{ formatDate(journalEntry.date) }}
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
            v-if="journalEntry.status === 'draft' && hasPermission('accounting.journal-entries.update')"
            class="btn-primary"
            @click="editJournalEntry"
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
          <button
            v-if="journalEntry.status === 'draft' && hasPermission('accounting.journal-entries.post')"
            class="btn-success"
            :disabled="!isBalanced"
            @click="confirmPost"
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
                d="M5 13l4 4L19 7"
              />
            </svg>
            Post
          </button>
        </div>
      </div>

      <!-- Main Content -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Main Information -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Entry Information Card -->
          <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Entry Information
            </h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Entry Number
                </dt>
                <dd class="mt-1 text-sm text-gray-900 font-mono">
                  {{ journalEntry.entry_number }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Date
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatDate(journalEntry.date) }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Reference
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ journalEntry.reference || 'N/A' }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Currency
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ journalEntry.currency }}
                </dd>
              </div>
              <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-gray-500">
                  Description
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ journalEntry.description }}
                </dd>
              </div>
            </dl>
          </div>

          <!-- Totals Card -->
          <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Totals
            </h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Total Debit
                </dt>
                <dd class="mt-1 text-lg font-semibold text-gray-900">
                  {{ formatCurrency(journalEntry.total_debit) }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Total Credit
                </dt>
                <dd class="mt-1 text-lg font-semibold text-gray-900">
                  {{ formatCurrency(journalEntry.total_credit) }}
                </dd>
              </div>
            </dl>
          </div>

          <!-- Entry Lines Card -->
          <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Entry Lines
            </h2>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                      Account
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
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr
                    v-for="line in journalEntry.lines"
                    :key="line.id"
                  >
                    <td class="px-4 py-3 text-sm text-gray-900">
                      <div class="font-medium">
                        {{ line.account_code }}
                      </div>
                      <div class="text-gray-500">
                        {{ line.account_name }}
                      </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                      {{ line.description || '-' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-right text-gray-900 font-medium">
                      {{ line.debit > 0 ? formatCurrency(line.debit) : '-' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-right text-gray-900 font-medium">
                      {{ line.credit > 0 ? formatCurrency(line.credit) : '-' }}
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
                      {{ formatCurrency(journalEntry.total_debit) }}
                    </td>
                    <td class="px-4 py-3 text-sm text-right text-gray-900">
                      {{ formatCurrency(journalEntry.total_credit) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Posted Information Card -->
          <div
            v-if="journalEntry.status === 'posted' && journalEntry.posted_at"
            class="bg-white shadow-sm rounded-lg p-6"
          >
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Posted Information
            </h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Posted At
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatDateTime(journalEntry.posted_at) }}
                </dd>
              </div>
              <div v-if="journalEntry.posted_by_name">
                <dt class="text-sm font-medium text-gray-500">
                  Posted By
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ journalEntry.posted_by_name }}
                </dd>
              </div>
            </dl>
          </div>
        </div>

        <!-- Right Column - Actions & Stats -->
        <div class="space-y-6">
          <!-- Quick Actions Card -->
          <div
            v-if="journalEntry.status === 'draft'"
            class="bg-white shadow-sm rounded-lg p-6"
          >
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              Quick Actions
            </h2>
            <div class="space-y-2">
              <button
                v-if="hasPermission('accounting.journal-entries.post')"
                class="w-full btn-success justify-center"
                :disabled="!isBalanced"
                @click="confirmPost"
              >
                Post Entry
              </button>
              <button
                v-if="hasPermission('accounting.journal-entries.delete')"
                class="w-full btn-danger justify-center"
                @click="confirmDelete"
              >
                Delete Entry
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
                  {{ formatDateTime(journalEntry.created_at) }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Last Updated
                </dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatDateTime(journalEntry.updated_at) }}
                </dd>
              </div>
            </dl>
          </div>
        </div>
      </div>
    </div>

    <!-- Post Confirmation Modal -->
    <ConfirmDialog
      v-if="showPostDialog"
      title="Post Journal Entry"
      message="Are you sure you want to post this journal entry? Once posted, it cannot be modified."
      confirm-text="Post"
      cancel-text="Cancel"
      @confirm="postJournalEntry"
      @cancel="showPostDialog = false"
    />

    <!-- Delete Confirmation Modal -->
    <ConfirmDialog
      v-if="showDeleteDialog"
      title="Delete Journal Entry"
      message="Are you sure you want to delete this journal entry? This action cannot be undone."
      confirm-text="Delete"
      cancel-text="Cancel"
      @confirm="deleteJournalEntry"
      @cancel="showDeleteDialog = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { accountingApi } from '@/api/accounting'
import type { JournalEntry } from '@/types/accounting'
import { usePermissions } from '@/composables/usePermissions'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'

const router = useRouter()
const route = useRoute()
const { hasPermission } = usePermissions()

const loading = ref(false)
const error = ref<string | null>(null)
const journalEntry = ref<JournalEntry | null>(null)
const showPostDialog = ref(false)
const showDeleteDialog = ref(false)

const entryId = route.params.id as string

const isBalanced = computed(() => {
  if (!journalEntry.value) return false
  const diff = Math.abs(journalEntry.value.total_debit - journalEntry.value.total_credit)
  return diff < 0.01 // Allow for floating point precision
})

const fetchJournalEntry = async () => {
  loading.value = true
  error.value = null
  
  try {
    journalEntry.value = await accountingApi.getJournalEntry(entryId)
  } catch (err: any) {
    console.error('Failed to fetch journal entry:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to load journal entry details.'
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.push({ name: 'accounting-journal-entries' })
}

const editJournalEntry = () => {
  router.push({ name: 'accounting-journal-entry-edit', params: { id: entryId } })
}

const confirmPost = () => {
  showPostDialog.value = true
}

const postJournalEntry = async () => {
  try {
    await accountingApi.postJournalEntry(entryId)
    showPostDialog.value = false
    await fetchJournalEntry()
  } catch (err: any) {
    console.error('Failed to post journal entry:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to post journal entry.'
  }
}

const confirmDelete = () => {
  showDeleteDialog.value = true
}

const deleteJournalEntry = async () => {
  try {
    await accountingApi.deleteJournalEntry(entryId)
    showDeleteDialog.value = false
    router.push({ name: 'accounting-journal-entries' })
  } catch (err: any) {
    console.error('Failed to delete journal entry:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to delete journal entry.'
  }
}

const getStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800',
    posted: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
    reversed: 'bg-yellow-100 text-yellow-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatStatus = (status: string) => {
  return status.charAt(0).toUpperCase() + status.slice(1)
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatDateTime = (dateString: string) => {
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
    currency: journalEntry.value?.currency || 'USD'
  }).format(amount)
}

onMounted(() => {
  fetchJournalEntry()
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
  @apply inline-flex items-center rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-danger {
  @apply inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2;
}
</style>
