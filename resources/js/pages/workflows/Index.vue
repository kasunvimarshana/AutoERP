<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Workflow Engine</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage DB-driven state machines and transition rules.</p>
        </div>
      </div>

      <div
        v-if="workflow.error"
        class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400"
        role="alert"
      >
        {{ workflow.error }}
      </div>

      <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Workflows table">
            <thead class="bg-gray-50 dark:bg-gray-800/50">
              <tr>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Document Type</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Initial State</th>
                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">States</th>
                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Transitions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
              <tr v-if="workflow.loading">
                <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
              </tr>
              <tr v-else-if="workflow.workflows.length === 0">
                <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No workflows found.</td>
              </tr>
              <tr
                v-for="wf in workflow.workflows"
                v-else
                :key="wf.id"
                class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
              >
                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ wf.name }}</td>
                <td class="px-4 py-3 text-sm font-mono text-gray-500 dark:text-gray-400">{{ wf.document_type ?? '—' }}</td>
                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ wf.initial_state ?? '—' }}</td>
                <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">{{ wf.states_count ?? (wf.states?.length ?? '—') }}</td>
                <td class="px-4 py-3 text-sm text-right text-gray-500 dark:text-gray-400">{{ wf.transitions_count ?? (wf.transitions?.length ?? '—') }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <Pagination :meta="workflow.meta" @change="workflow.fetchWorkflows" />
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Pagination from '@/components/Pagination.vue';
import { useWorkflowStore } from '@/stores/workflow';

const workflow = useWorkflowStore();

onMounted(() => workflow.fetchWorkflows());
</script>
