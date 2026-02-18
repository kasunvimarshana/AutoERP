<template>
  <div class="workflow-viewer">
    <div
      v-if="loading"
      class="flex items-center justify-center h-64"
    >
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600" />
    </div>

    <div
      v-else-if="error"
      class="bg-red-50 border border-red-200 rounded-lg p-4"
    >
      <p class="text-sm text-red-600">
        {{ error }}
      </p>
    </div>

    <div
      v-else-if="workflow"
      class="space-y-6"
    >
      <!-- Workflow Header -->
      <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-2xl font-bold text-gray-900">
              {{ workflow.name }}
            </h2>
            <p class="mt-1 text-sm text-gray-600">
              Current State: 
              <span
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                :class="getStateClasses(currentState)"
              >
                {{ currentState?.label || 'Unknown' }}
              </span>
            </p>
          </div>
        </div>
      </div>

      <!-- Workflow Diagram -->
      <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">
          Workflow Diagram
        </h3>
        
        <div class="relative">
          <!-- States -->
          <div class="flex flex-wrap gap-4 mb-8">
            <div
              v-for="state in workflow.states"
              :key="state.id"
              class="flex flex-col items-center"
            >
              <div
                class="w-32 h-32 rounded-lg border-2 flex flex-col items-center justify-center p-4 transition-all"
                :class="[
                  state.id === currentStateId ? 'border-primary-500 bg-primary-50 shadow-lg' : 'border-gray-300 bg-white',
                  getStateTypeClasses(state.type)
                ]"
              >
                <component
                  :is="getStateIcon(state.type)"
                  class="h-8 w-8 mb-2"
                  :class="state.id === currentStateId ? 'text-primary-600' : 'text-gray-400'"
                />
                <span class="text-sm font-medium text-center">{{ state.label }}</span>
              </div>
              
              <!-- State Actions -->
              <div
                v-if="state.id === currentStateId && state.actions && state.actions.length > 0"
                class="mt-2 space-x-2"
              >
                <button
                  v-for="action in state.actions"
                  :key="action"
                  class="px-3 py-1 text-xs font-medium rounded-md bg-primary-100 text-primary-700 hover:bg-primary-200"
                  @click="executeStateAction(action)"
                >
                  {{ action }}
                </button>
              </div>
            </div>
          </div>

          <!-- Transitions (as arrows) -->
          <svg
            class="absolute top-0 left-0 w-full h-full pointer-events-none"
            style="z-index: -1;"
          >
            <defs>
              <marker
                id="arrowhead"
                markerWidth="10"
                markerHeight="7"
                refX="9"
                refY="3.5"
                orient="auto"
              >
                <polygon
                  points="0 0, 10 3.5, 0 7"
                  fill="#9CA3AF"
                />
              </marker>
            </defs>
            <!-- Arrow paths would be calculated dynamically -->
          </svg>
        </div>
      </div>

      <!-- Available Transitions -->
      <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">
          Available Actions
        </h3>
        
        <div
          v-if="availableTransitions.length === 0"
          class="text-sm text-gray-500"
        >
          No actions available for the current state.
        </div>

        <div
          v-else
          class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
        >
          <button
            v-for="transition in availableTransitions"
            :key="`${transition.from}-${transition.to}`"
            :disabled="!canExecuteTransition(transition)"
            class="flex items-center p-4 border-2 border-gray-200 rounded-lg hover:border-primary-500 hover:bg-primary-50 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
            @click="executeTransition(transition)"
          >
            <ArrowRightIcon class="h-5 w-5 mr-3 text-primary-600" />
            <div class="flex-1 text-left">
              <div class="font-medium text-gray-900">
                {{ transition.label }}
              </div>
              <div class="text-sm text-gray-500">
                → {{ getStateLabel(transition.to) }}
              </div>
            </div>
          </button>
        </div>
      </div>

      <!-- Transition History -->
      <div
        v-if="history && history.length > 0"
        class="bg-white border border-gray-200 rounded-lg p-6"
      >
        <h3 class="text-lg font-medium text-gray-900 mb-4">
          History
        </h3>
        
        <div class="flow-root">
          <ul class="-mb-8">
            <li
              v-for="(item, index) in history"
              :key="index"
              class="relative pb-8"
            >
              <span
                v-if="index !== history.length - 1"
                class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"
                aria-hidden="true"
              />
              <div class="relative flex space-x-3">
                <div>
                  <span class="h-8 w-8 rounded-full bg-primary-500 flex items-center justify-center ring-8 ring-white">
                    <CheckIcon class="h-5 w-5 text-white" />
                  </span>
                </div>
                <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                  <div>
                    <p class="text-sm text-gray-900">
                      {{ item.transition_label }}
                      <span class="text-gray-500">
                        from {{ item.from_state_label }} to {{ item.to_state_label }}
                      </span>
                    </p>
                    <p
                      v-if="item.comment"
                      class="mt-1 text-sm text-gray-500"
                    >
                      {{ item.comment }}
                    </p>
                  </div>
                  <div class="whitespace-nowrap text-right text-sm text-gray-500">
                    <time :datetime="item.created_at">{{ formatDate(item.created_at) }}</time>
                    <p class="text-xs">
                      by {{ item.user_name }}
                    </p>
                  </div>
                </div>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Transition Confirmation Modal -->
    <TransitionRoot
      as="template"
      :show="showTransitionModal"
    >
      <Dialog
        as="div"
        class="relative z-50"
        @close="showTransitionModal = false"
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
                <div>
                  <div class="mt-3 text-center sm:mt-5">
                    <DialogTitle
                      as="h3"
                      class="text-base font-semibold leading-6 text-gray-900"
                    >
                      {{ selectedTransition?.label }}
                    </DialogTitle>
                    <div class="mt-4">
                      <textarea
                        v-model="transitionComment"
                        rows="4"
                        placeholder="Add a comment (optional)"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                      />
                    </div>
                  </div>
                </div>
                <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                  <button
                    type="button"
                    :disabled="executing"
                    class="inline-flex w-full justify-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 sm:col-start-2 disabled:opacity-50"
                    @click="confirmTransition"
                  >
                    {{ executing ? 'Processing...' : 'Confirm' }}
                  </button>
                  <button
                    type="button"
                    class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:col-start-1 sm:mt-0"
                    @click="showTransitionModal = false"
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
import { ref, computed, onMounted } from 'vue';
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionRoot,
  TransitionChild
} from '@headlessui/vue';
import {
  ArrowRightIcon,
  CheckIcon,
  PlayIcon,
  PauseIcon,
  CheckCircleIcon
} from '@heroicons/vue/24/outline';
import { useMetadataApi } from '@/composables/useMetadataApi';
import { useMetadataStore } from '@/stores/metadata';
import type { WorkflowMetadata, WorkflowTransitionMetadata } from '@/types/metadata';

interface Props {
  workflowId: string;
  recordId: string | number;
  currentStateId?: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  'transition-complete': [stateId: string];
  'action-complete': [action: string];
}>();

const metadataApi = useMetadataApi();
const metadataStore = useMetadataStore();

// State
const workflow = ref<WorkflowMetadata | null>(null);
const loading = ref(false);
const error = ref<string | null>(null);
const executing = ref(false);
const history = ref<any[]>([]);
const showTransitionModal = ref(false);
const selectedTransition = ref<WorkflowTransitionMetadata | null>(null);
const transitionComment = ref('');

// Computed
const currentState = computed(() => {
  return workflow.value?.states.find(s => s.id === props.currentStateId);
});

const availableTransitions = computed(() => {
  if (!workflow.value || !props.currentStateId) return [];
  
  return workflow.value.transitions.filter(t => {
    if (t.from !== props.currentStateId) return false;
    
    // Check permissions
    if (t.permissions && t.permissions.length > 0) {
      return metadataStore.hasAnyPermission(t.permissions);
    }
    
    return true;
  });
});

// Methods
const loadWorkflow = async () => {
  loading.value = true;
  error.value = null;

  try {
    workflow.value = await metadataApi.getWorkflowMetadata(props.workflowId);
    await loadHistory();
  } catch (err: any) {
    error.value = err.message || 'Failed to load workflow';
  } finally {
    loading.value = false;
  }
};

const loadHistory = async () => {
  // Load transition history for this record
  // This would come from the backend
};

const executeTransition = (transition: WorkflowTransitionMetadata) => {
  selectedTransition.value = transition;
  showTransitionModal.value = true;
};

const confirmTransition = async () => {
  if (!selectedTransition.value) return;

  executing.value = true;
  
  try {
    const result = await metadataApi.executeWorkflowTransition(
      props.workflowId,
      props.recordId,
      selectedTransition.value.to,
      {
        comment: transitionComment.value
      }
    );

    emit('transition-complete', selectedTransition.value.to);
    showTransitionModal.value = false;
    transitionComment.value = '';
    
    // Reload history
    await loadHistory();
  } catch (err: any) {
    error.value = err.message || 'Transition failed';
  } finally {
    executing.value = false;
  }
};

const executeStateAction = async (action: string) => {
  try {
    await metadataApi.executeAction(action, {
      workflow_id: props.workflowId,
      record_id: props.recordId,
      state_id: props.currentStateId
    });

    emit('action-complete', action);
  } catch (err: any) {
    error.value = err.message || 'Action failed';
  }
};

const canExecuteTransition = (transition: WorkflowTransitionMetadata): boolean => {
  // Check conditions
  if (transition.conditions) {
    // Implement condition checking logic
  }
  
  return true;
};

const getStateLabel = (stateId: string): string => {
  const state = workflow.value?.states.find(s => s.id === stateId);
  return state?.label || stateId;
};

const getStateClasses = (state: any): string => {
  const typeClasses: Record<string, string> = {
    initial: 'bg-blue-100 text-blue-800',
    intermediate: 'bg-yellow-100 text-yellow-800',
    final: 'bg-green-100 text-green-800'
  };
  return typeClasses[state?.type || 'intermediate'] || 'bg-gray-100 text-gray-800';
};

const getStateTypeClasses = (type: string): string => {
  const classes: Record<string, string> = {
    initial: 'border-blue-300',
    final: 'border-green-300'
  };
  return classes[type] || '';
};

const getStateIcon = (type: string) => {
  const icons: Record<string, any> = {
    initial: PlayIcon,
    intermediate: PauseIcon,
    final: CheckCircleIcon
  };
  return icons[type] || CheckCircleIcon;
};

const formatDate = (date: string): string => {
  return new Date(date).toLocaleString();
};

// Lifecycle
onMounted(() => {
  loadWorkflow();
});
</script>
