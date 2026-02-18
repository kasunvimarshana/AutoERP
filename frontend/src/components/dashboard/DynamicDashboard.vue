<template>
  <div class="dynamic-dashboard">
    <!-- Dashboard Header -->
    <div
      v-if="metadata?.title"
      class="mb-6"
    >
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
        {{ metadata.title }}
      </h2>
    </div>

    <!-- Loading State -->
    <div
      v-if="loading"
      class="flex items-center justify-center py-12"
    >
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600" />
    </div>

    <!-- Error State -->
    <div
      v-else-if="error"
      class="bg-red-50 border border-red-200 rounded-lg p-4"
    >
      <p class="text-red-800">
        {{ error }}
      </p>
    </div>

    <!-- Dashboard Content -->
    <div
      v-else-if="metadata"
      :class="dashboardLayoutClass"
    >
      <DynamicWidget
        v-for="widget in visibleWidgets"
        :key="widget.id"
        :widget="widget"
        :class="getWidgetClass(widget)"
        @refresh="refreshWidget(widget.id)"
        @error="handleWidgetError"
      />
    </div>

    <!-- Empty State -->
    <div
      v-else
      class="text-center py-12 text-gray-500"
    >
      <p>No dashboard configuration available</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import type { DashboardMetadata, WidgetMetadata } from '@/types/metadata';
import { useMetadataStore } from '@/stores/metadata';
import DynamicWidget from './DynamicWidget.vue';

interface Props {
  dashboardId?: string;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  loaded: [metadata: DashboardMetadata];
  error: [error: string];
}>();

const metadataStore = useMetadataStore();
const metadata = ref<DashboardMetadata | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);
const refreshIntervals = new Map<string, NodeJS.Timeout>();

const dashboardLayoutClass = computed(() => {
  if (!metadata.value) return '';
  
  return metadata.value.layout === 'masonry'
    ? 'masonry-grid gap-6'
    : 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6';
});

const visibleWidgets = computed(() => {
  if (!metadata.value) return [];
  
  return metadata.value.widgets.filter(widget => {
    // Check permissions if specified
    if (widget.permissions && widget.permissions.length > 0) {
      return metadataStore.hasAnyPermission(widget.permissions);
    }
    return true;
  });
});

const getWidgetClass = (widget: WidgetMetadata) => {
  const classes = ['dashboard-widget'];
  
  if (metadata.value?.layout === 'grid') {
    // Grid-based positioning
    classes.push(`col-span-${widget.position.colSpan || 1}`);
    classes.push(`row-span-${widget.position.rowSpan || 1}`);
  }
  
  return classes.join(' ');
};

const loadDashboard = async () => {
  loading.value = true;
  error.value = null;

  try {
    const dashboardData = await metadataStore.loadDashboardMetadata(props.dashboardId);
    metadata.value = dashboardData;
    
    // Setup auto-refresh for widgets with refreshInterval
    setupAutoRefresh();
    
    emit('loaded', dashboardData);
  } catch (err: any) {
    error.value = err.message || 'Failed to load dashboard';
    emit('error', error.value);
  } finally {
    loading.value = false;
  }
};

const setupAutoRefresh = () => {
  if (!metadata.value) return;

  metadata.value.widgets.forEach(widget => {
    if (widget.refreshInterval && widget.refreshInterval > 0) {
      const interval = setInterval(() => {
        refreshWidget(widget.id);
      }, widget.refreshInterval * 1000);
      
      refreshIntervals.set(widget.id, interval);
    }
  });
};

const refreshWidget = (widgetId: string) => {
  // Widget will handle its own refresh via the refresh event
  console.log(`Refreshing widget: ${widgetId}`);
};

const handleWidgetError = (payload: { widgetId: string; error: string }) => {
  console.error(`Widget ${payload.widgetId} error:`, payload.error);
};

const cleanup = () => {
  // Clear all auto-refresh intervals
  refreshIntervals.forEach(interval => clearInterval(interval));
  refreshIntervals.clear();
};

onMounted(() => {
  loadDashboard();
});

onBeforeUnmount(() => {
  cleanup();
});

// Expose methods for parent components
defineExpose({
  refresh: loadDashboard,
  refreshWidget,
});
</script>

<style scoped>
.dashboard-widget {
  @apply transition-all duration-200;
}

.masonry-grid {
  column-count: 1;
}

@media (min-width: 768px) {
  .masonry-grid {
    column-count: 2;
  }
}

@media (min-width: 1024px) {
  .masonry-grid {
    column-count: 3;
  }
}

@media (min-width: 1280px) {
  .masonry-grid {
    column-count: 4;
  }
}

.masonry-grid .dashboard-widget {
  break-inside: avoid;
  margin-bottom: 1.5rem;
}
</style>
