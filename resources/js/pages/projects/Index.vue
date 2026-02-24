<template>
  <AppLayout>
    <div class="space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Project Management</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage projects, tasks, milestones, and time entries.</p>
      </div>

      <!-- Tabs -->
      <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-6" aria-label="Tabs">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            :class="[
              'whitespace-nowrap pb-3 px-1 border-b-2 text-sm font-medium transition-colors',
              activeTab === tab.key
                ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300',
            ]"
            @click="switchTab(tab.key)"
          >
            {{ tab.label }}
          </button>
        </nav>
      </div>

      <!-- Projects Tab -->
      <template v-if="activeTab === 'projects'">
        <div
          v-if="pm.error"
          class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400"
          role="alert"
        >
          {{ pm.error }}
        </div>
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Projects table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Project</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Budget</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Spent</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Start Date</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Due Date</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="pm.loading">
                  <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="pm.projects.length === 0">
                  <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No projects found.</td>
                </tr>
                <tr
                  v-for="project in pm.projects"
                  v-else
                  :key="project.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ project.name }}</p>
                    <p v-if="project.description" class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs">{{ project.description }}</p>
                  </td>
                  <td class="px-4 py-3"><StatusBadge :status="project.status" /></td>
                  <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">{{ formatCurrency(project.budget) }}</td>
                  <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">{{ formatCurrency(project.spent) }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(project.start_date) }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(project.due_date ?? project.end_date) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="pm.meta" @change="pm.fetchProjects" />
        </div>
      </template>

      <!-- Tasks Tab -->
      <template v-if="activeTab === 'tasks'">
        <div
          v-if="pm.tasksError"
          class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400"
          role="alert"
        >
          {{ pm.tasksError }}
        </div>
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Tasks table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Title</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Priority</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Est. Hours</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Actual Hours</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Due Date</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="pm.tasksLoading">
                  <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="pm.tasks.length === 0">
                  <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No tasks found.</td>
                </tr>
                <tr
                  v-for="task in pm.tasks"
                  v-else
                  :key="task.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ task.title }}</p>
                    <p v-if="task.description" class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs">{{ task.description }}</p>
                  </td>
                  <td class="px-4 py-3"><StatusBadge :status="task.status" /></td>
                  <td class="px-4 py-3"><StatusBadge :status="task.priority" /></td>
                  <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">{{ task.estimated_hours ?? '—' }}</td>
                  <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">{{ task.actual_hours ?? '—' }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(task.due_date) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="pm.tasksMeta" @change="pm.fetchTasks" />
        </div>
      </template>

      <!-- Milestones Tab -->
      <template v-if="activeTab === 'milestones'">
        <div
          v-if="pm.milestonesError"
          class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400"
          role="alert"
        >
          {{ pm.milestonesError }}
        </div>
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Milestones table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Project ID</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Due Date</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="pm.milestonesLoading">
                  <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="pm.milestones.length === 0">
                  <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No milestones found.</td>
                </tr>
                <tr
                  v-for="milestone in pm.milestones"
                  v-else
                  :key="milestone.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ milestone.name }}</td>
                  <td class="px-4 py-3 text-xs font-mono text-indigo-600 dark:text-indigo-400 truncate max-w-[10rem]" :title="milestone.project_id">
                    {{ milestone.project_id }}
                  </td>
                  <td class="px-4 py-3 text-sm" :class="isDueSoon(milestone.due_date) ? 'text-amber-600 dark:text-amber-400 font-medium' : 'text-gray-500 dark:text-gray-400'">
                    {{ formatDate(milestone.due_date) }}
                  </td>
                  <td class="px-4 py-3"><StatusBadge :status="milestone.status" /></td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="pm.milestonesMeta" @change="pm.fetchMilestones" />
        </div>
      </template>

      <!-- Time Entries Tab -->
      <template v-if="activeTab === 'time_entries'">
        <div
          v-if="pm.timeEntriesError"
          class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400"
          role="alert"
        >
          {{ pm.timeEntriesError }}
        </div>
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Time entries table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Date</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Project ID</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Description</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Hours</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="pm.timeEntriesLoading">
                  <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="pm.timeEntries.length === 0">
                  <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No time entries found.</td>
                </tr>
                <tr
                  v-for="entry in pm.timeEntries"
                  v-else
                  :key="entry.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(entry.entry_date) }}</td>
                  <td class="px-4 py-3 text-xs font-mono text-indigo-600 dark:text-indigo-400 truncate max-w-[10rem]" :title="entry.project_id">
                    {{ entry.project_id }}
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-900 dark:text-white truncate max-w-xs">{{ entry.description ?? '—' }}</td>
                  <td class="px-4 py-3 text-sm text-right font-medium text-gray-900 dark:text-white">{{ entry.hours }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="pm.timeEntriesMeta" @change="pm.fetchTimeEntries" />
        </div>
      </template>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import Pagination from '@/components/Pagination.vue';
import { useProjectsStore } from '@/stores/projects';
import { useFormatters } from '@/composables/useFormatters';
const { formatDate, formatCurrency } = useFormatters();

const pm = useProjectsStore();

const tabs = [
    { key: 'projects',     label: 'Projects' },
    { key: 'tasks',        label: 'Tasks' },
    { key: 'milestones',   label: 'Milestones' },
    { key: 'time_entries', label: 'Time Entries' },
];

const activeTab = ref('projects');

function switchTab(key) {
    activeTab.value = key;
    if (key === 'projects'     && pm.projects.length === 0)     pm.fetchProjects();
    if (key === 'tasks'        && pm.tasks.length === 0)         pm.fetchTasks();
    if (key === 'milestones'   && pm.milestones.length === 0)    pm.fetchMilestones();
    if (key === 'time_entries' && pm.timeEntries.length === 0)   pm.fetchTimeEntries();
}

function isDueSoon(dateStr) {
    if (!dateStr) return false;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const due = new Date(dateStr + 'T00:00:00');
    const diffMs = due - today;
    return diffMs >= 0 && diffMs <= 7 * 24 * 60 * 60 * 1000;
}

onMounted(() => pm.fetchProjects());
</script>

