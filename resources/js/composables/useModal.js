import { ref } from 'vue';

export function useModal() {
  const isOpen = ref(false);
  const data = ref(null);

  const open = (initialData = null) => {
    data.value = initialData;
    isOpen.value = true;
  };

  const close = () => {
    isOpen.value = false;
    data.value = null;
  };

  const toggle = () => {
    isOpen.value = !isOpen.value;
    if (!isOpen.value) {
      data.value = null;
    }
  };

  return {
    isOpen,
    data,
    open,
    close,
    toggle,
  };
}
