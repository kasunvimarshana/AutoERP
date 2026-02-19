<template>
  <nav class="flex items-center justify-between border-t border-gray-200 px-4 sm:px-0">
    <div class="-mt-px flex w-0 flex-1">
      <button
        :disabled="currentPage <= 1"
        :class="previousButtonClasses"
        @click="goToPage(currentPage - 1)"
      >
        <svg class="mr-3 h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path fill-rule="evenodd" d="M18 10a.75.75 0 01-.75.75H4.66l2.1 1.95a.75.75 0 11-1.02 1.1l-3.5-3.25a.75.75 0 010-1.1l3.5-3.25a.75.75 0 111.02 1.1l-2.1 1.95h12.59A.75.75 0 0118 10z" clip-rule="evenodd" />
        </svg>
        Previous
      </button>
    </div>
    <div class="hidden md:-mt-px md:flex">
      <button
        v-for="page in visiblePages"
        :key="page"
        :class="pageButtonClasses(page)"
        @click="goToPage(page)"
      >
        {{ page }}
      </button>
    </div>
    <div class="-mt-px flex w-0 flex-1 justify-end">
      <button
        :disabled="currentPage >= totalPages"
        :class="nextButtonClasses"
        @click="goToPage(currentPage + 1)"
      >
        Next
        <svg class="ml-3 h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path fill-rule="evenodd" d="M2 10a.75.75 0 01.75-.75h12.59l-2.1-1.95a.75.75 0 111.02-1.1l3.5 3.25a.75.75 0 010 1.1l-3.5 3.25a.75.75 0 11-1.02-1.1l2.1-1.95H2.75A.75.75 0 012 10z" clip-rule="evenodd" />
        </svg>
      </button>
    </div>
  </nav>
  <div class="flex items-center justify-between px-4 py-3 sm:px-6">
    <div class="flex flex-1 justify-between sm:hidden">
      <span class="text-sm text-gray-700">
        Page <span class="font-medium">{{ currentPage }}</span> of <span class="font-medium">{{ totalPages }}</span>
      </span>
    </div>
    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
      <div>
        <p class="text-sm text-gray-700">
          Showing
          <span class="font-medium">{{ startItem }}</span>
          to
          <span class="font-medium">{{ endItem }}</span>
          of
          <span class="font-medium">{{ total }}</span>
          results
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  currentPage: {
    type: Number,
    required: true,
  },
  totalPages: {
    type: Number,
    required: true,
  },
  total: {
    type: Number,
    required: true,
  },
  perPage: {
    type: Number,
    required: true,
  },
  maxVisibleButtons: {
    type: Number,
    default: 7,
  },
});

const emit = defineEmits(['page-change']);

const startItem = computed(() => {
  return (props.currentPage - 1) * props.perPage + 1;
});

const endItem = computed(() => {
  return Math.min(props.currentPage * props.perPage, props.total);
});

const visiblePages = computed(() => {
  const pages = [];
  const halfVisible = Math.floor(props.maxVisibleButtons / 2);
  
  let start = Math.max(1, props.currentPage - halfVisible);
  let end = Math.min(props.totalPages, props.currentPage + halfVisible);
  
  if (end - start < props.maxVisibleButtons - 1) {
    if (start === 1) {
      end = Math.min(props.totalPages, start + props.maxVisibleButtons - 1);
    } else {
      start = Math.max(1, end - props.maxVisibleButtons + 1);
    }
  }
  
  for (let i = start; i <= end; i++) {
    pages.push(i);
  }
  
  return pages;
});

const previousButtonClasses = computed(() => {
  const baseClasses = 'inline-flex items-center border-t-2 border-transparent pt-4 pr-1 text-sm font-medium';
  const disabledClasses = props.currentPage <= 1
    ? 'text-gray-300 cursor-not-allowed'
    : 'text-gray-500 hover:border-gray-300 hover:text-gray-700';
  
  return `${baseClasses} ${disabledClasses}`;
});

const nextButtonClasses = computed(() => {
  const baseClasses = 'inline-flex items-center border-t-2 border-transparent pt-4 pl-1 text-sm font-medium';
  const disabledClasses = props.currentPage >= props.totalPages
    ? 'text-gray-300 cursor-not-allowed'
    : 'text-gray-500 hover:border-gray-300 hover:text-gray-700';
  
  return `${baseClasses} ${disabledClasses}`;
});

function pageButtonClasses(page) {
  const baseClasses = 'inline-flex items-center border-t-2 px-4 pt-4 text-sm font-medium';
  const activeClasses = page === props.currentPage
    ? 'border-indigo-500 text-indigo-600'
    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700';
  
  return `${baseClasses} ${activeClasses}`;
}

function goToPage(page) {
  if (page >= 1 && page <= props.totalPages && page !== props.currentPage) {
    emit('page-change', page);
  }
}
</script>
