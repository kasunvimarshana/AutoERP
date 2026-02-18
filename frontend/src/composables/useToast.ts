/**
 * Toast Notification Composable
 * Provides easy-to-use toast notification methods
 */

export interface ToastOptions {
  title?: string;
  message: string;
  duration?: number;
  dismissible?: boolean;
  action?: {
    label: string;
    callback: () => void;
  };
}

export function useToast() {
  /**
   * Show a toast notification
   */
  const show = (type: 'success' | 'error' | 'warning' | 'info', options: ToastOptions) => {
    window.dispatchEvent(new CustomEvent('show-toast', {
      detail: {
        type,
        ...options,
      }
    }));
  };

  /**
   * Show success toast
   */
  const success = (message: string, options?: Omit<ToastOptions, 'message'>) => {
    show('success', { message, ...options });
  };

  /**
   * Show error toast
   */
  const error = (message: string, options?: Omit<ToastOptions, 'message'>) => {
    show('error', { message, duration: 0, ...options }); // Errors persist by default
  };

  /**
   * Show warning toast
   */
  const warning = (message: string, options?: Omit<ToastOptions, 'message'>) => {
    show('warning', { message, duration: 7000, ...options }); // Warnings last longer
  };

  /**
   * Show info toast
   */
  const info = (message: string, options?: Omit<ToastOptions, 'message'>) => {
    show('info', { message, ...options });
  };

  /**
   * Show a toast for a saved action
   */
  const saved = (entity?: string) => {
    success(entity ? `${entity} saved successfully` : 'Saved successfully', {
      duration: 3000,
    });
  };

  /**
   * Show a toast for a deleted action
   */
  const deleted = (entity?: string) => {
    success(entity ? `${entity} deleted successfully` : 'Deleted successfully', {
      duration: 3000,
    });
  };

  /**
   * Show a toast for a created action
   */
  const created = (entity?: string) => {
    success(entity ? `${entity} created successfully` : 'Created successfully', {
      duration: 3000,
    });
  };

  /**
   * Show a toast for an updated action
   */
  const updated = (entity?: string) => {
    success(entity ? `${entity} updated successfully` : 'Updated successfully', {
      duration: 3000,
    });
  };

  /**
   * Show a toast for a validation error
   */
  const validationError = (message = 'Please fix the errors and try again') => {
    error(message, {
      title: 'Validation Error',
      duration: 5000,
    });
  };

  /**
   * Show a toast for a network error
   */
  const networkError = (message = 'Unable to connect to server. Please check your internet connection.') => {
    error(message, {
      title: 'Network Error',
      duration: 0,
    });
  };

  /**
   * Show a toast for an unauthorized action
   */
  const unauthorized = (message = 'You do not have permission to perform this action') => {
    error(message, {
      title: 'Unauthorized',
      duration: 5000,
    });
  };

  /**
   * Show a toast for a not found error
   */
  const notFound = (entity?: string) => {
    error(
      entity ? `${entity} not found` : 'Resource not found',
      {
        title: 'Not Found',
        duration: 5000,
      }
    );
  };

  /**
   * Show a toast with an action button
   */
  const withAction = (
    type: 'success' | 'error' | 'warning' | 'info',
    message: string,
    actionLabel: string,
    actionCallback: () => void,
    options?: Omit<ToastOptions, 'message' | 'action'>
  ) => {
    show(type, {
      message,
      action: {
        label: actionLabel,
        callback: actionCallback,
      },
      ...options,
    });
  };

  /**
   * Show an undo toast
   */
  const undo = (message: string, undoCallback: () => void, duration = 5000) => {
    withAction('info', message, 'Undo', undoCallback, { duration });
  };

  /**
   * Show a loading toast
   */
  const loading = (message = 'Loading...') => {
    info(message, {
      duration: 0,
      dismissible: false,
    });
  };

  return {
    show,
    success,
    error,
    warning,
    info,
    saved,
    deleted,
    created,
    updated,
    validationError,
    networkError,
    unauthorized,
    notFound,
    withAction,
    undo,
    loading,
  };
}
