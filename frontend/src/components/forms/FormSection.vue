<template>
  <div
    class="form-section"
    :class="{ 'mb-6': !isLast }"
  >
    <div
      v-if="section.title || section.description"
      class="form-section-header"
      :class="{ 'cursor-pointer': section.collapsible }"
      @click="section.collapsible && toggleSection()"
    >
      <div class="flex items-center justify-between">
        <div class="flex-1">
          <h3
            v-if="section.title"
            class="text-lg font-medium text-gray-900 dark:text-white"
          >
            {{ section.title }}
          </h3>
          <p
            v-if="section.description"
            class="mt-1 text-sm text-gray-600 dark:text-gray-400"
          >
            {{ section.description }}
          </p>
        </div>
        <button
          v-if="section.collapsible"
          type="button"
          class="ml-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-transform"
          :class="{ 'rotate-180': !isExpanded }"
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
              d="M19 9l-7 7-7-7"
            />
          </svg>
        </button>
      </div>
    </div>

    <transition
      enter-active-class="transition-all duration-300 ease-out"
      leave-active-class="transition-all duration-200 ease-in"
      enter-from-class="opacity-0 max-h-0"
      enter-to-class="opacity-100 max-h-screen"
      leave-from-class="opacity-100 max-h-screen"
      leave-to-class="opacity-0 max-h-0"
    >
      <div
        v-show="isExpanded"
        class="form-section-content bg-white dark:bg-gray-800 shadow rounded-lg p-6 mt-4"
      >
        <div
          :class="getLayoutClass"
        >
          <slot :fields="section.fields" />
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import type { FormSectionMetadata } from '@/types/metadata';

interface Props {
  section: FormSectionMetadata & {
    layout?: '1-column' | '2-column' | '3-column' | 'auto';
  };
  isLast?: boolean;
}

const props = defineProps<Props>();

const isExpanded = ref(true);

const getLayoutClass = computed(() => {
  const layout = props.section.layout || '2-column';
  
  const layoutClasses: Record<string, string> = {
    '1-column': 'grid grid-cols-1 gap-6',
    '2-column': 'grid grid-cols-1 gap-6 sm:grid-cols-2',
    '3-column': 'grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3',
    'auto': 'space-y-6',
  };

  return layoutClasses[layout] || layoutClasses['2-column'];
});

const toggleSection = () => {
  isExpanded.value = !isExpanded.value;
};

onMounted(() => {
  // Set initial expanded state from section metadata
  if (props.section.collapsible && props.section.defaultExpanded !== undefined) {
    isExpanded.value = props.section.defaultExpanded;
  }
});

defineExpose({
  isExpanded,
  toggleSection,
});
</script>

<style scoped>
.form-section-header {
  @apply transition-colors duration-200;
}

.form-section-header.cursor-pointer:hover {
  @apply opacity-90;
}
</style>
