<template>
  <div
    v-if="hasError"
    class="error-boundary"
  >
    <div class="error-boundary-content">
      <div class="error-icon">
        <svg
          class="w-16 h-16 text-red-500"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
          />
        </svg>
      </div>
      
      <h2 class="error-title">
        {{ errorTitle }}
      </h2>
      
      <p class="error-message">
        {{ errorMessage }}
      </p>

      <div
        v-if="showDetails && errorDetails"
        class="error-details"
      >
        <button
          class="details-toggle"
          @click="showStack = !showStack"
        >
          {{ showStack ? 'Hide' : 'Show' }} Error Details
        </button>
        
        <div
          v-if="showStack"
          class="stack-trace"
        >
          <pre>{{ errorDetails.stack || errorDetails.message }}</pre>
        </div>
      </div>

      <div class="error-actions">
        <button
          class="btn-primary"
          @click="handleRetry"
        >
          Try Again
        </button>
        
        <button
          class="btn-secondary"
          @click="handleGoHome"
        >
          Go to Dashboard
        </button>

        <button
          v-if="showReportButton"
          class="btn-secondary"
          @click="handleReportError"
        >
          Report Error
        </button>
      </div>
    </div>
  </div>
  
  <slot v-else />
</template>

<script setup lang="ts">
import { ref, onErrorCaptured, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useErrorHandler } from '@/composables/useErrorHandler';
import { useToast } from '@/composables/useToast';

interface Props {
  fallback?: boolean;
  showDetails?: boolean;
  showReportButton?: boolean;
  onError?: (error: Error) => void;
  onReset?: () => void;
}

const props = withDefaults(defineProps<Props>(), {
  fallback: true,
  showDetails: import.meta.env.DEV,
  showReportButton: true,
});

const emit = defineEmits<{
  error: [error: Error];
  retry: [];
  reset: [];
}>();

const router = useRouter();
const { handleError } = useErrorHandler();
const toast = useToast();

const hasError = ref(false);
const errorDetails = ref<any>(null);
const showStack = ref(false);

const errorTitle = computed(() => {
  if (!errorDetails.value) return 'Something went wrong';
  
  const code = errorDetails.value.code;
  if (code === 404) return 'Page Not Found';
  if (code === 403) return 'Access Denied';
  if (code === 401) return 'Unauthorized';
  if (code >= 500) return 'Server Error';
  
  return 'Something went wrong';
});

const errorMessage = computed(() => {
  if (!errorDetails.value) return 'An unexpected error occurred. Please try again.';
  
  return errorDetails.value.message || 'An unexpected error occurred. Please try again.';
});

// Capture errors in child components
onErrorCaptured((error: Error, instance, info) => {
  hasError.value = true;
  errorDetails.value = handleError(error, {
    component: instance?.$options?.name || 'Unknown',
    info,
  });

  // Call onError prop if provided
  if (props.onError) {
    props.onError(error);
  }

  // Emit error event
  emit('error', error);

  // Prevent error from propagating further
  return false;
});

const handleRetry = () => {
  hasError.value = false;
  errorDetails.value = null;
  showStack.value = false;

  // Call onReset prop if provided
  if (props.onReset) {
    props.onReset();
  }

  // Emit retry event
  emit('retry');

  // Reload current route
  router.go(0);
};

const handleGoHome = () => {
  hasError.value = false;
  errorDetails.value = null;
  router.push('/dashboard');
};

const handleReportError = () => {
  // Here you could send error details to an error tracking service
  // For now, we'll just copy to clipboard
  const errorReport = {
    timestamp: new Date().toISOString(),
    url: window.location.href,
    userAgent: navigator.userAgent,
    error: {
      message: errorDetails.value?.message,
      stack: errorDetails.value?.stack,
      context: errorDetails.value?.context,
    },
  };

  navigator.clipboard.writeText(JSON.stringify(errorReport, null, 2))
    .then(() => {
      toast.success('Error details copied to clipboard. Please share this with support.');
    })
    .catch(() => {
      toast.error('Failed to copy error details. Please take a screenshot.');
    });
};

// Expose reset method for parent components
defineExpose({
  reset: () => {
    hasError.value = false;
    errorDetails.value = null;
    emit('reset');
  },
});
</script>

<style scoped>
.error-boundary {
  min-height: 400px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  background-color: #f9fafb;
}

.error-boundary-content {
  max-width: 600px;
  text-align: center;
  background: white;
  border-radius: 0.5rem;
  padding: 2rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.error-icon {
  display: flex;
  justify-content: center;
  margin-bottom: 1rem;
}

.error-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #111827;
  margin-bottom: 0.5rem;
}

.error-message {
  color: #6b7280;
  margin-bottom: 1.5rem;
  line-height: 1.5;
}

.error-details {
  margin-bottom: 1.5rem;
}

.details-toggle {
  color: #3b82f6;
  font-size: 0.875rem;
  cursor: pointer;
  background: none;
  border: none;
  text-decoration: underline;
  padding: 0;
}

.details-toggle:hover {
  color: #2563eb;
}

.stack-trace {
  margin-top: 1rem;
  padding: 1rem;
  background: #f3f4f6;
  border-radius: 0.375rem;
  text-align: left;
  max-height: 300px;
  overflow-y: auto;
}

.stack-trace pre {
  font-size: 0.75rem;
  color: #374151;
  white-space: pre-wrap;
  word-break: break-word;
  margin: 0;
  font-family: 'Courier New', monospace;
}

.error-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: center;
  flex-wrap: wrap;
}

.btn-primary,
.btn-secondary {
  padding: 0.625rem 1.25rem;
  border-radius: 0.375rem;
  font-weight: 500;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-primary {
  background-color: #3b82f6;
  color: white;
}

.btn-primary:hover {
  background-color: #2563eb;
}

.btn-secondary {
  background-color: white;
  color: #374151;
  border: 1px solid #d1d5db;
}

.btn-secondary:hover {
  background-color: #f9fafb;
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
  .error-boundary {
    background-color: #111827;
  }

  .error-boundary-content {
    background: #1f2937;
  }

  .error-title {
    color: #f9fafb;
  }

  .error-message {
    color: #d1d5db;
  }

  .stack-trace {
    background: #111827;
  }

  .stack-trace pre {
    color: #d1d5db;
  }

  .btn-secondary {
    background-color: #374151;
    color: #f9fafb;
    border-color: #4b5563;
  }

  .btn-secondary:hover {
    background-color: #4b5563;
  }
}
</style>
