<template>
  <div class="dynamic-widget bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
    <!-- Widget Header -->
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        {{ widget.title }}
      </h3>
      <button
        v-if="widget.refreshInterval"
        :disabled="refreshing"
        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
        :class="{ 'animate-spin': refreshing }"
        @click="handleRefresh"
      >
        <svg
          class="w-5 h-5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
          />
        </svg>
      </button>
    </div>

    <!-- Widget Content -->
    <div class="widget-content">
      <!-- Loading State -->
      <div
        v-if="loading"
        class="flex items-center justify-center py-8"
      >
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600" />
      </div>

      <!-- Error State -->
      <div
        v-else-if="error"
        class="text-red-600 text-sm py-4"
      >
        {{ error }}
      </div>

      <!-- Widget Type Renderers -->
      <component
        :is="widgetComponent"
        v-else-if="widgetComponent"
        :data="widgetData"
        :config="widget"
        @action="handleAction"
      />

      <!-- Empty State -->
      <div
        v-else
        class="text-gray-500 text-sm py-4 text-center"
      >
        No data available
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, defineAsyncComponent, type Component } from 'vue';
import type { WidgetMetadata } from '@/types/metadata';
import { useMetadataApi } from '@/composables/useMetadataApi';
import StatWidget from './widgets/StatWidget.vue';
import ChartWidget from './widgets/ChartWidget.vue';
import TableWidget from './widgets/TableWidget.vue';
import ListWidget from './widgets/ListWidget.vue';

interface Props {
  widget: WidgetMetadata;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  refresh: [];
  error: [{ widgetId: string; error: string }];
  action: [{ widgetId: string; action: string; data: any }];
}>();

const { fetchWidgetData } = useMetadataApi();
const widgetData = ref<any>(null);
const loading = ref(true);
const error = ref<string | null>(null);
const refreshing = ref(false);

// Map widget types to components
const widgetTypeComponents: Record<string, Component> = {
  stat: StatWidget,
  chart: ChartWidget,
  table: TableWidget,
  list: ListWidget,
};

const widgetComponent = computed(() => {
  // Custom component takes precedence
  if (props.widget.customComponent) {
    return defineAsyncComponent({
      loader: () => import(`../custom/${props.widget.customComponent}.vue`),
      errorComponent: () => null,
      delay: 200,
      timeout: 3000,
    });
  }

  // Built-in widget type
  return widgetTypeComponents[props.widget.type];
});

const loadWidgetData = async () => {
  if (refreshing.value) return;
  
  loading.value = true;
  error.value = null;

  try {
    const data = await fetchData();
    widgetData.value = data;
  } catch (err: any) {
    error.value = err.message || 'Failed to load widget data';
    emit('error', { widgetId: props.widget.id, error: error.value });
  } finally {
    loading.value = false;
    refreshing.value = false;
  }
};

const fetchData = async () => {
  const { dataSource } = props.widget;

  switch (dataSource.type) {
    case 'api':
      return await fetchWidgetData(dataSource.config.endpoint, dataSource.config.params);
    
    case 'static':
      return dataSource.config.data;
    
    case 'computed':
      // For computed data, the config should contain a function reference or formula
      return evaluateComputedData(dataSource.config);
    
    default:
      throw new Error(`Unknown data source type: ${dataSource.type}`);
  }
};

const evaluateComputedData = (config: any) => {
  // This would need to be implemented based on your computed data requirements
  // For now, return the config as-is
  return config.data || null;
};

const handleRefresh = async () => {
  refreshing.value = true;
  await loadWidgetData();
  emit('refresh');
};

const handleAction = (payload: { action: string; data: any }) => {
  emit('action', {
    widgetId: props.widget.id,
    action: payload.action,
    data: payload.data,
  });
};

onMounted(() => {
  loadWidgetData();
});

// Expose refresh method
defineExpose({
  refresh: loadWidgetData,
});
</script>

<style scoped>
.widget-content {
  @apply min-h-[200px];
}
</style>
