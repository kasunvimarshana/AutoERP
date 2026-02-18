<template>
  <nav
    v-if="breadcrumbs.length > 0"
    class="breadcrumb-container"
    aria-label="Breadcrumb navigation"
  >
    <ol class="breadcrumb" role="list">
      <li
        v-for="(crumb, index) in breadcrumbs"
        :key="index"
        class="breadcrumb-item"
        :class="{ 'is-active': index === breadcrumbs.length - 1 }"
      >
        <!-- Home Icon for first item -->
        <HomeIcon
          v-if="index === 0 && showHomeIcon"
          class="home-icon"
          aria-hidden="true"
        />

        <!-- Link for all items except last -->
        <router-link
          v-if="index < breadcrumbs.length - 1 && crumb.path"
          :to="crumb.path"
          class="breadcrumb-link"
        >
          {{ crumb.label }}
        </router-link>

        <!-- Current page (no link) -->
        <span v-else class="breadcrumb-current" aria-current="page">
          {{ crumb.label }}
        </span>

        <!-- Separator -->
        <ChevronRightIcon
          v-if="index < breadcrumbs.length - 1"
          class="breadcrumb-separator"
          aria-hidden="true"
        />
      </li>
    </ol>

    <!-- Optional: Entity info badge -->
    <div v-if="showEntityBadge && currentEntity" class="entity-badge">
      <component
        :is="getIconComponent(currentEntity.icon)"
        class="entity-icon"
        aria-hidden="true"
      />
      <span>{{ currentEntity.label }}</span>
    </div>
  </nav>
</template>

<script setup>
import { computed, watch, ref } from 'vue';
import { useRoute } from 'vue-router';
import { HomeIcon, ChevronRightIcon } from '@heroicons/vue/24/outline';
import { generateBreadcrumbs } from '../../services/dynamicRoutesService';
import { getBreadcrumbFromNavigation } from '../../services/dynamicNavigationService';
import { useMetadataStore } from '../../stores/metadataStore';

const props = defineProps({
  /**
   * Breadcrumb items (overrides automatic generation)
   */
  items: {
    type: Array,
    default: null,
  },
  /**
   * Show home icon for first item
   */
  showHomeIcon: {
    type: Boolean,
    default: true,
  },
  /**
   * Show entity badge with current entity info
   */
  showEntityBadge: {
    type: Boolean,
    default: false,
  },
  /**
   * Source for breadcrumb generation: 'route' or 'navigation'
   */
  source: {
    type: String,
    default: 'route',
    validator: (value) => ['route', 'navigation'].includes(value),
  },
  /**
   * Custom separator (pass null to use default ChevronRight)
   */
  separator: {
    type: String,
    default: null,
  },
});

const route = useRoute();
const metadataStore = useMetadataStore();

const currentEntity = ref(null);

/**
 * Computed breadcrumbs
 */
const breadcrumbs = computed(() => {
  // Use provided items if available
  if (props.items) {
    return props.items;
  }

  // Generate from route metadata
  if (props.source === 'route' && route.meta?.breadcrumb) {
    return generateBreadcrumbs(route, route.params);
  }

  // Generate from navigation (requires navigation items to be loaded)
  if (props.source === 'navigation') {
    // This would require navigation items to be available
    // For now, fall back to simple path-based breadcrumbs
    return generatePathBreadcrumbs(route.path);
  }

  // Fallback: generate from path
  return generatePathBreadcrumbs(route.path);
});

/**
 * Generate breadcrumbs from path segments
 */
function generatePathBreadcrumbs(path) {
  const segments = path.split('/').filter(Boolean);
  const crumbs = [{ label: 'Home', path: '/' }];

  let currentPath = '';
  segments.forEach((segment, index) => {
    currentPath += `/${segment}`;
    
    // Format segment label
    const label = segment
      .split('-')
      .map(word => word.charAt(0).toUpperCase() + word.slice(1))
      .join(' ');

    crumbs.push({
      label,
      path: index < segments.length - 1 ? currentPath : null,
    });
  });

  return crumbs;
}

/**
 * Get icon component for entity
 */
function getIconComponent(iconName) {
  // This would import and return the appropriate icon component
  return HomeIcon;
}

/**
 * Load entity metadata if route has entity
 */
watch(
  () => route.meta?.entity,
  async (entityName) => {
    if (entityName && props.showEntityBadge) {
      try {
        currentEntity.value = await metadataStore.fetchEntity(entityName);
      } catch (error) {
        console.error('Failed to load entity metadata:', error);
        currentEntity.value = null;
      }
    } else {
      currentEntity.value = null;
    }
  },
  { immediate: true }
);
</script>

<style scoped>
.breadcrumb-container {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 0;
  margin-bottom: 1rem;
  gap: 1rem;
}

.breadcrumb {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  padding: 0;
  margin: 0;
  list-style: none;
  gap: 0.25rem;
}

.breadcrumb-item {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.875rem;
}

.breadcrumb-item.is-active {
  font-weight: 600;
}

.home-icon {
  width: 1rem;
  height: 1rem;
  color: #6b7280;
  margin-right: 0.25rem;
}

.breadcrumb-link {
  color: #3b82f6;
  text-decoration: none;
  transition: color 0.15s ease;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
}

.breadcrumb-link:hover {
  color: #2563eb;
  background: #eff6ff;
}

.breadcrumb-link:focus {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
}

.breadcrumb-current {
  color: #111827;
  padding: 0.25rem 0.5rem;
}

.breadcrumb-separator {
  width: 1rem;
  height: 1rem;
  color: #9ca3af;
  flex-shrink: 0;
  margin: 0 0.25rem;
}

/* Entity Badge */
.entity-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.375rem 0.75rem;
  background: #f3f4f6;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  color: #374151;
  flex-shrink: 0;
}

.entity-icon {
  width: 1rem;
  height: 1rem;
  color: #6b7280;
}

/* Responsive */
@media (max-width: 640px) {
  .breadcrumb-container {
    flex-direction: column;
    align-items: flex-start;
  }

  .breadcrumb-item {
    font-size: 0.8125rem;
  }

  .entity-badge {
    width: 100%;
  }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .breadcrumb-link {
    color: #60a5fa;
  }

  .breadcrumb-link:hover {
    color: #93c5fd;
    background: #1e3a8a;
  }

  .breadcrumb-current {
    color: #f3f4f6;
  }

  .entity-badge {
    background: #374151;
    color: #d1d5db;
  }
}

/* Accessibility */
.breadcrumb-item:focus-within {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
  border-radius: 0.25rem;
}

/* Print styles */
@media print {
  .breadcrumb-container {
    display: none;
  }
}
</style>
