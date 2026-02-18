/**
 * Global Error Handler Composable
 * Provides centralized error handling for the application
 */

import { ref, onErrorCaptured } from 'vue';
import { useRouter } from 'vue-router';
import { AxiosError } from 'axios';

export interface ErrorDetails {
  message: string;
  code?: string | number;
  stack?: string;
  context?: Record<string, any>;
  timestamp: Date;
  severity: 'error' | 'warning' | 'info';
}

const globalErrors = ref<ErrorDetails[]>([]);
const errorListeners: Array<(error: ErrorDetails) => void> = [];

export function useErrorHandler() {
  const router = useRouter();
  const localErrors = ref<ErrorDetails[]>([]);

  /**
   * Format error message from various error types
   */
  const formatError = (error: unknown): ErrorDetails => {
    const timestamp = new Date();

    // Axios HTTP Error
    if (error instanceof AxiosError) {
      const status = error.response?.status;
      const data = error.response?.data;

      let message = error.message;
      if (data?.message) {
        message = data.message;
      } else if (data?.errors) {
        const errorValues = Object.values(data.errors);
        const flattenedErrors = Array.isArray(errorValues) ? errorValues.flat() : [errorValues];
        message = flattenedErrors.join(', ');
      }

      return {
        message,
        code: status,
        context: {
          url: error.config?.url,
          method: error.config?.method,
          data: error.response?.data,
        },
        timestamp,
        severity: status >= 500 ? 'error' : 'warning',
      };
    }

    // Standard Error
    if (error instanceof Error) {
      return {
        message: error.message,
        stack: error.stack,
        timestamp,
        severity: 'error',
      };
    }

    // String error
    if (typeof error === 'string') {
      return {
        message: error,
        timestamp,
        severity: 'error',
      };
    }

    // Unknown error type
    return {
      message: 'An unexpected error occurred',
      context: { error },
      timestamp,
      severity: 'error',
    };
  };

  /**
   * Handle error and add to error log
   */
  const handleError = (error: unknown, context?: Record<string, any>) => {
    const errorDetails = formatError(error);
    
    if (context) {
      errorDetails.context = {
        ...errorDetails.context,
        ...context,
      };
    }

    // Add to local and global error lists
    localErrors.value.push(errorDetails);
    globalErrors.value.push(errorDetails);

    // Notify listeners
    errorListeners.forEach(listener => listener(errorDetails));

    // Log to console in development
    if (import.meta.env.DEV) {
      console.error('[Error Handler]', errorDetails);
    }

    // Handle specific error codes
    const code = errorDetails.code;
    if (code === 401) {
      router.push('/login');
    } else if (code === 403) {
      router.push('/unauthorized');
    } else if (code === 404) {
      // Don't redirect on 404, let component handle it
    } else if (code && code >= 500) {
      // Server error - could show error page
      console.error('Server error:', errorDetails);
    }

    return errorDetails;
  };

  /**
   * Handle async operation with error catching
   */
  const handleAsync = async <T>(
    operation: () => Promise<T>,
    context?: Record<string, any>
  ): Promise<{ data: T | null; error: ErrorDetails | null }> => {
    try {
      const data = await operation();
      return { data, error: null };
    } catch (error) {
      const errorDetails = handleError(error, context);
      return { data: null, error: errorDetails };
    }
  };

  /**
   * Clear errors
   */
  const clearErrors = () => {
    localErrors.value = [];
  };

  /**
   * Clear all global errors
   */
  const clearAllErrors = () => {
    globalErrors.value = [];
    localErrors.value = [];
  };

  /**
   * Subscribe to error events
   */
  const onError = (callback: (error: ErrorDetails) => void) => {
    errorListeners.push(callback);
    return () => {
      const index = errorListeners.indexOf(callback);
      if (index > -1) {
        errorListeners.splice(index, 1);
      }
    };
  };

  /**
   * Capture component errors (use in component setup)
   */
  const captureComponentErrors = () => {
    onErrorCaptured((error, instance, info) => {
      handleError(error, {
        component: instance?.$options.name || 'Unknown',
        info,
      });
      
      // Return false to prevent error from propagating
      return false;
    });
  };

  return {
    errors: localErrors,
    globalErrors,
    handleError,
    handleAsync,
    clearErrors,
    clearAllErrors,
    onError,
    captureComponentErrors,
    formatError,
  };
}

/**
 * Setup global error handler for the app
 */
export function setupGlobalErrorHandler(app: any) {
  // Vue error handler
  app.config.errorHandler = (error: unknown, instance: any, info: string) => {
    const { handleError } = useErrorHandler();
    handleError(error, {
      component: instance?.$options?.name || 'Unknown',
      info,
    });
  };

  // Vue warning handler
  app.config.warnHandler = (msg: string, instance: any, trace: string) => {
    if (import.meta.env.DEV) {
      console.warn('[Vue Warning]', msg, instance, trace);
    }
  };

  // Global unhandled promise rejection
  window.addEventListener('unhandledrejection', (event) => {
    const { handleError } = useErrorHandler();
    handleError(event.reason, {
      type: 'unhandledrejection',
      promise: 'Promise rejection',
    });
  });

  // Global error event
  window.addEventListener('error', (event) => {
    const { handleError } = useErrorHandler();
    handleError(event.error || event.message, {
      type: 'window.error',
      filename: event.filename,
      lineno: event.lineno,
      colno: event.colno,
    });
  });
}
