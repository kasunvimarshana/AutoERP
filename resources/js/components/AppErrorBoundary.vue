<template>
  <slot v-if="!hasError" />
  <div
    v-else
    class="rounded-lg border border-red-200 bg-red-50 p-6 text-center space-y-3"
    role="alert"
  >
    <span class="text-4xl">⚠️</span>
    <h3 class="text-base font-semibold text-red-700">Something went wrong</h3>
    <p class="text-sm text-red-600">{{ errorMessage }}</p>
    <button
      class="mt-2 px-4 py-2 text-sm text-white bg-red-600 rounded-lg hover:bg-red-700"
      @click="reset"
    >
      Try again
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref, onErrorCaptured } from 'vue';

const hasError = ref(false);
const errorMessage = ref('An unexpected error occurred.');

onErrorCaptured((err: unknown) => {
  hasError.value = true;
  if (err instanceof Error) {
    errorMessage.value = err.message;
  }
  // Return false to stop propagation
  return false;
});

function reset(): void {
  hasError.value = false;
  errorMessage.value = 'An unexpected error occurred.';
}

defineExpose({ reset });
</script>
