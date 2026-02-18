<template>
  <div class="dynamic-dashboard">
    <div class="dashboard-header">
      <h1 class="dashboard-title">{{ config?.title || 'Dashboard' }}</h1>
      <div class="dashboard-actions">
        <slot name="actions" />
      </div>
    </div>

    <div 
      v-if="!loading && widgets.length > 0"
      class="dashboard-grid"
      :style="gridStyle"
    >
      <div
        v-for="widget in visibleWidgets"
        :key="widget.id"
        class="dashboard-widget"
        :style="widgetStyle(widget)"
      >
        <div class="widget-header">
          <h3 class="widget-title">
            <component
              v-if="widget.icon"
              :is="widget.icon"
              class="widget-icon"
            />
            {{ widget.title }}
          </h3>
          <div class="widget-actions">
            <slot :name="`widget-actions-${widget.id}`" :widget="widget" />
            <button
              v-if="widget.refreshable"
              @click="refreshWidget(widget.id)"
              class="widget-action-btn"
              title="Refresh"
            >
              ↻
            </button>
          </div>
        </div>

        <div class="widget-content">
          <!-- KPI Widget -->
          <div v-if="widget.type === 'kpi'" class="widget-kpi">
            <div class="kpi-value">{{ formatValue(widget.data?.value, widget) }}</div>
            <div class="kpi-label">{{ widget.data?.label || widget.title }}</div>
            <div v-if="widget.data?.change" class="kpi-change" :class="changeClass(widget.data.change)">
              <span class="change-icon">{{ widget.data.change > 0 ? '↑' : '↓' }}</span>
              {{ Math.abs(widget.data.change) }}%
            </div>
          </div>

          <!-- Chart Widget -->
          <div v-else-if="widget.type === 'chart'" class="widget-chart">
            <slot :name="`widget-${widget.id}`" :widget="widget" :data="widget.data">
              <div class="chart-placeholder">
                Chart: {{ widget.chart_type || 'line' }}
              </div>
            </slot>
          </div>

          <!-- Table Widget -->
          <div v-else-if="widget.type === 'table'" class="widget-table">
            <DynamicTable
              v-if="widget.data?.fields && widget.data?.items"
              :fields="widget.data.fields"
              :data="widget.data.items"
              :paginated="widget.config?.paginated !== false"
              :per-page="widget.config?.perPage || 5"
              :hide-actions="true"
              :hide-row-actions="!widget.config?.showRowActions"
            />
          </div>

          <!-- List Widget -->
          <div v-else-if="widget.type === 'list'" class="widget-list">
            <ul class="list-items">
              <li
                v-for="(item, index) in widget.data?.items"
                :key="index"
                class="list-item"
              >
                <slot :name="`widget-list-item-${widget.id}`" :item="item" :index="index">
                  <span class="list-item-label">{{ item.label }}</span>
                  <span class="list-item-value">{{ formatValue(item.value, widget) }}</span>
                </slot>
              </li>
            </ul>
          </div>

          <!-- Custom Widget -->
          <div v-else class="widget-custom">
            <slot :name="`widget-${widget.id}`" :widget="widget" :data="widget.data">
              <div class="widget-placeholder">
                Custom widget: {{ widget.type }}
              </div>
            </slot>
          </div>
        </div>

        <div v-if="widget.footer" class="widget-footer">
          <slot :name="`widget-footer-${widget.id}`" :widget="widget">
            {{ widget.footer }}
          </slot>
        </div>
      </div>
    </div>

    <div v-else-if="loading" class="dashboard-loading">
      <div class="loading-spinner">Loading dashboard...</div>
    </div>

    <div v-else class="dashboard-empty">
      <slot name="empty">
        <p>No widgets configured</p>
      </slot>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import DynamicTable from './DynamicTable.vue';

const props = defineProps({
  config: {
    type: Object,
    required: true,
    default: () => ({})
  },
  widgets: {
    type: Array,
    required: true,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  },
  autoRefresh: {
    type: Number,
    default: 0 // 0 = no auto refresh, otherwise interval in seconds
  }
});

const emit = defineEmits(['refresh', 'widget-refresh', 'widget-action']);

const refreshTimers = ref({});

const gridStyle = computed(() => {
  const columns = props.config?.columns || 12;
  const gap = props.config?.gap || '1rem';
  
  return {
    gridTemplateColumns: `repeat(${columns}, 1fr)`,
    gap: gap
  };
});

const visibleWidgets = computed(() => {
  return props.widgets.filter(widget => widget.visible !== false);
});

const widgetStyle = (widget) => {
  const colSpan = widget.col_span || widget.width || 1;
  const rowSpan = widget.row_span || widget.height || 1;
  
  return {
    gridColumn: `span ${colSpan}`,
    gridRow: `span ${rowSpan}`
  };
};

const formatValue = (value, widget) => {
  if (value === null || value === undefined) return '-';
  
  const format = widget.format || widget.config?.format;
  
  if (format === 'currency') {
    const currency = widget.currency || widget.config?.currency || 'USD';
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currency
    }).format(value);
  }
  
  if (format === 'percent') {
    return `${value}%`;
  }
  
  if (format === 'number') {
    return new Intl.NumberFormat('en-US').format(value);
  }
  
  return value;
};

const changeClass = (change) => {
  if (change > 0) return 'positive';
  if (change < 0) return 'negative';
  return 'neutral';
};

const refreshWidget = async (widgetId) => {
  emit('widget-refresh', widgetId);
};

const setupAutoRefresh = () => {
  if (props.autoRefresh > 0) {
    visibleWidgets.value.forEach(widget => {
      if (widget.refreshable !== false) {
        const interval = widget.refresh_interval || props.autoRefresh;
        refreshTimers.value[widget.id] = setInterval(() => {
          refreshWidget(widget.id);
        }, interval * 1000);
      }
    });
  }
};

const clearAutoRefresh = () => {
  Object.values(refreshTimers.value).forEach(timer => {
    clearInterval(timer);
  });
  refreshTimers.value = {};
};

onMounted(() => {
  setupAutoRefresh();
});

onUnmounted(() => {
  clearAutoRefresh();
});

defineExpose({
  refreshWidget,
  refreshAll: () => emit('refresh')
});
</script>

<style scoped>
.dynamic-dashboard {
  width: 100%;
  padding: 1rem;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.dashboard-title {
  font-size: 1.875rem;
  font-weight: 700;
  color: #111827;
  margin: 0;
}

.dashboard-actions {
  display: flex;
  gap: 0.5rem;
}

.dashboard-grid {
  display: grid;
  width: 100%;
}

.dashboard-widget {
  background: white;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.widget-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  border-bottom: 1px solid #e5e7eb;
  background: #f9fafb;
}

.widget-title {
  font-size: 1rem;
  font-weight: 600;
  color: #374151;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.widget-icon {
  width: 1.25rem;
  height: 1.25rem;
}

.widget-actions {
  display: flex;
  gap: 0.5rem;
}

.widget-action-btn {
  padding: 0.25rem 0.5rem;
  border: none;
  background: transparent;
  cursor: pointer;
  color: #6b7280;
  transition: color 0.15s;
}

.widget-action-btn:hover {
  color: #3b82f6;
}

.widget-content {
  padding: 1rem;
  flex: 1;
}

.widget-footer {
  padding: 0.75rem 1rem;
  border-top: 1px solid #e5e7eb;
  background: #f9fafb;
  font-size: 0.875rem;
  color: #6b7280;
}

/* KPI Widget */
.widget-kpi {
  text-align: center;
  padding: 1rem;
}

.kpi-value {
  font-size: 2.5rem;
  font-weight: 700;
  color: #111827;
  margin-bottom: 0.5rem;
}

.kpi-label {
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 0.5rem;
}

.kpi-change {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.875rem;
  font-weight: 500;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
}

.kpi-change.positive {
  color: #059669;
  background: #d1fae5;
}

.kpi-change.negative {
  color: #dc2626;
  background: #fee2e2;
}

.kpi-change.neutral {
  color: #6b7280;
  background: #f3f4f6;
}

.change-icon {
  font-weight: 700;
}

/* Chart Widget */
.widget-chart {
  min-height: 200px;
}

.chart-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 200px;
  color: #9ca3af;
  font-size: 0.875rem;
}

/* Table Widget */
.widget-table {
  overflow-x: auto;
}

/* List Widget */
.list-items {
  list-style: none;
  padding: 0;
  margin: 0;
}

.list-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 0;
  border-bottom: 1px solid #e5e7eb;
}

.list-item:last-child {
  border-bottom: none;
}

.list-item-label {
  color: #374151;
}

.list-item-value {
  font-weight: 600;
  color: #111827;
}

/* Loading & Empty States */
.dashboard-loading,
.dashboard-empty {
  padding: 3rem;
  text-align: center;
  color: #6b7280;
}

.loading-spinner {
  font-size: 1rem;
}

/* Responsive */
@media (max-width: 768px) {
  .dashboard-grid {
    grid-template-columns: 1fr !important;
  }
  
  .dashboard-widget {
    grid-column: span 1 !important;
  }
}
</style>
