<template>
  <div class="workflow-viewer bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
    <!-- Workflow Header -->
    <div class="mb-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        {{ workflow?.name || 'Workflow' }}
      </h3>
      <p
        v-if="currentState"
        class="mt-1 text-sm text-gray-500 dark:text-gray-400"
      >
        Current Status:
        <span
          class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
          :class="getStateClasses(currentState)"
        >
          {{ currentState.label }}
        </span>
      </p>
    </div>

    <!-- Loading State -->
    <div
      v-if="loading"
      class="flex items-center justify-center py-12"
    >
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600" />
    </div>

    <!-- Error State -->
    <div
      v-else-if="error"
      class="bg-red-50 border border-red-200 rounded-lg p-4"
    >
      <p class="text-red-800">
        {{ error }}
      </p>
    </div>

    <!-- Workflow Visualization -->
    <div
      v-else-if="workflow"
      class="workflow-diagram"
    >
      <!-- States -->
      <div class="flex items-center justify-between mb-8 overflow-x-auto pb-4">
        <div
          v-for="(state, index) in workflow.states"
          :key="state.id"
          class="flex items-center"
        >
          <!-- State Node -->
          <div
            class="flex-shrink-0 relative"
            :class="{ 'ml-8': index > 0 }"
          >
            <div
              class="w-32 h-32 rounded-full flex items-center justify-center border-4 transition-all"
              :class="getStateNodeClasses(state)"
            >
              <div class="text-center">
                <div class="text-sm font-semibold">
                  {{ state.label }}
                </div>
                <div
                  v-if="state.type === 'initial'"
                  class="text-xs mt-1 opacity-75"
                >
                  Start
                </div>
                <div
                  v-if="state.type === 'final'"
                  class="text-xs mt-1 opacity-75"
                >
                  End
                </div>
              </div>
            </div>
            
            <!-- Current State Indicator -->
            <div
              v-if="currentState && currentState.id === state.id"
              class="absolute -top-2 -right-2 w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center"
            >
              <svg
                class="w-4 h-4 text-white"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  fill-rule="evenodd"
                  d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                  clip-rule="evenodd"
                />
              </svg>
            </div>
          </div>

          <!-- Transition Arrow -->
          <div
            v-if="index < workflow.states.length - 1"
            class="flex-shrink-0 mx-4"
          >
            <svg
              class="w-8 h-8 text-gray-400"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M13 7l5 5m0 0l-5 5m5-5H6"
              />
            </svg>
          </div>
        </div>
      </div>

      <!-- Available Transitions -->
      <div
        v-if="availableTransitions.length > 0"
        class="mt-8"
      >
        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-4">
          Available Actions
        </h4>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <button
            v-for="transition in availableTransitions"
            :key="`${transition.from}-${transition.to}`"
            :disabled="isTransitioning || !canExecuteTransition(transition)"
            class="relative flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            :class="{
              'border-primary-300 bg-primary-50 dark:bg-primary-900/20': canExecuteTransition(transition),
              'border-gray-200 dark:border-gray-700 opacity-50 cursor-not-allowed': !canExecuteTransition(transition)
            }"
            @click="handleTransition(transition)"
          >
            <div class="flex-1 text-left">
              <div class="text-sm font-medium text-gray-900 dark:text-white">
                {{ transition.label }}
              </div>
              <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Move to: {{ getStateById(transition.to)?.label }}
              </div>
            </div>
            <svg
              class="w-5 h-5 text-gray-400"
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
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import type { WorkflowMetadata, WorkflowStateMetadata, WorkflowTransitionMetadata } from '@/types/metadata';
import { useMetadataApi } from '@/composables/useMetadataApi';
import { useMetadataStore } from '@/stores/metadata';

interface Props {
  workflowId: string;
  recordId: string | number;
  currentStateId?: string;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  transitionComplete: [{ from: string; to: string }];
  error: [error: string];
}>();

const { getWorkflowMetadata, executeWorkflowTransition } = useMetadataApi();
const metadataStore = useMetadataStore();

const workflow = ref<WorkflowMetadata | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);
const isTransitioning = ref(false);

const currentState = computed(() => {
  if (!workflow.value || !props.currentStateId) return null;
  return workflow.value.states.find(s => s.id === props.currentStateId);
});

const availableTransitions = computed(() => {
  if (!workflow.value || !currentState.value) return [];
  
  return workflow.value.transitions.filter(t => t.from === currentState.value!.id);
});

const getStateById = (stateId: string) => {
  return workflow.value?.states.find(s => s.id === stateId);
};

const getStateClasses = (state: WorkflowStateMetadata) => {
  const classes: Record<string, string> = {
    initial: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
    intermediate: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
    final: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
  };

  return classes[state.type] || classes.intermediate;
};

const getStateNodeClasses = (state: WorkflowStateMetadata) => {
  const isActive = currentState.value?.id === state.id;
  
  if (isActive) {
    return 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 text-blue-900 dark:text-blue-100 shadow-lg';
  }

  return 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300';
};

const canExecuteTransition = (transition: WorkflowTransitionMetadata) => {
  if (transition.permissions && transition.permissions.length > 0) {
    return metadataStore.hasAllPermissions(transition.permissions);
  }
  return true;
};

const handleTransition = async (transition: WorkflowTransitionMetadata) => {
  if (!canExecuteTransition(transition) || isTransitioning.value) return;

  isTransitioning.value = true;
  error.value = null;

  try {
    await executeWorkflowTransition(
      props.workflowId,
      props.recordId,
      `${transition.from}-${transition.to}`,
      {}
    );

    emit('transitionComplete', {
      from: transition.from,
      to: transition.to,
    });

    await loadWorkflow();
  } catch (err: any) {
    error.value = err.message || 'Failed to execute transition';
    emit('error', error.value);
  } finally {
    isTransitioning.value = false;
  }
};

const loadWorkflow = async () => {
  loading.value = true;
  error.value = null;

  try {
    const data = await getWorkflowMetadata(props.workflowId);
    workflow.value = data;
  } catch (err: any) {
    error.value = err.message || 'Failed to load workflow';
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadWorkflow();
});

defineExpose({
  refresh: loadWorkflow,
});
</script>

<style scoped>
.workflow-diagram {
  @apply overflow-x-auto;
}
</style>
