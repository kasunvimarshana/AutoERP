<template>
  <div class="relational-field">
    <!-- Single Select Lookup -->
    <div
      v-if="!multiple"
      class="relative"
    >
      <button
        type="button"
        :disabled="disabled"
        class="relative w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm pl-3 pr-10 py-2 text-left cursor-pointer focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
        :class="{ 'opacity-50 cursor-not-allowed': disabled }"
        @click="toggleDropdown"
      >
        <span
          v-if="selectedItem"
          class="block truncate"
        >
          {{ getDisplayText(selectedItem) }}
        </span>
        <span
          v-else
          class="block truncate text-gray-400"
        >
          {{ placeholder || 'Select an item...' }}
        </span>
        <span class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
          <svg
            class="h-5 w-5 text-gray-400"
            viewBox="0 0 20 20"
            fill="currentColor"
          >
            <path
              fill-rule="evenodd"
              d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
              clip-rule="evenodd"
            />
          </svg>
        </span>
      </button>

      <!-- Dropdown -->
      <transition
        enter-active-class="transition ease-out duration-100"
        enter-from-class="transform opacity-0 scale-95"
        enter-to-class="transform opacity-100 scale-100"
        leave-active-class="transition ease-in duration-75"
        leave-from-class="transform opacity-100 scale-100"
        leave-to-class="transform opacity-0 scale-95"
      >
        <div
          v-show="isOpen"
          class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-800 shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm"
        >
          <!-- Search -->
          <div class="sticky top-0 bg-white dark:bg-gray-800 p-2 border-b border-gray-200 dark:border-gray-700">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search..."
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
              @input="handleSearch"
            >
          </div>

          <!-- Loading -->
          <div
            v-if="loading"
            class="px-3 py-2 text-sm text-gray-500 text-center"
          >
            Loading...
          </div>

          <!-- Options -->
          <ul
            v-else
            class="py-1"
          >
            <li
              v-for="item in filteredItems"
              :key="getItemValue(item)"
              class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-gray-100 dark:hover:bg-gray-700"
              :class="{
                'bg-primary-50 dark:bg-primary-900': isSelected(item)
              }"
              @click="selectItem(item)"
            >
              <span
                class="block truncate"
                :class="{ 'font-semibold': isSelected(item) }"
              >
                {{ getDisplayText(item) }}
              </span>
              <span
                v-if="isSelected(item)"
                class="absolute inset-y-0 right-0 flex items-center pr-4 text-primary-600 dark:text-primary-400"
              >
                <svg
                  class="h-5 w-5"
                  viewBox="0 0 20 20"
                  fill="currentColor"
                >
                  <path
                    fill-rule="evenodd"
                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                    clip-rule="evenodd"
                  />
                </svg>
              </span>
            </li>
          </ul>

          <!-- No Results -->
          <div
            v-if="!loading && filteredItems.length === 0"
            class="px-3 py-2 text-sm text-gray-500 text-center"
          >
            No results found
          </div>
        </div>
      </transition>
    </div>

    <!-- Multi-Select -->
    <div
      v-else
      class="space-y-2"
    >
      <!-- Selected Items -->
      <div
        v-if="selectedItems.length > 0"
        class="flex flex-wrap gap-2 mb-2"
      >
        <span
          v-for="item in selectedItems"
          :key="getItemValue(item)"
          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200"
        >
          {{ getDisplayText(item) }}
          <button
            type="button"
            class="ml-1 inline-flex items-center p-0.5 text-primary-600 hover:text-primary-800 dark:text-primary-300 dark:hover:text-primary-100"
            @click="removeItem(item)"
          >
            <svg
              class="h-3 w-3"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                fill-rule="evenodd"
                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                clip-rule="evenodd"
              />
            </svg>
          </button>
        </span>
      </div>

      <!-- Search Input -->
      <div class="relative">
        <input
          v-model="searchQuery"
          type="text"
          :placeholder="placeholder || 'Search and select...'"
          :disabled="disabled"
          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
          @focus="isOpen = true"
          @input="handleSearch"
        >

        <!-- Dropdown for multi-select -->
        <transition
          enter-active-class="transition ease-out duration-100"
          enter-from-class="transform opacity-0 scale-95"
          enter-to-class="transform opacity-100 scale-100"
          leave-active-class="transition ease-in duration-75"
          leave-from-class="transform opacity-100 scale-100"
          leave-to-class="transform opacity-0 scale-95"
        >
          <div
            v-show="isOpen && filteredItems.length > 0"
            class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-800 shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm"
          >
            <ul class="py-1">
              <li
                v-for="item in filteredItems"
                :key="getItemValue(item)"
                class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-gray-100 dark:hover:bg-gray-700"
                :class="{ 'opacity-50': isSelected(item) }"
                @click="addItem(item)"
              >
                <span class="block truncate">
                  {{ getDisplayText(item) }}
                </span>
              </li>
            </ul>
          </div>
        </transition>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { api } from '@/api/client';

interface RelationalFieldConfig {
  entity: string;           // Related entity/table name
  apiEndpoint: string;      // API endpoint to fetch options
  valueField: string;       // Field to use as value (e.g., 'id')
  displayField: string;     // Field to display (e.g., 'name')
  searchFields?: string[];  // Fields to search in
  filters?: Record<string, any>; // Additional filters
}

interface Props {
  modelValue: any;
  config: RelationalFieldConfig;
  multiple?: boolean;
  placeholder?: string;
  disabled?: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  'update:modelValue': [value: any];
}>();

const items = ref<any[]>([]);
const searchQuery = ref('');
const isOpen = ref(false);
const loading = ref(false);

const selectedItem = computed(() => {
  if (props.multiple || !props.modelValue) return null;
  return items.value.find(item => getItemValue(item) === props.modelValue);
});

const selectedItems = computed(() => {
  if (!props.multiple || !props.modelValue) return [];
  const values = Array.isArray(props.modelValue) ? props.modelValue : [props.modelValue];
  return items.value.filter(item => values.includes(getItemValue(item)));
});

const filteredItems = computed(() => {
  if (!searchQuery.value) return items.value;

  const query = searchQuery.value.toLowerCase();
  const searchFields = props.config.searchFields || [props.config.displayField];

  return items.value.filter(item =>
    searchFields.some(field =>
      String(item[field] || '').toLowerCase().includes(query)
    )
  );
});

const getItemValue = (item: any) => {
  return item[props.config.valueField];
};

const getDisplayText = (item: any) => {
  return item[props.config.displayField];
};

const isSelected = (item: any) => {
  const value = getItemValue(item);
  if (props.multiple) {
    const values = Array.isArray(props.modelValue) ? props.modelValue : [];
    return values.includes(value);
  }
  return props.modelValue === value;
};

const toggleDropdown = () => {
  if (!props.disabled) {
    isOpen.value = !isOpen.value;
  }
};

const selectItem = (item: any) => {
  emit('update:modelValue', getItemValue(item));
  isOpen.value = false;
  searchQuery.value = '';
};

const addItem = (item: any) => {
  const value = getItemValue(item);
  const currentValues = Array.isArray(props.modelValue) ? [...props.modelValue] : [];
  
  if (!currentValues.includes(value)) {
    emit('update:modelValue', [...currentValues, value]);
  }
  
  searchQuery.value = '';
};

const removeItem = (item: any) => {
  const value = getItemValue(item);
  const currentValues = Array.isArray(props.modelValue) ? [...props.modelValue] : [];
  emit('update:modelValue', currentValues.filter(v => v !== value));
};

const loadItems = async () => {
  loading.value = true;
  try {
    const response = await api.get(props.config.apiEndpoint, {
      params: props.config.filters || {},
    });
    items.value = response.data.data || response.data;
  } catch (error) {
    console.error('Failed to load relational field items:', error);
  } finally {
    loading.value = false;
  }
};

const handleSearch = () => {
  if (searchQuery.value.length >= 2) {
    // Could implement server-side search here
    // For now, using client-side filtering via computed property
  }
};

// Close dropdown when clicking outside
const handleClickOutside = (event: MouseEvent) => {
  const target = event.target as HTMLElement;
  if (!target.closest('.relational-field')) {
    isOpen.value = false;
  }
};

onMounted(() => {
  loadItems();
  document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside);
});

watch(() => props.config.apiEndpoint, () => {
  loadItems();
});
</script>
