<template>
  <div class="list-widget">
    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
      <li
        v-for="(item, index) in data?.items || []"
        :key="index"
        class="py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
        :class="{ 'cursor-pointer': item.clickable }"
        @click="handleItemClick(item)"
      >
        <div class="flex items-center justify-between">
          <div class="flex-1 min-w-0">
            <!-- Title -->
            <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
              {{ item.title }}
            </div>
            
            <!-- Description -->
            <div
              v-if="item.description"
              class="text-sm text-gray-500 dark:text-gray-400 truncate"
            >
              {{ item.description }}
            </div>
            
            <!-- Meta Info -->
            <div
              v-if="item.meta"
              class="mt-1 flex items-center space-x-2"
            >
              <span
                v-for="(metaValue, metaKey) in item.meta"
                :key="metaKey"
                class="text-xs text-gray-500 dark:text-gray-400"
              >
                {{ metaValue }}
              </span>
            </div>
          </div>

          <!-- Badge -->
          <div
            v-if="item.badge"
            class="ml-4"
          >
            <span
              :class="getBadgeClasses(item.badge.variant)"
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
            >
              {{ item.badge.label }}
            </span>
          </div>

          <!-- Icon -->
          <div
            v-if="item.icon"
            class="ml-4 flex-shrink-0"
          >
            <component
              :is="getIcon(item.icon)"
              class="w-5 h-5 text-gray-400"
            />
          </div>
        </div>
      </li>
    </ul>

    <!-- Empty State -->
    <div
      v-if="!data?.items || data.items.length === 0"
      class="text-center py-8 text-gray-500"
    >
      No items to display
    </div>
  </div>
</template>

<script setup lang="ts">
import { ChevronRightIcon } from '@heroicons/vue/24/outline';

interface ListItem {
  title: string;
  description?: string;
  meta?: Record<string, string>;
  badge?: {
    label: string;
    variant: 'primary' | 'success' | 'warning' | 'danger' | 'info';
  };
  icon?: string;
  clickable?: boolean;
  data?: any;
}

interface ListData {
  items: ListItem[];
}

interface Props {
  data: ListData;
  config?: any;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  action: [{ action: string; data: any }];
}>();

const getBadgeClasses = (variant: string) => {
  const variants: Record<string, string> = {
    primary: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
    success: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
    warning: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
    danger: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
    info: 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400',
  };

  return variants[variant] || variants.info;
};

const getIcon = (iconName: string) => {
  // Default to chevron right if no specific icon
  return ChevronRightIcon;
};

const handleItemClick = (item: ListItem) => {
  if (!item.clickable) return;

  emit('action', {
    action: 'item-click',
    data: item.data || item,
  });
};
</script>
