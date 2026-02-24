<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Leave Management</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage leave types, allocations, and employee leave requests.</p>
        </div>
      </div>

      <div class="border-b border-gray-200 dark:border-gray-800">
        <nav class="-mb-px flex gap-6" aria-label="Leave tabs">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            type="button"
            :class="[
              activeTab === tab.key
                ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400'
                : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600',
              'whitespace-nowrap border-b-2 pb-3 text-sm font-medium transition-colors',
            ]"
            @click="switchTab(tab.key)"
          >
            {{ tab.label }}
            <span
              v-if="tab.count !== null"
              :class="activeTab === tab.key ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'"
              class="ml-2 rounded-full px-2 py-0.5 text-xs font-semibold"
            >{{ tab.count }}</span>
          </button>
        </nav>
      </div>

      <div
        v-if="leave.error"
        class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400"
        role="alert"
      >
        {{ leave.error }}
      </div>

      <!-- Leave Types tab -->
      <div v-if="activeTab === 'types'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Leave Types table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Type</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Max Days</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Paid</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="leave.loading">
                  <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="leave.leaveTypes.length === 0">
                  <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No leave types found.</td>
                </tr>
                <tr
                  v-for="type in leave.leaveTypes"
                  v-else
                  :key="type.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ type.name }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 capitalize">{{ type.type ?? '—' }}</td>
                  <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">{{ type.max_days ?? '—' }}</td>
                  <td class="px-4 py-3 text-sm">
                    <span :class="type.is_paid ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500'">
                      {{ type.is_paid ? 'Yes' : 'No' }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="leave.typesMeta" @change="leave.fetchLeaveTypes" />
        </div>
      </div>

      <!-- Leave Requests tab -->
      <div v-if="activeTab === 'requests'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Leave Requests table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Employee</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Leave Type</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">From</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">To</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Days</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="leave.loading">
                  <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="leave.leaveRequests.length === 0">
                  <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No leave requests found.</td>
                </tr>
                <tr
                  v-for="req in leave.leaveRequests"
                  v-else
                  :key="req.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ req.employee_name ?? req.employee_id }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ req.leave_type_name ?? req.leave_type_id ?? '—' }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(req.start_date) }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(req.end_date) }}</td>
                  <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">{{ req.duration_days ?? '—' }}</td>
                  <td class="px-4 py-3"><StatusBadge :status="req.status" /></td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="leave.requestsMeta" @change="leave.fetchLeaveRequests" />
        </div>
      </div>

      <!-- Allocations tab -->
      <div v-if="activeTab === 'allocations'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Leave Allocations table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Employee</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Leave Type</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Period</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Total</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Used</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Remaining</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="leave.allocationsLoading">
                  <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="leave.allocations.length === 0">
                  <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No leave allocations found.</td>
                </tr>
                <tr
                  v-for="alloc in leave.allocations"
                  v-else
                  :key="alloc.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white font-mono">{{ alloc.employee_id }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ alloc.leave_type_id }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ alloc.period_label ?? '—' }}</td>
                  <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">{{ alloc.total_days }}</td>
                  <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">{{ alloc.used_days }}</td>
                  <td class="px-4 py-3 text-sm text-right">
                    <span :class="remainingClass(alloc)">{{ remaining(alloc) }}</span>
                  </td>
                  <td class="px-4 py-3"><StatusBadge :status="alloc.status" /></td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="leave.allocationsMeta" @change="leave.fetchAllocations" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import Pagination from '@/components/Pagination.vue';
import { useLeaveStore } from '@/stores/leave';
import { useFormatters } from '@/composables/useFormatters';
const { formatDate } = useFormatters();

const leave = useLeaveStore();
const activeTab = ref('requests');

const tabs = computed(() => [
    { key: 'requests',    label: 'Leave Requests',    count: leave.requestsMeta.total    || null },
    { key: 'allocations', label: 'Allocations',       count: leave.allocationsMeta.total || null },
    { key: 'types',       label: 'Leave Types',       count: leave.typesMeta.total       || null },
]);

function switchTab(key) {
    activeTab.value = key;
    if (key === 'types')       leave.fetchLeaveTypes();
    else if (key === 'requests')    leave.fetchLeaveRequests();
    else if (key === 'allocations') leave.fetchAllocations();
}

function remaining(alloc) {
    return (parseFloat(alloc.total_days) - parseFloat(alloc.used_days)).toFixed(2);
}

function remainingClass(alloc) {
    const rem = parseFloat(alloc.total_days) - parseFloat(alloc.used_days);
    if (rem <= 0) return 'text-red-600 dark:text-red-400 font-semibold';
    if (rem <= 2) return 'text-amber-600 dark:text-amber-400 font-semibold';
    return 'text-emerald-600 dark:text-emerald-400';
}

onMounted(() => leave.fetchLeaveRequests());
</script>
