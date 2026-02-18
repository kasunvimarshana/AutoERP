<template>
  <div class="journal-entry-list-view">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          Journal Entries
        </h1>
        <p class="mt-1 text-sm text-gray-600">
          Manage your accounting journal entries and transactions
        </p>
      </div>
      <button
        v-if="hasPermission('accounting.journal-entries.create')"
        class="btn-primary"
        @click="createJournalEntry"
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
        New Journal Entry
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
            placeholder="Search by entry number, reference..."
            @input="debouncedSearch"
          >
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
            v-model="filters.status"
            class="input"
            @change="fetchJournalEntries"
          >
            <option value="">
              All Statuses
            </option>
            <option value="draft">
              Draft
            </option>
            <option value="posted">
              Posted
            </option>
            <option value="cancelled">
              Cancelled
            </option>
            <option value="reversed">
              Reversed
            </option>
          </select>
        </div>

        <!-- Date Range Start -->
        <div>
          <label
            for="date_from"
            class="block text-sm font-medium text-gray-700 mb-1"
          >
            Date From
          </label>
          <input
            id="date_from"
            v-model="filters.date_from"
            type="date"
            class="input"
            @change="fetchJournalEntries"
          >
        </div>

        <!-- Date Range End -->
        <div>
          <label
            for="date_to"
            class="block text-sm font-medium text-gray-700 mb-1"
          >
            Date To
          </label>
          <input
            id="date_to"
            v-model="filters.date_to"
            type="date"
            class="input"
            @change="fetchJournalEntries"
          >
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div
      v-if="loading"
      class="text-center py-12"
    >
      <div class="inline-block h-12 w-12 animate-spin rounded-full border-4 border-solid border-indigo-600 border-r-transparent" />
      <p class="mt-4 text-gray-600">
        Loading journal entries...
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
      v-else
      class="bg-white shadow-sm rounded-lg overflow-hidden"
    >
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Entry Number
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Date
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Reference
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Description
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Status
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              Total Debit
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              Total Credit
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr
            v-for="entry in journalEntries"
            :key="entry.id"
            class="hover:bg-gray-50 cursor-pointer"
            @click="viewJournalEntry(entry.id)"
          >
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
              {{ entry.entry_number }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
              {{ formatDate(entry.date) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
              {{ entry.reference || '-' }}
            </td>
            <td class="px-6 py-4 text-sm text-gray-900">
              {{ truncateText(entry.description, 50) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span
                :class="getStatusClass(entry.status)"
                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
              >
                {{ formatStatus(entry.status) }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
              {{ formatCurrency(entry.total_debit, entry.currency) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
              {{ formatCurrency(entry.total_credit, entry.currency) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <button
                class="text-indigo-600 hover:text-indigo-900 mr-3"
                @click.stop="viewJournalEntry(entry.id)"
              >
                View
              </button>
              <button
                v-if="entry.status === 'draft' && hasPermission('accounting.journal-entries.update')"
                class="text-indigo-600 hover:text-indigo-900 mr-3"
                @click.stop="editJournalEntry(entry.id)"
              >
                Edit
              </button>
              <button
                v-if="entry.status === 'draft' && hasPermission('accounting.journal-entries.post')"
                class="text-green-600 hover:text-green-900 mr-3"
                @click.stop="confirmPost(entry.id)"
              >
                Post
              </button>
              <button
                v-if="entry.status === 'draft' && hasPermission('accounting.journal-entries.delete')"
                class="text-red-600 hover:text-red-900"
                @click.stop="confirmDelete(entry.id)"
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
        v-if="journalEntries.length === 0"
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
          No journal entries found
        </h3>
        <p class="mt-1 text-sm text-gray-500">
          Get started by creating a new journal entry.
        </p>
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
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { accountingApi } from '@/api/accounting'
import type { JournalEntry, JournalEntryQueryParams } from '@/types/accounting'
import { usePermissions } from '@/composables/usePermissions'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'

const router = useRouter()
const { hasPermission } = usePermissions()

const journalEntries = ref<JournalEntry[]>([])
const loading = ref(false)
const error = ref('')
const showPostDialog = ref(false)
const showDeleteDialog = ref(false)
const entryToPost = ref<number | null>(null)
const entryToDelete = ref<number | null>(null)

const filters = reactive<JournalEntryQueryParams>({
  search: '',
  status: undefined,
  date_from: undefined,
  date_to: undefined,
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
    fetchJournalEntries()
  }, 300)
}

// Fetch journal entries
const fetchJournalEntries = async () => {
  loading.value = true
  error.value = ''
  
  try {
    const response = await accountingApi.getJournalEntries(filters)
    journalEntries.value = response.data
    Object.assign(pagination, response.meta)
  } catch (err: any) {
    error.value = err.message || 'Failed to load journal entries'
    console.error('Error fetching journal entries:', err)
  } finally {
    loading.value = false
  }
}

// Navigation
const createJournalEntry = () => {
  router.push({ name: 'accounting-journal-entry-create' })
}

const viewJournalEntry = (id: number) => {
  router.push({ name: 'accounting-journal-entry-detail', params: { id } })
}

const editJournalEntry = (id: number) => {
  router.push({ name: 'accounting-journal-entry-edit', params: { id } })
}

// Post
const confirmPost = (id: number) => {
  entryToPost.value = id
  showPostDialog.value = true
}

const postJournalEntry = async () => {
  if (!entryToPost.value) return
  
  try {
    await accountingApi.postJournalEntry(entryToPost.value)
    showPostDialog.value = false
    entryToPost.value = null
    await fetchJournalEntries()
  } catch (err: any) {
    error.value = err.message || 'Failed to post journal entry'
    console.error('Error posting journal entry:', err)
  }
}

// Delete
const confirmDelete = (id: number) => {
  entryToDelete.value = id
  showDeleteDialog.value = true
}

const deleteJournalEntry = async () => {
  if (!entryToDelete.value) return
  
  try {
    await accountingApi.deleteJournalEntry(entryToDelete.value)
    showDeleteDialog.value = false
    entryToDelete.value = null
    await fetchJournalEntries()
  } catch (err: any) {
    error.value = err.message || 'Failed to delete journal entry'
    console.error('Error deleting journal entry:', err)
  }
}

// Pagination
const changePage = (page: number) => {
  filters.page = page
  fetchJournalEntries()
}

// Formatting helpers
const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatStatus = (status: string): string => {
  return status.charAt(0).toUpperCase() + status.slice(1)
}

const formatCurrency = (amount: number, currency: string = 'USD'): string => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency
  }).format(amount)
}

const truncateText = (text: string, maxLength: number): string => {
  if (text.length <= maxLength) return text
  return text.substring(0, maxLength) + '...'
}

const getStatusClass = (status: string): string => {
  const classes: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800',
    posted: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
    reversed: 'bg-yellow-100 text-yellow-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

// Initialize
onMounted(() => {
  fetchJournalEntries()
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
