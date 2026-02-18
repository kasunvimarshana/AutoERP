import { ref } from 'vue';

interface ConfirmDialogOptions {
  title: string;
  message: string;
  type?: 'danger' | 'warning' | 'info';
  confirmText?: string;
  cancelText?: string;
}

const isOpen = ref(false);
const dialogOptions = ref<ConfirmDialogOptions>({
  title: '',
  message: '',
  type: 'danger',
  confirmText: 'Confirm',
  cancelText: 'Cancel',
});

let resolvePromise: ((value: boolean) => void) | null = null;

export function useConfirmDialog() {
  const confirm = (options: ConfirmDialogOptions): Promise<boolean> => {
    return new Promise((resolve) => {
      dialogOptions.value = {
        ...options,
        type: options.type || 'danger',
        confirmText: options.confirmText || 'Confirm',
        cancelText: options.cancelText || 'Cancel',
      };
      isOpen.value = true;
      resolvePromise = resolve;
    });
  };

  const handleConfirm = () => {
    if (resolvePromise) {
      resolvePromise(true);
      resolvePromise = null;
    }
    isOpen.value = false;
  };

  const handleCancel = () => {
    if (resolvePromise) {
      resolvePromise(false);
      resolvePromise = null;
    }
    isOpen.value = false;
  };

  return {
    isOpen,
    dialogOptions,
    confirm,
    handleConfirm,
    handleCancel,
  };
}
