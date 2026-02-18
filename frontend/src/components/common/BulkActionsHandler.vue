<template>
  <div class="bulk-actions-handler">
    <!-- Bulk Actions Toolbar -->
    <transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 translate-y-1"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-1"
    >
      <div
        v-if="selectedCount > 0"
        class="sticky top-0 z-10 bg-primary-50 border border-primary-200 rounded-lg shadow-sm px-4 py-3 mb-4"
      >
        <div class="flex items-center justify-between">
          <!-- Selection Info -->
          <div class="flex items-center space-x-4">
            <div class="flex items-center">
              <input
                type="checkbox"
                :checked="allSelected"
                :indeterminate="someSelected"
                class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                @change="toggleSelectAll"
              >
              <span class="ml-3 text-sm font-medium text-gray-700">
                {{ selectedCount }} {{ selectedCount === 1 ? 'item' : 'items' }} selected
              </span>
            </div>

            <button
              v-if="selectedCount < totalCount && !selectingAll"
              class="text-sm text-primary-600 hover:text-primary-700 font-medium"
              @click="selectAll"
            >
              Select all {{ totalCount }} items
            </button>

            <button
              v-if="selectingAll"
              class="text-sm text-gray-600 hover:text-gray-700"
              @click="deselectAll"
            >
              Clear selection
            </button>
          </div>

          <!-- Bulk Actions Menu -->
          <div class="flex items-center space-x-2">
            <Menu
              as="div"
              class="relative inline-block text-left"
            >
              <div>
                <MenuButton
                  class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500"
                >
                  <span>Bulk Actions</span>
                  <ChevronDownIcon
                    class="ml-2 -mr-1 h-5 w-5"
                    aria-hidden="true"
                  />
                </MenuButton>
              </div>

              <transition
                enter-active-class="transition ease-out duration-100"
                enter-from-class="transform opacity-0 scale-95"
                enter-to-class="transform opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75"
                leave-from-class="transform opacity-100 scale-100"
                leave-to-class="transform opacity-0 scale-95"
              >
                <MenuItems
                  class="absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                >
                  <div class="py-1">
                    <MenuItem
                      v-for="action in availableActions"
                      :key="action.id"
                      v-slot="{ active }"
                    >
                      <button
                        :disabled="action.disabled"
                        class="w-full text-left px-4 py-2 text-sm flex items-center"
                        :class="[
                          active ? 'bg-gray-100 text-gray-900' : 'text-gray-700',
                          action.disabled ? 'opacity-50 cursor-not-allowed' : '',
                          action.variant === 'danger' ? 'text-red-600' : ''
                        ]"
                        @click="executeBulkAction(action)"
                      >
                        <component
                          :is="getIcon(action.icon)"
                          v-if="action.icon"
                          class="mr-3 h-5 w-5"
                          :class="action.variant === 'danger' ? 'text-red-500' : 'text-gray-400'"
                        />
                        {{ action.label }}
                      </button>
                    </MenuItem>
                  </div>
                </MenuItems>
              </transition>
            </Menu>

            <button
              class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500"
              @click="deselectAll"
            >
              <XMarkIcon class="h-5 w-5" />
            </button>
          </div>
        </div>

        <!-- Progress Bar -->
        <div
          v-if="processing"
          class="mt-3"
        >
          <div class="flex items-center justify-between text-sm text-gray-600 mb-1">
            <span>Processing {{ processedCount }} of {{ selectedCount }}</span>
            <span>{{ Math.round((processedCount / selectedCount) * 100) }}%</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-2">
            <div
              class="bg-primary-600 h-2 rounded-full transition-all duration-300"
              :style="{ width: `${(processedCount / selectedCount) * 100}%` }"
            />
          </div>
        </div>
      </div>
    </transition>

    <!-- Confirmation Modal -->
    <TransitionRoot
      as="template"
      :show="showConfirmModal"
    >
      <Dialog
        as="div"
        class="relative z-50"
        @close="showConfirmModal = false"
      >
        <TransitionChild
          as="template"
          enter="ease-out duration-300"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="ease-in duration-200"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
        </TransitionChild>

        <div class="fixed inset-0 z-10 overflow-y-auto">
          <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <TransitionChild
              as="template"
              enter="ease-out duration-300"
              enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
              enter-to="opacity-100 translate-y-0 sm:scale-100"
              leave="ease-in duration-200"
              leave-from="opacity-100 translate-y-0 sm:scale-100"
              leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
              <DialogPanel
                class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6"
              >
                <div class="sm:flex sm:items-start">
                  <div
                    class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10"
                  >
                    <ExclamationTriangleIcon
                      class="h-6 w-6 text-red-600"
                      aria-hidden="true"
                    />
                  </div>
                  <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                    <DialogTitle
                      as="h3"
                      class="text-base font-semibold leading-6 text-gray-900"
                    >
                      {{ confirmModalData.title }}
                    </DialogTitle>
                    <div class="mt-2">
                      <p class="text-sm text-gray-500">
                        {{ confirmModalData.message }}
                      </p>
                    </div>
                  </div>
                </div>
                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                  <button
                    type="button"
                    class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto"
                    @click="confirmAction"
                  >
                    Confirm
                  </button>
                  <button
                    type="button"
                    class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto"
                    @click="showConfirmModal = false"
                  >
                    Cancel
                  </button>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import {
  Menu,
  MenuButton,
  MenuItems,
  MenuItem,
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionRoot,
  TransitionChild
} from '@headlessui/vue';
import {
  ChevronDownIcon,
  XMarkIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline';
import * as HeroIcons from '@heroicons/vue/24/outline';
import type { ActionMetadata } from '@/types/metadata';

interface Props {
  selectedItems: any[];
  totalCount: number;
  actions: ActionMetadata[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
  'execute-action': [actionId: string, items: any[]];
  'select-all': [];
  'deselect-all': [];
  'toggle-select-all': [];
}>();

// State
const processing = ref(false);
const processedCount = ref(0);
const selectingAll = ref(false);
const showConfirmModal = ref(false);
const confirmModalData = ref({
  title: '',
  message: '',
  actionId: '',
  action: null as ActionMetadata | null
});

// Computed
const selectedCount = computed(() => props.selectedItems.length);

const allSelected = computed(() => {
  return selectedCount.value === props.totalCount && props.totalCount > 0;
});

const someSelected = computed(() => {
  return selectedCount.value > 0 && selectedCount.value < props.totalCount;
});

const availableActions = computed(() => {
  return props.actions.filter(action => {
    if (typeof action.visible === 'function') {
      return action.visible();
    }
    return action.visible !== false;
  });
});

// Methods
const selectAll = () => {
  selectingAll.value = true;
  emit('select-all');
};

const deselectAll = () => {
  selectingAll.value = false;
  emit('deselect-all');
};

const toggleSelectAll = () => {
  emit('toggle-select-all');
};

const executeBulkAction = async (action: ActionMetadata) => {
  // Check if action requires confirmation
  if (action.confirm) {
    confirmModalData.value = {
      title: action.confirm.title || 'Confirm Action',
      message: action.confirm.message || `Are you sure you want to ${action.label.toLowerCase()} ${selectedCount.value} items?`,
      actionId: action.id,
      action
    };
    showConfirmModal.value = true;
    return;
  }

  await performBulkAction(action);
};

const confirmAction = async () => {
  showConfirmModal.value = false;
  
  if (confirmModalData.value.action) {
    await performBulkAction(confirmModalData.value.action);
  }
};

const performBulkAction = async (action: ActionMetadata) => {
  processing.value = true;
  processedCount.value = 0;

  try {
    // Emit action to parent component
    emit('execute-action', action.id, props.selectedItems);

    // Simulate progress for demo
    const batchSize = 10;
    const batches = Math.ceil(selectedCount.value / batchSize);

    for (let i = 0; i < batches; i++) {
      await new Promise(resolve => setTimeout(resolve, 300));
      processedCount.value = Math.min((i + 1) * batchSize, selectedCount.value);
    }

    // Success - deselect all
    deselectAll();
  } catch (error) {
    console.error('Bulk action failed:', error);
  } finally {
    processing.value = false;
    processedCount.value = 0;
  }
};

const getIcon = (iconName: string) => {
  const pascalCase = iconName
    .split('-')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join('');
  
  return (HeroIcons as any)[`${pascalCase}Icon`] || (HeroIcons as any).QuestionMarkCircleIcon;
};
</script>
