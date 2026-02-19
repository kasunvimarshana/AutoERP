<template>
  <div
    v-if="show"
    class="rounded-md p-4 mb-4"
    :class="[
      type === 'success' ? 'bg-green-50 border border-green-200' : '',
      type === 'error' ? 'bg-red-50 border border-red-200' : '',
      type === 'warning' ? 'bg-yellow-50 border border-yellow-200' : '',
      type === 'info' ? 'bg-blue-50 border border-blue-200' : '',
    ]"
  >
    <div class="flex">
      <div class="flex-shrink-0">
        <svg
          v-if="type === 'success'"
          class="h-5 w-5 text-green-400"
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 20 20"
          fill="currentColor"
        >
          <path
            fill-rule="evenodd"
            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
            clip-rule="evenodd"
          />
        </svg>
        
        <svg
          v-if="type === 'error'"
          class="h-5 w-5 text-red-400"
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 20 20"
          fill="currentColor"
        >
          <path
            fill-rule="evenodd"
            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
            clip-rule="evenodd"
          />
        </svg>
      </div>
      
      <div class="ml-3 flex-1">
        <p
          class="text-sm font-medium"
          :class="[
            type === 'success' ? 'text-green-800' : '',
            type === 'error' ? 'text-red-800' : '',
            type === 'warning' ? 'text-yellow-800' : '',
            type === 'info' ? 'text-blue-800' : '',
          ]"
        >
          {{ message }}
        </p>
      </div>
      
      <div v-if="dismissible" class="ml-auto pl-3">
        <button
          @click="$emit('dismiss')"
          class="-mx-1.5 -my-1.5 rounded-md p-1.5 inline-flex focus:outline-none focus:ring-2 focus:ring-offset-2"
          :class="[
            type === 'success' ? 'text-green-500 hover:bg-green-100 focus:ring-green-600' : '',
            type === 'error' ? 'text-red-500 hover:bg-red-100 focus:ring-red-600' : '',
            type === 'warning' ? 'text-yellow-500 hover:bg-yellow-100 focus:ring-yellow-600' : '',
            type === 'info' ? 'text-blue-500 hover:bg-blue-100 focus:ring-blue-600' : '',
          ]"
        >
          <span class="sr-only">Dismiss</span>
          <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path
              fill-rule="evenodd"
              d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
              clip-rule="evenodd"
            />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  show: {
    type: Boolean,
    default: true,
  },
  type: {
    type: String,
    default: 'info',
    validator: (value) => ['success', 'error', 'warning', 'info'].includes(value),
  },
  message: {
    type: String,
    required: true,
  },
  dismissible: {
    type: Boolean,
    default: true,
  },
});

defineEmits(['dismiss']);
</script>
