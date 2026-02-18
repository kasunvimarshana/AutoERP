<template>
  <div class="search-navigation relative">
    <!-- Search Input -->
    <div class="relative">
      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
        <svg
          class="h-5 w-5 text-gray-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
          />
        </svg>
      </div>
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Search pages, features..."
        class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
        @focus="showResults = true"
        @keydown.down.prevent="navigateDown"
        @keydown.up.prevent="navigateUp"
        @keydown.enter.prevent="selectResult"
        @keydown.esc="closeResults"
      >
      <div
        v-if="searchQuery"
        class="absolute inset-y-0 right-0 pr-3 flex items-center"
      >
        <button
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
          @click="clearSearch"
        >
          <svg
            class="h-5 w-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>
    </div>

    <!-- Search Results Dropdown -->
    <transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 translate-y-1"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-1"
    >
      <div
        v-show="showResults && (searchQuery || recentItems.length > 0)"
        class="absolute z-50 mt-2 w-full rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 max-h-96 overflow-auto"
      >
        <!-- Loading State -->
        <div
          v-if="loading"
          class="px-4 py-3 text-sm text-gray-500 text-center"
        >
          Searching...
        </div>

        <!-- Search Results -->
        <template v-else-if="searchQuery && filteredResults.length > 0">
          <div class="py-2">
            <div
              v-for="(result, index) in filteredResults"
              :key="result.id"
              class="px-4 py-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
              :class="{
                'bg-gray-100 dark:bg-gray-700': index === selectedIndex
              }"
              @click="navigateTo(result)"
            >
              <div class="flex items-center">
                <!-- Icon -->
                <div class="flex-shrink-0">
                  <component
                    :is="getIcon(result.icon)"
                    v-if="result.icon"
                    class="h-5 w-5 text-gray-400"
                  />
                </div>
                
                <!-- Content -->
                <div class="ml-3 flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                    {{ result.label }}
                  </p>
                  <p
                    v-if="result.description"
                    class="text-xs text-gray-500 dark:text-gray-400 truncate"
                  >
                    {{ result.description }}
                  </p>
                </div>

                <!-- Badge -->
                <div
                  v-if="result.badge"
                  class="ml-3 flex-shrink-0"
                >
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200">
                    {{ result.badge }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </template>

        <!-- No Results -->
        <div
          v-else-if="searchQuery && filteredResults.length === 0"
          class="px-4 py-3 text-sm text-gray-500 text-center"
        >
          No results found for "{{ searchQuery }}"
        </div>

        <!-- Recent Items -->
        <div v-else-if="!searchQuery && recentItems.length > 0">
          <div class="px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            Recent
          </div>
          <div class="py-2">
            <div
              v-for="item in recentItems"
              :key="item.id"
              class="px-4 py-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
              @click="navigateTo(item)"
            >
              <p class="text-sm font-medium text-gray-900 dark:text-white">
                {{ item.label }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useMetadataStore } from '@/stores/metadata';

interface SearchResult {
  id: string;
  label: string;
  path: string;
  description?: string;
  icon?: string;
  badge?: string;
  type: 'page' | 'feature' | 'module';
}

const router = useRouter();
const metadataStore = useMetadataStore();

const searchQuery = ref('');
const showResults = ref(false);
const loading = ref(false);
const selectedIndex = ref(0);
const recentItems = ref<SearchResult[]>([]);

// Build searchable items from metadata
const searchableItems = computed((): SearchResult[] => {
  const items: SearchResult[] = [];

  // Add navigation items from metadata
  const navigation = metadataStore.navigation || [];
  
  const addNavItems = (navItems: any[], parentLabel = '') => {
    navItems.forEach(item => {
      if (item.path) {
        items.push({
          id: item.id,
          label: parentLabel ? `${parentLabel} > ${item.label}` : item.label,
          path: item.path,
          icon: item.icon,
          type: 'page',
        });
      }
      
      if (item.children && item.children.length > 0) {
        addNavItems(item.children, item.label);
      }
    });
  };

  addNavItems(navigation);

  return items;
});

const filteredResults = computed(() => {
  if (!searchQuery.value) return [];

  const query = searchQuery.value.toLowerCase();
  return searchableItems.value
    .filter(item =>
      item.label.toLowerCase().includes(query) ||
      (item.description && item.description.toLowerCase().includes(query))
    )
    .slice(0, 10); // Limit to 10 results
});

const navigateDown = () => {
  if (selectedIndex.value < filteredResults.value.length - 1) {
    selectedIndex.value++;
  }
};

const navigateUp = () => {
  if (selectedIndex.value > 0) {
    selectedIndex.value--;
  }
};

const selectResult = () => {
  if (filteredResults.value.length > 0 && selectedIndex.value >= 0) {
    navigateTo(filteredResults.value[selectedIndex.value]);
  }
};

const navigateTo = (result: SearchResult) => {
  router.push(result.path);
  addToRecent(result);
  closeResults();
};

const addToRecent = (result: SearchResult) => {
  // Remove if already exists
  recentItems.value = recentItems.value.filter(item => item.id !== result.id);
  
  // Add to beginning
  recentItems.value.unshift(result);
  
  // Keep only last 5
  if (recentItems.value.length > 5) {
    recentItems.value = recentItems.value.slice(0, 5);
  }

  // Store in localStorage
  localStorage.setItem('recentSearchItems', JSON.stringify(recentItems.value));
};

const clearSearch = () => {
  searchQuery.value = '';
  selectedIndex.value = 0;
};

const closeResults = () => {
  showResults.value = false;
  selectedIndex.value = 0;
};

const getIcon = (iconName: string) => {
  return () => import(`@heroicons/vue/24/outline/${iconName}.js`);
};

// Load recent items from localStorage
const loadRecentItems = () => {
  const stored = localStorage.getItem('recentSearchItems');
  if (stored) {
    try {
      recentItems.value = JSON.parse(stored);
    } catch (e) {
      console.error('Failed to load recent items:', e);
    }
  }
};

// Reset selected index when search query changes
watch(searchQuery, () => {
  selectedIndex.value = 0;
});

// Initialize recent items
loadRecentItems();
</script>
