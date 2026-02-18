<template>
  <div class="dynamic-table-container">
    <header
      v-if="config.title"
      class="mb-5"
    >
      <h2 class="text-2xl font-bold text-gray-900">
        {{ config.title }}
      </h2>
    </header>

    <section class="rounded-lg border border-gray-300 bg-white overflow-hidden">
      <div
        v-if="needsTopControls"
        class="border-b border-gray-200 p-4 flex flex-wrap gap-3 items-center justify-between bg-gray-50"
      >
        <div class="flex gap-3 items-center flex-1 min-w-0">
          <input
            v-if="config.searchable"
            :value="queryState.searchPhrase"
            type="search"
            placeholder="Type to search..."
            class="flex-1 max-w-sm px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
            @input="debounceSearch"
          >
          
          <template v-if="config.filters">
            <div
              v-for="criterion in config.filters"
              :key="criterion.name"
              class="min-w-[150px]"
            >
              <component
                :is="getFilterWidget(criterion.type)"
                :label="criterion.label"
                :options="criterion.options"
                :value="queryState.criteriaValues[criterion.name]"
                @change="applyCriterion(criterion.name, $event)"
              />
            </div>
          </template>
        </div>

        <div class="flex gap-2 items-center">
          <button
            v-if="multipleRowsChosen && config.bulkActions?.length"
            type="button"
            class="relative px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            @click="bulkMenuVisible = !bulkMenuVisible"
          >
            Bulk ({{ chosenRowKeys.size }})
          </button>

          <button
            v-if="config.exportable"
            :disabled="exportRunning"
            type="button"
            class="px-4 py-2 border border-gray-300 bg-white text-sm font-medium rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            @click="triggerDataExport"
          >
            {{ exportRunning ? 'Processing...' : 'Export Data' }}
          </button>
        </div>
      </div>

      <div class="overflow-auto">
        <table class="w-full border-collapse">
          <thead>
            <tr class="bg-gray-100 border-b border-gray-300">
              <th
                v-if="config.bulkActions?.length"
                class="w-12 px-4 py-3"
              >
                <input
                  type="checkbox"
                  :checked="entirePageSelected"
                  class="w-4 h-4 rounded border-gray-400 text-indigo-600 focus:ring-indigo-500"
                  @change="togglePageSelection"
                >
              </th>
              <th
                v-for="descriptor in displayedDescriptors"
                :key="descriptor.key"
                :style="buildHeaderStyles(descriptor)"
                class="px-4 py-3 text-xs font-semibold text-gray-700 uppercase tracking-wide select-none"
              >
                <div
                  :class="buildHeaderClasses(descriptor)"
                  @click="descriptor.sortable && cycleSortOrder(descriptor.key)"
                >
                  <span>{{ descriptor.label }}</span>
                  <span
                    v-if="descriptor.sortable && queryState.sortColumn === descriptor.key"
                    class="ml-2"
                  >
                    {{ queryState.sortDirection === 'asc' ? '↑' : '↓' }}
                  </span>
                </div>
              </th>
              <th
                v-if="config.actions?.length"
                class="px-4 py-3 text-right text-xs font-semibold text-gray-700 uppercase"
              >
                Operations
              </th>
            </tr>
          </thead>

          <tbody
            v-if="loadState === 'success' && recordSet.length"
            class="divide-y divide-gray-200"
          >
            <tr
              v-for="record in recordSet"
              :key="extractRecordKey(record)"
              class="hover:bg-indigo-50 transition-colors duration-150"
            >
              <td
                v-if="config.bulkActions?.length"
                class="px-4 py-3"
              >
                <input
                  type="checkbox"
                  :checked="chosenRowKeys.has(extractRecordKey(record))"
                  class="w-4 h-4 rounded border-gray-400 text-indigo-600 focus:ring-indigo-500"
                  @change="toggleSingleRecord(record)"
                >
              </td>
              <td
                v-for="descriptor in displayedDescriptors"
                :key="descriptor.key"
                :style="{ textAlign: descriptor.align || 'left' }"
                class="px-4 py-3 text-sm"
              >
                <component
                  :is="resolveCellComponent(descriptor)"
                  :descriptor="descriptor"
                  :record="record"
                  :raw-value="digValue(record, descriptor.key)"
                />
              </td>
              <td
                v-if="config.actions?.length"
                class="px-4 py-3 text-right"
              >
                <div class="inline-flex gap-2">
                  <button
                    v-for="operation in config.actions"
                    :key="operation.id"
                    :class="getOperationButtonClass(operation.variant)"
                    type="button"
                    class="px-3 py-1 text-xs font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-1"
                    @click="executeRowOperation(operation, record)"
                  >
                    {{ operation.label }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>

          <tbody v-else-if="loadState === 'loading'">
            <tr>
              <td
                :colspan="calculateSpan"
                class="px-4 py-16 text-center"
              >
                <div class="flex flex-col items-center gap-3">
                  <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600" />
                  <p class="text-gray-600 text-sm">
                    Fetching records...
                  </p>
                </div>
              </td>
            </tr>
          </tbody>

          <tbody v-else-if="loadState === 'error'">
            <tr>
              <td
                :colspan="calculateSpan"
                class="px-4 py-16 text-center"
              >
                <div class="flex flex-col items-center gap-3">
                  <p class="text-red-600 font-medium">
                    {{ errorMessage }}
                  </p>
                  <button
                    type="button"
                    class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700"
                    @click="reloadData"
                  >
                    Try Again
                  </button>
                </div>
              </td>
            </tr>
          </tbody>

          <tbody v-else>
            <tr>
              <td
                :colspan="calculateSpan"
                class="px-4 py-16 text-center text-gray-500"
              >
                No records found
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <footer
        v-if="config.pagination?.enabled && totalCount > 0"
        class="border-t border-gray-200 px-4 py-3 bg-gray-50 flex items-center justify-between"
      >
        <div class="flex items-center gap-2">
          <span class="text-sm text-gray-700">Show</span>
          <select
            :value="queryState.perPage"
            class="px-2 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
            @change="adjustPageSize($event)"
          >
            <option
              v-for="size in config.pagination.pageSizeOptions"
              :key="size"
              :value="size"
            >
              {{ size }}
            </option>
          </select>
          <span class="text-sm text-gray-700">per page</span>
        </div>

        <div class="flex items-center gap-2">
          <span class="text-sm text-gray-700">
            {{ rangeStart }} - {{ rangeEnd }} of {{ totalCount }}
          </span>
          
          <div class="flex gap-1 ml-4">
            <button
              :disabled="queryState.currentPage === 1"
              type="button"
              class="px-3 py-1 border border-gray-300 rounded-md text-sm hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed"
              @click="jumpToPage(1)"
            >
              ««
            </button>
            <button
              :disabled="queryState.currentPage === 1"
              type="button"
              class="px-3 py-1 border border-gray-300 rounded-md text-sm hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed"
              @click="jumpToPage(queryState.currentPage - 1)"
            >
              ‹
            </button>
            
            <template
              v-for="pageNum in visiblePageNumbers"
              :key="pageNum"
            >
              <button
                v-if="typeof pageNum === 'number'"
                :class="pageNum === queryState.currentPage ? 'bg-indigo-600 text-white' : 'border border-gray-300 hover:bg-gray-100'"
                type="button"
                class="px-3 py-1 rounded-md text-sm font-medium"
                @click="jumpToPage(pageNum)"
              >
                {{ pageNum }}
              </button>
              <span
                v-else
                class="px-2 py-1 text-gray-500"
              >…</span>
            </template>
            
            <button
              :disabled="queryState.currentPage >= lastPage"
              type="button"
              class="px-3 py-1 border border-gray-300 rounded-md text-sm hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed"
              @click="jumpToPage(queryState.currentPage + 1)"
            >
              ›
            </button>
            <button
              :disabled="queryState.currentPage >= lastPage"
              type="button"
              class="px-3 py-1 border border-gray-300 rounded-md text-sm hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed"
              @click="jumpToPage(lastPage)"
            >
              »»
            </button>
          </div>
        </div>
      </footer>
    </section>

    <Teleport to="body">
      <div
        v-if="bulkMenuVisible"
        class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50"
        @click="bulkMenuVisible = false"
      >
        <div
          class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4"
          @click.stop
        >
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Bulk Actions ({{ chosenRowKeys.size }} selected)
          </h3>
          <div class="space-y-2">
            <button
              v-for="bulkOp in config.bulkActions"
              :key="bulkOp.id"
              type="button"
              class="w-full px-4 py-2 text-left text-sm font-medium rounded-md hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
              @click="executeBulkOperation(bulkOp)"
            >
              {{ bulkOp.label }}
            </button>
          </div>
          <button
            type="button"
            class="mt-4 w-full px-4 py-2 border border-gray-300 rounded-md text-sm font-medium hover:bg-gray-50"
            @click="bulkMenuVisible = false"
          >
            Cancel
          </button>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted, defineAsyncComponent } from 'vue';
import type { TableMetadata, TableColumnMetadata, TableActionMetadata, BulkActionMetadata } from '@/types/metadata';
import { apiClient } from '@/api/client';

interface TableConfiguration {
  metadata: TableMetadata;
}

const props = defineProps<TableConfiguration>();

const emit = defineEmits<{
  action: [actionType: string, data: any];
  bulkAction: [actionType: string, records: any[]];
  loaded: [records: any[]];
  failed: [message: string];
}>();

const config = computed(() => props.metadata);

const recordSet = ref<any[]>([]);
const loadState = ref<'idle' | 'loading' | 'success' | 'error'>('idle');
const errorMessage = ref('');
const chosenRowKeys = ref<Set<string>>(new Set());
const bulkMenuVisible = ref(false);
const exportRunning = ref(false);
const searchTimer = ref<ReturnType<typeof setTimeout> | null>(null);
const totalCount = ref(0);

const queryState = reactive({
  searchPhrase: '',
  criteriaValues: {} as Record<string, any>,
  sortColumn: null as string | null,
  sortDirection: 'asc' as 'asc' | 'desc',
  currentPage: 1,
  perPage: config.value.pagination?.pageSize || 10
});

const displayedDescriptors = computed(() =>
  config.value.columns.filter(col => col.visible !== false)
);

const needsTopControls = computed(() =>
  config.value.searchable ||
  config.value.filters?.length ||
  config.value.exportable ||
  config.value.bulkActions?.length
);

const multipleRowsChosen = computed(() => chosenRowKeys.value.size > 0);

const entirePageSelected = computed(() =>
  recordSet.value.length > 0 &&
  recordSet.value.every(rec => chosenRowKeys.value.has(extractRecordKey(rec)))
);

const calculateSpan = computed(() => {
  let span = displayedDescriptors.value.length;
  if (config.value.bulkActions?.length) span++;
  if (config.value.actions?.length) span++;
  return span;
});

const lastPage = computed(() => Math.ceil(totalCount.value / queryState.perPage));

const rangeStart = computed(() => 
  totalCount.value === 0 ? 0 : (queryState.currentPage - 1) * queryState.perPage + 1
);

const rangeEnd = computed(() =>
  Math.min(queryState.currentPage * queryState.perPage, totalCount.value)
);

const visiblePageNumbers = computed(() => {
  const pages: (number | string)[] = [];
  const current = queryState.currentPage;
  const total = lastPage.value;
  const delta = 2;

  for (let i = 1; i <= total; i++) {
    if (
      i === 1 ||
      i === total ||
      (i >= current - delta && i <= current + delta)
    ) {
      pages.push(i);
    } else if (pages[pages.length - 1] !== '...') {
      pages.push('...');
    }
  }

  return pages;
});

onMounted(() => {
  fetchRecords();
});

watch(
  () => [
    queryState.searchPhrase,
    queryState.criteriaValues,
    queryState.sortColumn,
    queryState.sortDirection,
    queryState.currentPage,
    queryState.perPage
  ],
  () => {
    fetchRecords();
  },
  { deep: true }
);

function extractRecordsFromResponse(apiResponse: any): any[] {
  if (Array.isArray(apiResponse.data)) {
    return apiResponse.data;
  }
  
  if (apiResponse.data && Array.isArray(apiResponse.data.data)) {
    return apiResponse.data.data;
  }
  
  return [];
}

async function fetchRecords() {
  loadState.value = 'loading';
  errorMessage.value = '';

  try {
    const requestParams = assembleQueryParams();
    const apiResponse = await apiClient.get(config.value.apiEndpoint, { params: requestParams });

    if (apiResponse.data) {
      recordSet.value = extractRecordsFromResponse(apiResponse);

      if (apiResponse.meta) {
        totalCount.value = apiResponse.meta.total || recordSet.value.length;
        queryState.currentPage = apiResponse.meta.current_page || queryState.currentPage;
      } else {
        totalCount.value = recordSet.value.length;
      }

      loadState.value = 'success';
      emit('loaded', recordSet.value);
    }
  } catch (err: any) {
    loadState.value = 'error';
    errorMessage.value = err.message || 'Failed to retrieve data';
    emit('failed', errorMessage.value);
  }
}

function assembleQueryParams(): Record<string, any> {
  const params: Record<string, any> = {};

  if (config.value.pagination?.enabled) {
    params.page = queryState.currentPage;
    params.per_page = queryState.perPage;
  }

  if (queryState.searchPhrase.trim()) {
    params.search = queryState.searchPhrase.trim();
  }

  if (queryState.sortColumn) {
    params.sort_by = queryState.sortColumn;
    params.sort_order = queryState.sortDirection;
  }

  Object.entries(queryState.criteriaValues).forEach(([key, val]) => {
    if (val !== null && val !== undefined && val !== '') {
      params[`filter[${key}]`] = val;
    }
  });

  return params;
}

function debounceSearch(event: Event) {
  const target = event.target as HTMLInputElement;
  
  if (searchTimer.value) {
    clearTimeout(searchTimer.value);
  }

  searchTimer.value = setTimeout(() => {
    queryState.searchPhrase = target.value;
    queryState.currentPage = 1;
  }, 400);
}

function applyCriterion(criterionName: string, value: any) {
  queryState.criteriaValues[criterionName] = value;
  queryState.currentPage = 1;
}

function cycleSortOrder(columnKey: string) {
  if (queryState.sortColumn === columnKey) {
    queryState.sortDirection = queryState.sortDirection === 'asc' ? 'desc' : 'asc';
  } else {
    queryState.sortColumn = columnKey;
    queryState.sortDirection = 'asc';
  }
  queryState.currentPage = 1;
}

function jumpToPage(pageNumber: number) {
  if (pageNumber >= 1 && pageNumber <= lastPage.value) {
    queryState.currentPage = pageNumber;
  }
}

function adjustPageSize(event: Event) {
  const target = event.target as HTMLSelectElement;
  queryState.perPage = parseInt(target.value, 10);
  queryState.currentPage = 1;
}

function digValue(record: any, path: string): any {
  const segments = path.split('.');
  let current = record;

  for (const segment of segments) {
    if (current && typeof current === 'object' && segment in current) {
      current = current[segment];
    } else {
      return null;
    }
  }

  return current;
}

function extractRecordKey(record: any): string {
  return record.id?.toString() || record.uuid || JSON.stringify(record);
}

function toggleSingleRecord(record: any) {
  const key = extractRecordKey(record);
  
  if (chosenRowKeys.value.has(key)) {
    chosenRowKeys.value.delete(key);
  } else {
    chosenRowKeys.value.add(key);
  }
}

function togglePageSelection() {
  if (entirePageSelected.value) {
    chosenRowKeys.value.clear();
  } else {
    recordSet.value.forEach(rec => {
      chosenRowKeys.value.add(extractRecordKey(rec));
    });
  }
}

function getChosenRecords(): any[] {
  return recordSet.value.filter(rec => chosenRowKeys.value.has(extractRecordKey(rec)));
}

function executeRowOperation(operation: TableActionMetadata, record: any) {
  if (operation.confirm) {
    if (!confirm(`${operation.confirm.title}\n\n${operation.confirm.message}`)) {
      return;
    }
  }
  
  emit('action', operation.action, record);
}

function executeBulkOperation(bulkOp: BulkActionMetadata) {
  if (bulkOp.confirm) {
    if (!confirm(`${bulkOp.confirm.title}\n\n${bulkOp.confirm.message}`)) {
      return;
    }
  }

  const selected = getChosenRecords();
  emit('bulkAction', bulkOp.action, selected);
  bulkMenuVisible.value = false;
  chosenRowKeys.value.clear();
}

async function triggerDataExport() {
  exportRunning.value = true;

  try {
    const params = assembleQueryParams();
    params.export = 'csv';
    delete params.page;
    delete params.per_page;

    const response = await apiClient.getInstance().get(config.value.apiEndpoint, {
      params,
      responseType: 'blob'
    });

    const blobUrl = URL.createObjectURL(new Blob([response.data]));
    const anchor = document.createElement('a');
    anchor.href = blobUrl;
    anchor.download = `table_export_${new Date().getTime()}.csv`;
    document.body.appendChild(anchor);
    anchor.click();
    document.body.removeChild(anchor);
    URL.revokeObjectURL(blobUrl);
  } catch (err) {
    console.error('Export operation failed:', err);
  } finally {
    exportRunning.value = false;
  }
}

function reloadData() {
  fetchRecords();
}

function buildHeaderStyles(descriptor: TableColumnMetadata): Record<string, string> {
  const styles: Record<string, string> = {};
  
  if (descriptor.width) {
    styles.width = descriptor.width;
  }
  
  return styles;
}

function buildHeaderClasses(descriptor: TableColumnMetadata): string[] {
  const classes = ['flex', 'items-center'];
  
  if (descriptor.align === 'center') {
    classes.push('justify-center');
  } else if (descriptor.align === 'right') {
    classes.push('justify-end');
  } else {
    classes.push('justify-start');
  }
  
  if (descriptor.sortable) {
    classes.push('cursor-pointer', 'hover:text-indigo-600');
  }
  
  return classes;
}

function getOperationButtonClass(variant: string): string {
  const baseClasses = 'transition-colors';
  
  switch (variant) {
    case 'primary':
      return `${baseClasses} bg-indigo-600 text-white hover:bg-indigo-700`;
    case 'secondary':
      return `${baseClasses} bg-gray-200 text-gray-800 hover:bg-gray-300`;
    case 'success':
      return `${baseClasses} bg-green-600 text-white hover:bg-green-700`;
    case 'warning':
      return `${baseClasses} bg-yellow-500 text-white hover:bg-yellow-600`;
    case 'danger':
      return `${baseClasses} bg-red-600 text-white hover:bg-red-700`;
    default:
      return `${baseClasses} bg-gray-600 text-white hover:bg-gray-700`;
  }
}

function getFilterWidget(filterType: string) {
  const widgetMap: Record<string, any> = {
    text: defineAsyncComponent(() => import('./filters/TextFilter.vue')),
    select: defineAsyncComponent(() => import('./filters/SelectFilter.vue')),
    multiselect: defineAsyncComponent(() => import('./filters/MultiSelectFilter.vue')),
    date: defineAsyncComponent(() => import('./filters/DateFilter.vue')),
    daterange: defineAsyncComponent(() => import('./filters/DateRangeFilter.vue')),
    number: defineAsyncComponent(() => import('./filters/NumberFilter.vue')),
    boolean: defineAsyncComponent(() => import('./filters/BooleanFilter.vue'))
  };

  return widgetMap[filterType] || widgetMap.text;
}

function resolveCellComponent(descriptor: TableColumnMetadata) {
  if (descriptor.customComponent) {
    return defineAsyncComponent(() => 
      import(`@/components/custom/${descriptor.customComponent}.vue`)
    );
  }

  const typeComponentMap: Record<string, any> = {
    text: defineAsyncComponent(() => import('./cells/TextCell.vue')),
    number: defineAsyncComponent(() => import('./cells/NumberCell.vue')),
    date: defineAsyncComponent(() => import('./cells/DateCell.vue')),
    datetime: defineAsyncComponent(() => import('./cells/DateTimeCell.vue')),
    boolean: defineAsyncComponent(() => import('./cells/BooleanCell.vue')),
    badge: defineAsyncComponent(() => import('./cells/BadgeCell.vue'))
  };

  return typeComponentMap[descriptor.type] || typeComponentMap.text;
}

defineExpose({
  reload: fetchRecords,
  clearChosenRows: () => chosenRowKeys.value.clear(),
  getChosenRecords
});
</script>
