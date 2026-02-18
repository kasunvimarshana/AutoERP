import { ref, onMounted, onUnmounted } from 'vue';

/**
 * Composable for accessibility features
 */
export const useAccessibility = () => {
  const announcements = ref([]);
  const focusTrapStack = ref([]);

  /**
   * Announce message to screen readers
   */
  const announce = (message, priority = 'polite') => {
    announcements.value.push({
      id: Date.now(),
      message,
      priority
    });

    // Create live region if not exists
    if (typeof document !== 'undefined') {
      let liveRegion = document.getElementById(`aria-live-${priority}`);
      if (!liveRegion) {
        liveRegion = document.createElement('div');
        liveRegion.id = `aria-live-${priority}`;
        liveRegion.setAttribute('aria-live', priority);
        liveRegion.setAttribute('aria-atomic', 'true');
        liveRegion.className = 'sr-only';
        document.body.appendChild(liveRegion);
      }
      liveRegion.textContent = message;

      // Clear after announcement
      setTimeout(() => {
        liveRegion.textContent = '';
      }, 1000);
    }
  };

  /**
   * Focus trap for modals and dialogs
   */
  const setupFocusTrap = (element) => {
    if (!element) return null;

    const focusableSelectors = [
      'a[href]',
      'button:not([disabled])',
      'textarea:not([disabled])',
      'input:not([disabled])',
      'select:not([disabled])',
      '[tabindex]:not([tabindex="-1"])'
    ].join(',');

    const focusableElements = element.querySelectorAll(focusableSelectors);
    const firstFocusable = focusableElements[0];
    const lastFocusable = focusableElements[focusableElements.length - 1];

    const handleKeyDown = (e) => {
      if (e.key !== 'Tab') return;

      if (e.shiftKey) {
        if (document.activeElement === firstFocusable) {
          e.preventDefault();
          lastFocusable?.focus();
        }
      } else {
        if (document.activeElement === lastFocusable) {
          e.preventDefault();
          firstFocusable?.focus();
        }
      }
    };

    element.addEventListener('keydown', handleKeyDown);

    // Focus first element
    firstFocusable?.focus();

    const cleanup = () => {
      element.removeEventListener('keydown', handleKeyDown);
    };

    focusTrapStack.value.push({ element, cleanup });

    return cleanup;
  };

  /**
   * Remove focus trap
   */
  const removeFocusTrap = () => {
    const trap = focusTrapStack.value.pop();
    if (trap) {
      trap.cleanup();
    }
  };

  /**
   * Skip to main content
   */
  const skipToMain = () => {
    const main = document.getElementById('main-content') || document.querySelector('main');
    if (main) {
      main.setAttribute('tabindex', '-1');
      main.focus();
      main.removeAttribute('tabindex');
    }
  };

  /**
   * Check if user prefers reduced motion
   */
  const prefersReducedMotion = () => {
    if (typeof window === 'undefined') return false;
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  };

  /**
   * Generate unique ID for ARIA attributes
   */
  const generateId = (prefix = 'aria') => {
    return `${prefix}-${Math.random().toString(36).substr(2, 9)}`;
  };

  /**
   * Set up keyboard navigation for a list
   */
  const setupKeyboardNav = (container, options = {}) => {
    const {
      itemSelector = '[role="option"]',
      onSelect = () => {},
      orientation = 'vertical'
    } = options;

    if (!container) return null;

    const getItems = () => Array.from(container.querySelectorAll(itemSelector));
    
    const handleKeyDown = (e) => {
      const items = getItems();
      const currentIndex = items.indexOf(document.activeElement);

      let nextIndex = currentIndex;

      switch (e.key) {
        case 'ArrowDown':
        case 'ArrowRight':
          if ((orientation === 'vertical' && e.key === 'ArrowDown') ||
              (orientation === 'horizontal' && e.key === 'ArrowRight')) {
            e.preventDefault();
            nextIndex = (currentIndex + 1) % items.length;
          }
          break;

        case 'ArrowUp':
        case 'ArrowLeft':
          if ((orientation === 'vertical' && e.key === 'ArrowUp') ||
              (orientation === 'horizontal' && e.key === 'ArrowLeft')) {
            e.preventDefault();
            nextIndex = currentIndex > 0 ? currentIndex - 1 : items.length - 1;
          }
          break;

        case 'Home':
          e.preventDefault();
          nextIndex = 0;
          break;

        case 'End':
          e.preventDefault();
          nextIndex = items.length - 1;
          break;

        case 'Enter':
        case ' ':
          e.preventDefault();
          onSelect(items[currentIndex], currentIndex);
          break;

        default:
          return;
      }

      if (items[nextIndex]) {
        items[nextIndex].focus();
      }
    };

    container.addEventListener('keydown', handleKeyDown);

    return () => {
      container.removeEventListener('keydown', handleKeyDown);
    };
  };

  /**
   * Clean up on unmount
   */
  onUnmounted(() => {
    // Clean up all focus traps
    while (focusTrapStack.value.length > 0) {
      removeFocusTrap();
    }
  });

  return {
    announce,
    setupFocusTrap,
    removeFocusTrap,
    skipToMain,
    prefersReducedMotion,
    generateId,
    setupKeyboardNav
  };
};

/**
 * Directive for managing focus
 */
export const vFocus = {
  mounted(el, binding) {
    if (binding.value !== false) {
      el.focus();
    }
  }
};

/**
 * Directive for auto-focus on show
 */
export const vAutoFocus = {
  updated(el, binding) {
    if (binding.value === true && binding.oldValue === false) {
      el.focus();
    }
  }
};
