<template>
  <AppLayout>
    <div class="space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">HR &amp; Payroll</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage departments, employees, payroll runs, attendance, performance goals, and salary structures.</p>
      </div>

      <!-- Tabs -->
      <div class="border-b border-gray-200 dark:border-gray-800">
        <nav class="-mb-px flex gap-6" aria-label="HR tabs">
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
          </button>
        </nav>
      </div>

      <div
        v-if="hr.error"
        class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400"
        role="alert"
      >
        {{ hr.error }}
      </div>

      <!-- Employees tab -->
      <div v-if="activeTab === 'employees'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Employees table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Employee</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Department</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Job Title</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Hire Date</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="hr.loading">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="hr.employees.length === 0">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No employees found.</td>
                </tr>
                <tr
                  v-for="emp in hr.employees"
                  v-else
                  :key="emp.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                      <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center shrink-0">
                        <span class="text-xs font-semibold text-indigo-700 dark:text-indigo-300 uppercase">{{ initials(emp.name) }}</span>
                      </div>
                      <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ emp.name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ emp.email ?? '' }}</p>
                      </div>
                    </div>
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ emp.department_name ?? emp.department?.name ?? '—' }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ emp.job_title ?? '—' }}</td>
                  <td class="px-4 py-3"><StatusBadge :status="emp.status" /></td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(emp.hire_date) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="hr.meta" @change="hr.fetchEmployees" />
        </div>
      </div>

      <!-- Attendance tab -->
      <div v-if="activeTab === 'attendance'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Attendance table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Employee</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Work Date</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Check In</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Check Out</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Hours</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="hr.attendanceLoading">
                  <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="hr.attendance.length === 0">
                  <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No attendance records found.</td>
                </tr>
                <tr
                  v-for="rec in hr.attendance"
                  v-else
                  :key="rec.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 font-mono">{{ rec.employee_id }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(rec.work_date) }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatTime(rec.check_in) }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ rec.check_out ? formatTime(rec.check_out) : '—' }}</td>
                  <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ rec.duration_hours != null ? `${rec.duration_hours} h` : '—' }}</td>
                  <td class="px-4 py-3"><StatusBadge :status="rec.status" /></td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="hr.attendanceMeta" @change="hr.fetchAttendance" />
        </div>
      </div>
      <!-- Performance Goals tab -->
      <div v-if="activeTab === 'performance'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Performance goals table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Title</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Employee</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Period</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Year</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Due Date</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="hr.goalsLoading">
                  <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="hr.performanceGoals.length === 0">
                  <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No performance goals found.</td>
                </tr>
                <tr
                  v-for="goal in hr.performanceGoals"
                  v-else
                  :key="goal.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ goal.title }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 font-mono text-xs">{{ goal.employee_id }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 uppercase">{{ goal.period }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ goal.year ?? '—' }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ goal.due_date ? formatDate(goal.due_date) : '—' }}</td>
                  <td class="px-4 py-3"><StatusBadge :status="goal.status" /></td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="hr.goalsMeta" @change="hr.fetchPerformanceGoals" />
        </div>
      </div>

      <!-- Salary Components tab -->
      <div v-if="activeTab === 'components'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Salary components table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Code</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Type</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Default Amount</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="hr.componentsLoading">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="hr.salaryComponents.length === 0">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No salary components found.</td>
                </tr>
                <tr
                  v-for="comp in hr.salaryComponents"
                  v-else
                  :key="comp.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-mono font-semibold text-indigo-700 dark:text-indigo-300">{{ comp.code }}</td>
                  <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ comp.name }}</td>
                  <td class="px-4 py-3"><StatusBadge :status="comp.type" /></td>
                  <td class="px-4 py-3 text-sm text-right tabular-nums text-gray-700 dark:text-gray-300">{{ formatCurrency(comp.default_amount) }}</td>
                  <td class="px-4 py-3"><StatusBadge :status="comp.is_active ? 'active' : 'inactive'" /></td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="hr.componentsMeta" @change="hr.fetchSalaryComponents" />
        </div>
      </div>

      <!-- Salary Structures tab -->
      <div v-if="activeTab === 'structures'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Salary structures table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Code</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Description</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Created</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="hr.structuresLoading">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="hr.salaryStructures.length === 0">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No salary structures found.</td>
                </tr>
                <tr
                  v-for="st in hr.salaryStructures"
                  v-else
                  :key="st.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-mono font-semibold text-indigo-700 dark:text-indigo-300">{{ st.code }}</td>
                  <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ st.name }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ st.description ?? '—' }}</td>
                  <td class="px-4 py-3"><StatusBadge :status="st.is_active ? 'active' : 'inactive'" /></td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(st.created_at) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="hr.structuresMeta" @change="hr.fetchSalaryStructures" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import Pagination from '@/components/Pagination.vue';
import { useHrStore } from '@/stores/hr';
import { useFormatters } from '@/composables/useFormatters';
const { formatDate, formatTime, formatCurrency } = useFormatters();

const hr = useHrStore();

const tabs = [
    { key: 'employees', label: 'Employees' },
    { key: 'attendance', label: 'Attendance' },
    { key: 'performance', label: 'Performance Goals' },
    { key: 'components', label: 'Salary Components' },
    { key: 'structures', label: 'Salary Structures' },
];
const activeTab = ref('employees');

function switchTab(key) {
    activeTab.value = key;
    if (key === 'employees') hr.fetchEmployees();
    if (key === 'attendance') hr.fetchAttendance();
    if (key === 'performance') hr.fetchPerformanceGoals();
    if (key === 'components') hr.fetchSalaryComponents();
    if (key === 'structures') hr.fetchSalaryStructures();
}

function initials(name) {
    return (name ?? '').split(' ').filter(n => n).map(n => n[0]).join('').slice(0, 2).toUpperCase() || '?';
}

onMounted(() => hr.fetchEmployees());
</script>
