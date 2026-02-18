<template>
  <nav class="breadcrumb-nav py-4 px-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
    <ol class="flex items-center space-x-2 text-sm">
      <li
        v-for="(crumb, index) in breadcrumbs"
        :key="index"
        class="flex items-center"
      >
        <!-- Home Icon for first item -->
        <router-link
          v-if="index === 0"
          :to="crumb.path || '/'"
          class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
        >
          <svg
            class="w-5 h-5"
            fill="currentColor"
            viewBox="0 0 20 20"
          >
            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
          </svg>
        </router-link>

        <!-- Regular breadcrumb item -->
        <template v-else>
          <!-- Separator -->
          <svg
            class="w-5 h-5 text-gray-400 mx-2"
            fill="currentColor"
            viewBox="0 0 20 20"
          >
            <path
              fill-rule="evenodd"
              d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
              clip-rule="evenodd"
            />
          </svg>

          <!-- Link or text -->
          <router-link
            v-if="crumb.path && index < breadcrumbs.length - 1"
            :to="crumb.path"
            class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 transition-colors"
          >
            {{ crumb.label }}
          </router-link>
          <span
            v-else
            class="text-gray-900 dark:text-white font-medium"
            :class="{ 'cursor-default': index === breadcrumbs.length - 1 }"
          >
            {{ crumb.label }}
          </span>
        </template>
      </li>
    </ol>
  </nav>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';

interface Breadcrumb {
  label: string;
  path?: string;
}

interface Props {
  breadcrumbs?: Breadcrumb[];
  autoGenerate?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  autoGenerate: true,
});

const route = useRoute();

const breadcrumbs = computed(() => {
  // Use provided breadcrumbs if available
  if (props.breadcrumbs && props.breadcrumbs.length > 0) {
    return props.breadcrumbs;
  }

  // Auto-generate from route if enabled
  if (props.autoGenerate) {
    return generateBreadcrumbs();
  }

  return [];
});

const generateBreadcrumbs = (): Breadcrumb[] => {
  const crumbs: Breadcrumb[] = [
    { label: 'Home', path: '/' }
  ];

  // Get breadcrumbs from route meta if available
  if (route.meta.breadcrumbs) {
    return [...crumbs, ...(route.meta.breadcrumbs as Breadcrumb[])];
  }

  // Generate from path segments
  const pathSegments = route.path.split('/').filter(segment => segment);
  let currentPath = '';

  pathSegments.forEach((segment, index) => {
    currentPath += `/${segment}`;
    
    // Skip dynamic segments (starting with :)
    if (segment.startsWith(':')) return;

    // Format segment as label (capitalize, replace hyphens)
    const label = segment
      .split('-')
      .map(word => word.charAt(0).toUpperCase() + word.slice(1))
      .join(' ');

    crumbs.push({
      label,
      path: index < pathSegments.length - 1 ? currentPath : undefined,
    });
  });

  return crumbs;
};
</script>
