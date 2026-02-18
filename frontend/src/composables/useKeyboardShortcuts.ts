import { onMounted, onUnmounted } from 'vue';

interface KeyboardShortcut {
  key: string;
  ctrl?: boolean;
  alt?: boolean;
  shift?: boolean;
  meta?: boolean;
  callback: (event: KeyboardEvent) => void;
  description?: string;
}

export function useKeyboardShortcuts(shortcuts: KeyboardShortcut[]) {
  const handleKeyDown = (event: KeyboardEvent) => {
    for (const shortcut of shortcuts) {
      // Check each modifier separately to avoid conflicts
      const ctrlMatch = shortcut.ctrl === undefined || shortcut.ctrl === event.ctrlKey;
      const altMatch = shortcut.alt === undefined || shortcut.alt === event.altKey;
      const shiftMatch = shortcut.shift === undefined || shortcut.shift === event.shiftKey;
      const metaMatch = shortcut.meta === undefined || shortcut.meta === event.metaKey;
      const keyMatch = event.key.toLowerCase() === shortcut.key.toLowerCase();

      // Allow Ctrl shortcut to also work with Meta on Mac if neither ctrl nor meta is explicitly set
      const isCtrlOrMeta = (shortcut.ctrl && !shortcut.meta) && (event.ctrlKey || event.metaKey);
      const effectiveCtrlMatch = (shortcut.ctrl === undefined && shortcut.meta === undefined) ? true : 
                                  isCtrlOrMeta ? true : ctrlMatch;

      if (effectiveCtrlMatch && altMatch && shiftMatch && metaMatch && keyMatch) {
        event.preventDefault();
        shortcut.callback(event);
        break;
      }
    }
  };

  onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
  });

  onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
  });

  return {
    shortcuts,
  };
}
