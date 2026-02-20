<template>
  <div class="space-y-4">
    <PageHeader title="Reports" subtitle="Business intelligence and operational reporting" />

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
      <button
        v-for="report in REPORT_DEFINITIONS"
        :key="report.key"
        @click="selectReport(report.key)"
        :class="activeReport === report.key
          ? 'bg-blue-600 text-white border-blue-600'
          : 'bg-white text-gray-700 hover:bg-gray-100'"
        class="px-3 py-2 rounded-lg text-sm font-medium border transition-colors text-left"
      >
        {{ report.label }}
      </button>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-16">
      <AppSpinner size="lg" />
    </div>
    <div v-else-if="error" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
      {{ error }}
    </div>

    <div v-else class="bg-white shadow rounded-lg overflow-hidden">
      <AppEmptyState
        v-if="reportRows.length === 0"
        icon="📈"
        title="No data available"
        message="This report has no data for the current period."
      />
      <table v-else class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th
              v-for="col in activeColumns"
              :key="col.key"
              :class="col.align === 'right' ? 'text-right' : 'text-left'"
              class="px-4 py-3 text-xs font-medium text-gray-500 uppercase"
            >
              {{ col.label }}
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="(row, idx) in reportRows" :key="idx" class="hover:bg-gray-50">
            <td
              v-for="col in activeColumns"
              :key="col.key"
              :class="col.align === 'right' ? 'text-right font-medium' : 'text-left'"
              class="px-4 py-3 text-sm text-gray-700"
            >
              {{ row[col.key] ?? '—' }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { reportService, REPORT_DEFINITIONS } from '@/services/reports';
import type { ReportDefinition } from '@/services/reports';
import PageHeader from '@/components/PageHeader.vue';
import AppSpinner from '@/components/AppSpinner.vue';
import AppEmptyState from '@/components/AppEmptyState.vue';

const activeReport = ref<string>(REPORT_DEFINITIONS[0]?.key ?? 'sales-summary');
const reportRows = ref<Record<string, unknown>[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);

const activeDefinition = computed<ReportDefinition>(
  () => REPORT_DEFINITIONS.find((r) => r.key === activeReport.value) ?? REPORT_DEFINITIONS[0]!,
);

const activeColumns = computed(() => activeDefinition.value.columns);

async function loadReport(): Promise<void> {
  loading.value = true;
  error.value = null;
  try {
    const { data } = await reportService.fetch(activeDefinition.value.endpoint);
    reportRows.value = Array.isArray(data) ? data : (data as { data: Record<string, unknown>[] }).data;
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } };
    error.value = err.response?.data?.message ?? 'Failed to load report.';
  } finally {
    loading.value = false;
  }
}

function selectReport(key: string): void {
  activeReport.value = key;
  void loadReport();
}

onMounted(() => void loadReport());
</script>
