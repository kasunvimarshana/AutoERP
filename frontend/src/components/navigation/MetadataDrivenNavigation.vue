<template>
  <nav class="metadata-driven-navigation">
    <div class="space-y-1">
      <template
        v-for="item in visibleNavigationItems"
        :key="item.id"
      >
        <!-- Top Level Item -->
        <div>
          <!-- Item with children -->
          <template v-if="item.children && item.children.length > 0">
            <button
              class="w-full flex items-center justify-between px-4 py-2 text-sm font-medium rounded-md transition-colors"
              :class="getItemClasses(item)"
              @click="toggleExpanded(item.id)"
            >
              <span class="flex items-center">
                <component
                  :is="getIcon(item.icon)"
                  v-if="item.icon"
                  class="mr-3 h-5 w-5"
                />
                {{ item.label }}
                <span
                  v-if="item.badge"
                  class="ml-auto inline-block py-0.5 px-2 text-xs rounded-full"
                  :class="getBadgeClasses(item.badge.variant)"
                >
                  {{ item.badge.value }}
                </span>
              </span>
              <svg
                class="h-5 w-5 transition-transform"
                :class="{ 'rotate-90': isExpanded(item.id) }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 5l7 7-7 7"
                />
              </svg>
            </button>

            <!-- Children -->
            <transition name="slide-down">
              <div
                v-if="isExpanded(item.id)"
                class="mt-1 space-y-1 pl-11"
              >
                <router-link
                  v-for="child in item.children"
                  :key="child.id"
                  :to="child.path || '#'"
                  class="block px-4 py-2 text-sm rounded-md transition-colors"
                  :class="getItemClasses(child)"
                  active-class="bg-primary-100 text-primary-700"
                >
                  <span class="flex items-center">
                    <component
                      :is="getIcon(child.icon)"
                      v-if="child.icon"
                      class="mr-3 h-4 w-4"
                    />
                    {{ child.label }}
                    <span
                      v-if="child.badge"
                      class="ml-auto inline-block py-0.5 px-2 text-xs rounded-full"
                      :class="getBadgeClasses(child.badge.variant)"
                    >
                      {{ child.badge.value }}
                    </span>
                  </span>
                </router-link>
              </div>
            </transition>
          </template>

          <!-- Item without children -->
          <router-link
            v-else
            :to="item.path || '#'"
            class="flex items-center px-4 py-2 text-sm font-medium rounded-md transition-colors"
            :class="getItemClasses(item)"
            active-class="bg-primary-100 text-primary-700"
          >
            <component
              :is="getIcon(item.icon)"
              v-if="item.icon"
              class="mr-3 h-5 w-5"
            />
            {{ item.label }}
            <span
              v-if="item.badge"
              class="ml-auto inline-block py-0.5 px-2 text-xs rounded-full"
              :class="getBadgeClasses(item.badge.variant)"
            >
              {{ item.badge.value }}
            </span>
          </router-link>
        </div>
      </template>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useMetadataStore } from '@/stores/metadata';
import { useModuleStore } from '@/stores/modules';
import { DynamicRouteGenerator } from '@/router/dynamicRoutes';
import type { NavigationItem } from '@/router/dynamicRoutes';
import * as HeroIcons from '@heroicons/vue/24/outline';

interface Props {
  /** Use module store navigation or custom navigation items */
  useModules?: boolean;
  /** Custom navigation items */
  items?: NavigationItem[];
}

const props = withDefaults(defineProps<Props>(), {
  useModules: true,
  items: () => []
});

const metadataStore = useMetadataStore();
const moduleStore = useModuleStore();

// State
const expandedItems = ref<Set<string>>(new Set());

// Computed
const navigationItems = computed<NavigationItem[]>(() => {
  if (props.items && props.items.length > 0) {
    return props.items;
  }

  if (props.useModules && moduleStore.modules) {
    const userPermissions = metadataStore.permissions.user;
    return DynamicRouteGenerator.generateNavigation(
      moduleStore.modules,
      userPermissions
    );
  }

  // Fallback to metadata store navigation
  return metadataStore.visibleNavigation as any[] || [];
});

const visibleNavigationItems = computed(() => {
  return navigationItems.value.filter(item => {
    // Check visibility
    if (item.visible === false) return false;

    // Check permissions
    if (item.permission) {
      return metadataStore.hasPermission(item.permission);
    }

    // Check if any children are visible (for parent items)
    if (item.children && item.children.length > 0) {
      const hasVisibleChildren = item.children.some(child => {
        if (child.visible === false) return false;
        if (child.permission) {
          return metadataStore.hasPermission(child.permission);
        }
        return true;
      });
      return hasVisibleChildren;
    }

    return true;
  });
});

// Methods
const toggleExpanded = (itemId: string) => {
  if (expandedItems.value.has(itemId)) {
    expandedItems.value.delete(itemId);
  } else {
    expandedItems.value.add(itemId);
  }
};

const isExpanded = (itemId: string): boolean => {
  return expandedItems.value.has(itemId);
};

const getItemClasses = (item: NavigationItem): string => {
  const base = 'hover:bg-gray-100 hover:text-gray-900';
  const disabled = item.visible === false ? 'opacity-50 cursor-not-allowed' : '';
  return `${base} ${disabled} text-gray-700`;
};

const getBadgeClasses = (variant: string): string => {
  const variants: Record<string, string> = {
    primary: 'bg-primary-100 text-primary-800',
    success: 'bg-green-100 text-green-800',
    warning: 'bg-yellow-100 text-yellow-800',
    danger: 'bg-red-100 text-red-800'
  };
  return variants[variant] || variants.primary;
};

const getIcon = (iconName: string) => {
  // Convert icon name to PascalCase for HeroIcons
  const pascalCase = iconName
    .split('-')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join('');
  
  const iconComponent = (HeroIcons as any)[`${pascalCase}Icon`];
  
  if (!iconComponent) {
    console.warn(`Icon not found: ${iconName}`);
    return (HeroIcons as any).QuestionMarkCircleIcon;
  }
  
  return iconComponent;
};

// Lifecycle
onMounted(() => {
  // Auto-expand items with active children
  navigationItems.value.forEach(item => {
    if (item.children && item.children.length > 0) {
      const hasActiveChild = item.children.some(child => {
        if (!child.path) return false;
        return window.location.pathname.startsWith(child.path);
      });
      
      if (hasActiveChild) {
        expandedItems.value.add(item.id);
      }
    }
  });
});
</script>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.2s ease;
  max-height: 500px;
  overflow: hidden;
}

.slide-down-enter-from,
.slide-down-leave-to {
  max-height: 0;
  opacity: 0;
}
</style>
