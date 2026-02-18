<template>
  <aside :class="['sidebar', { 'mobile-open': isMobileOpen }]">
    <div class="sidebar-header">
      <h1 class="logo">AutoERP</h1>
      
      <!-- Search box for navigation -->
      <div v-if="enableSearch" class="nav-search">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search menu..."
          class="search-input"
          @input="handleSearch"
        />
      </div>
    </div>
    
    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <span>Loading menu...</span>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-state">
      <p>{{ error }}</p>
      <button @click="loadNavigationItems" class="retry-btn">
        Retry
      </button>
    </div>

    <!-- Navigation -->
    <nav v-else class="sidebar-nav" role="navigation" aria-label="Main navigation">
      <!-- Search Results -->
      <div v-if="searchQuery && searchResults.length > 0" class="search-results">
        <h3 class="search-results-title">Search Results</h3>
        <router-link
          v-for="result in searchResults"
          :key="result.path"
          :to="result.path"
          class="search-result-item"
          @click="clearSearch"
        >
          <span class="result-breadcrumb">{{ result.breadcrumb.join(' > ') }}</span>
        </router-link>
      </div>

      <!-- No Results -->
      <div v-else-if="searchQuery && searchResults.length === 0" class="no-results">
        <p>No menu items found</p>
      </div>

      <!-- Normal Navigation -->
      <template v-else v-for="item in displayedItems" :key="item.name">
        <!-- Single Item (no children) -->
        <div v-if="!item.children">
          <router-link
            :to="item.path"
            class="nav-item"
            :class="{ active: isActive(item) }"
            :aria-label="item.name"
          >
            <component :is="getIconComponent(item.icon)" class="nav-icon" aria-hidden="true" />
            <span>{{ item.name }}</span>
          </router-link>
        </div>

        <!-- Parent Item (with children) -->
        <div v-else class="nav-group">
          <button
            class="nav-item"
            :class="{ active: isActive(item), open: item.isOpen }"
            @click="toggleSubmenu(item)"
            :aria-expanded="item.isOpen"
            :aria-label="`${item.name} menu`"
          >
            <component :is="getIconComponent(item.icon)" class="nav-icon" aria-hidden="true" />
            <span>{{ item.name }}</span>
            <ChevronDownIcon class="chevron" :class="{ open: item.isOpen }" aria-hidden="true" />
          </button>

          <!-- Submenu -->
          <transition name="submenu">
            <div
              v-if="item.isOpen"
              class="submenu"
              role="menu"
              :aria-label="`${item.name} submenu`"
            >
              <router-link
                v-for="child in item.children"
                :key="child.path"
                :to="child.path"
                class="submenu-item"
                :class="{ active: isActive(child) }"
                role="menuitem"
              >
                <component
                  v-if="child.icon"
                  :is="getIconComponent(child.icon)"
                  class="submenu-icon"
                  aria-hidden="true"
                />
                {{ child.name }}
              </router-link>
            </div>
          </transition>
        </div>
      </template>
    </nav>
    
    <!-- Mobile Overlay -->
    <div
      v-if="isMobileOpen"
      class="sidebar-overlay"
      @click="$emit('close')"
      aria-hidden="true"
    ></div>
  </aside>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import {
  HomeIcon,
  UserGroupIcon,
  CubeIcon,
  UsersIcon,
  ShoppingCartIcon,
  ClipboardDocumentListIcon,
  CogIcon,
  CurrencyDollarIcon,
  ChartBarIcon,
  ChevronDownIcon,
  UserIcon,
  ShieldCheckIcon,
  KeyIcon,
  ArchiveBoxIcon,
  ClipboardDocumentIcon,
  UserCircleIcon,
  TruckIcon,
  ShoppingBagIcon,
  DocumentTextIcon,
  DocumentIcon,
  CreditCardIcon,
  ListBulletIcon,
  WrenchIcon,
  WrenchScrewdriverIcon,
  BanknotesIcon,
  PencilSquareIcon,
  ChartBarSquareIcon,
  RectangleStackIcon,
  FolderIcon,
} from '@heroicons/vue/24/outline';
import {
  loadNavigation,
  searchNavigation,
  isNavigationItemActive,
} from '../services/dynamicNavigationService';

const props = defineProps({
  isMobileOpen: {
    type: Boolean,
    default: false,
  },
  source: {
    type: String,
    default: 'entities', // 'entities' or 'menus'
    validator: (value) => ['entities', 'menus'].includes(value),
  },
  enableSearch: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['close']);

const route = useRoute();

// State
const loading = ref(false);
const error = ref(null);
const navigationItems = ref([]);
const searchQuery = ref('');
const searchResults = ref([]);

// Icon components map
const iconComponents = {
  HomeIcon,
  UserGroupIcon,
  CubeIcon,
  UsersIcon,
  ShoppingCartIcon,
  ClipboardDocumentListIcon,
  CogIcon,
  CurrencyDollarIcon,
  ChartBarIcon,
  ChevronDownIcon,
  UserIcon,
  ShieldCheckIcon,
  KeyIcon,
  ArchiveBoxIcon,
  ClipboardDocumentIcon,
  UserCircleIcon,
  TruckIcon,
  ShoppingBagIcon,
  DocumentTextIcon,
  DocumentIcon,
  CreditCardIcon,
  ListBulletIcon,
  WrenchIcon,
  WrenchScrewdriverIcon,
  BanknotesIcon,
  PencilSquareIcon,
  ChartBarSquareIcon,
  RectangleStackIcon,
  FolderIcon,
};

// Computed
const displayedItems = computed(() => {
  return navigationItems.value;
});

// Methods
async function loadNavigationItems() {
  try {
    loading.value = true;
    error.value = null;

    navigationItems.value = await loadNavigation(props.source);
  } catch (err) {
    console.error('Failed to load navigation:', err);
    error.value = 'Failed to load navigation menu';
  } finally {
    loading.value = false;
  }
}

function toggleSubmenu(item) {
  item.isOpen = !item.isOpen;
}

function isActive(item) {
  return isNavigationItemActive(item, route.path);
}

function getIconComponent(iconName) {
  return iconComponents[iconName] || FolderIcon;
}

function handleSearch() {
  if (searchQuery.value.trim()) {
    searchResults.value = searchNavigation(navigationItems.value, searchQuery.value);
  } else {
    searchResults.value = [];
  }
}

function clearSearch() {
  searchQuery.value = '';
  searchResults.value = [];
}

// Initialize
onMounted(() => {
  loadNavigationItems();
});

// Watch for route changes to auto-expand active parent
watch(() => route.path, (newPath) => {
  // Find and expand parent of active item
  const expandParent = (items) => {
    items.forEach(item => {
      if (item.children) {
        const hasActiveChild = item.children.some(child => child.path === newPath);
        if (hasActiveChild) {
          item.isOpen = true;
        }
        expandParent(item.children);
      }
    });
  };

  expandParent(navigationItems.value);
});
</script>

<style scoped>
.sidebar {
  position: fixed;
  left: 0;
  top: 0;
  bottom: 0;
  width: 260px;
  background: #1f2937;
  color: white;
  overflow-y: auto;
  z-index: 1000;
  transition: transform 0.3s ease;
  display: flex;
  flex-direction: column;
}

@media (max-width: 768px) {
  .sidebar {
    transform: translateX(-100%);
  }
  
  .sidebar.mobile-open {
    transform: translateX(0);
  }
  
  .sidebar-overlay {
    position: fixed;
    top: 0;
    left: 260px;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
  }
}

/* Header */
.sidebar-header {
  padding: 1.25rem;
  border-bottom: 1px solid #374151;
  flex-shrink: 0;
}

.logo {
  font-size: 1.25rem;
  font-weight: 700;
  color: #3b82f6;
  margin: 0 0 1rem 0;
}

.nav-search {
  margin-top: 0.75rem;
}

.search-input {
  width: 100%;
  padding: 0.5rem 0.75rem;
  background: #374151;
  border: 1px solid #4b5563;
  border-radius: 0.375rem;
  color: white;
  font-size: 0.875rem;
}

.search-input::placeholder {
  color: #9ca3af;
}

.search-input:focus {
  outline: none;
  border-color: #3b82f6;
}

/* Loading & Error States */
.loading-state,
.error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  color: #9ca3af;
  text-align: center;
}

.spinner {
  width: 2rem;
  height: 2rem;
  border: 2px solid #374151;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-bottom: 0.75rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.retry-btn {
  margin-top: 0.75rem;
  padding: 0.5rem 1rem;
  background: #3b82f6;
  border: none;
  border-radius: 0.375rem;
  color: white;
  cursor: pointer;
  font-size: 0.875rem;
}

.retry-btn:hover {
  background: #2563eb;
}

/* Navigation */
.sidebar-nav {
  padding: 1rem 0;
  flex: 1;
  overflow-y: auto;
}

.nav-item {
  display: flex;
  align-items: center;
  width: 100%;
  padding: 0.75rem 1.25rem;
  color: #d1d5db;
  text-decoration: none;
  transition: all 0.2s;
  background: none;
  border: none;
  cursor: pointer;
  text-align: left;
  border-left: 3px solid transparent;
}

.nav-item:hover {
  background: #374151;
  color: white;
}

.nav-item.active,
.nav-item.router-link-active {
  background: #374151;
  color: #3b82f6;
  border-left-color: #3b82f6;
}

.nav-icon {
  width: 1.25rem;
  height: 1.25rem;
  margin-right: 0.75rem;
  flex-shrink: 0;
}

.chevron {
  width: 1rem;
  height: 1rem;
  margin-left: auto;
  transition: transform 0.2s;
}

.chevron.open {
  transform: rotate(180deg);
}

/* Submenu */
.submenu {
  background: #111827;
}

.submenu-enter-active,
.submenu-leave-active {
  transition: all 0.2s ease;
  max-height: 500px;
  overflow: hidden;
}

.submenu-enter-from,
.submenu-leave-to {
  max-height: 0;
  opacity: 0;
}

.submenu-item {
  display: flex;
  align-items: center;
  padding: 0.625rem 1.25rem 0.625rem 3.25rem;
  color: #9ca3af;
  text-decoration: none;
  font-size: 0.875rem;
  transition: all 0.2s;
}

.submenu-item:hover {
  background: #1f2937;
  color: white;
}

.submenu-item.active,
.submenu-item.router-link-active {
  color: #3b82f6;
  background: #1f2937;
}

.submenu-icon {
  width: 1rem;
  height: 1rem;
  margin-right: 0.5rem;
}

/* Search Results */
.search-results {
  padding: 0.5rem 0;
}

.search-results-title {
  padding: 0.5rem 1.25rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: #9ca3af;
  text-transform: uppercase;
  margin: 0;
}

.search-result-item {
  display: block;
  padding: 0.75rem 1.25rem;
  color: #d1d5db;
  text-decoration: none;
  transition: all 0.2s;
}

.search-result-item:hover {
  background: #374151;
  color: white;
}

.result-breadcrumb {
  font-size: 0.875rem;
}

.no-results {
  padding: 1.5rem 1.25rem;
  color: #9ca3af;
  text-align: center;
  font-size: 0.875rem;
}
</style>
