/**
 * Accessibility (A11y) Composable
 * Provides utilities for accessible UI components
 */

import { ref, computed, onMounted, onUnmounted, Ref } from 'vue';

export interface A11yAnnouncement {
  message: string;
  priority?: 'polite' | 'assertive';
  timeout?: number;
}

const announcements = ref<A11yAnnouncement[]>([]);

export function useA11y() {
  /**
   * Generate unique ID for aria attributes
   */
  const generateId = (prefix = 'a11y') => {
    return `${prefix}-${Math.random().toString(36).substr(2, 9)}`;
  };

  /**
   * Announce message to screen readers
   */
  const announce = (message: string, priority: 'polite' | 'assertive' = 'polite', timeout = 3000) => {
    announcements.value.push({ message, priority, timeout });

    if (timeout > 0) {
      setTimeout(() => {
        announcements.value = announcements.value.filter(a => a.message !== message);
      }, timeout);
    }
  };

  /**
   * Clear all announcements
   */
  const clearAnnouncements = () => {
    announcements.value = [];
  };

  /**
   * Setup keyboard trap for modals/dialogs
   */
  const trapFocus = (element: HTMLElement | null) => {
    if (!element) return () => {};

    const focusableElements = element.querySelectorAll(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );

    const firstFocusable = focusableElements[0] as HTMLElement;
    const lastFocusable = focusableElements[focusableElements.length - 1] as HTMLElement;

    const handleKeyDown = (event: KeyboardEvent) => {
      if (event.key !== 'Tab') return;

      if (event.shiftKey) {
        // Shift + Tab
        if (document.activeElement === firstFocusable) {
          event.preventDefault();
          lastFocusable?.focus();
        }
      } else {
        // Tab
        if (document.activeElement === lastFocusable) {
          event.preventDefault();
          firstFocusable?.focus();
        }
      }
    };

    element.addEventListener('keydown', handleKeyDown);

    // Focus first element
    firstFocusable?.focus();

    // Return cleanup function
    return () => {
      element.removeEventListener('keydown', handleKeyDown);
    };
  };

  /**
   * Handle keyboard navigation for lists
   */
  const useListKeyboardNav = (
    items: Ref<any[]>,
    onSelect: (item: any, index: number) => void
  ) => {
    const activeIndex = ref(0);

    const handleKeyDown = (event: KeyboardEvent) => {
      switch (event.key) {
        case 'ArrowDown':
          event.preventDefault();
          activeIndex.value = Math.min(activeIndex.value + 1, items.value.length - 1);
          break;
        case 'ArrowUp':
          event.preventDefault();
          activeIndex.value = Math.max(activeIndex.value - 1, 0);
          break;
        case 'Home':
          event.preventDefault();
          activeIndex.value = 0;
          break;
        case 'End':
          event.preventDefault();
          activeIndex.value = items.value.length - 1;
          break;
        case 'Enter':
        case ' ':
          event.preventDefault();
          if (items.value[activeIndex.value]) {
            onSelect(items.value[activeIndex.value], activeIndex.value);
          }
          break;
      }
    };

    return {
      activeIndex,
      handleKeyDown,
    };
  };

  /**
   * Manage focus restoration
   */
  const useFocusRestore = () => {
    let previousActiveElement: HTMLElement | null = null;

    const saveFocus = () => {
      previousActiveElement = document.activeElement as HTMLElement;
    };

    const restoreFocus = () => {
      if (previousActiveElement && previousActiveElement.focus) {
        previousActiveElement.focus();
      }
    };

    return {
      saveFocus,
      restoreFocus,
    };
  };

  /**
   * Check if element is visible to user
   */
  const isVisibleToUser = (element: HTMLElement): boolean => {
    if (!element) return false;

    const style = window.getComputedStyle(element);
    return (
      style.display !== 'none' &&
      style.visibility !== 'hidden' &&
      style.opacity !== '0' &&
      element.offsetWidth > 0 &&
      element.offsetHeight > 0
    );
  };

  /**
   * Get accessible label for element
   */
  const getAccessibleLabel = (element: HTMLElement): string => {
    // Check aria-label
    const ariaLabel = element.getAttribute('aria-label');
    if (ariaLabel) return ariaLabel;

    // Check aria-labelledby
    const ariaLabelledBy = element.getAttribute('aria-labelledby');
    if (ariaLabelledBy) {
      const labelElement = document.getElementById(ariaLabelledBy);
      if (labelElement) return labelElement.textContent || '';
    }

    // Check title
    const title = element.getAttribute('title');
    if (title) return title;

    // Check placeholder for inputs
    if (element instanceof HTMLInputElement || element instanceof HTMLTextAreaElement) {
      const placeholder = element.getAttribute('placeholder');
      if (placeholder) return placeholder;
    }

    // Check text content
    return element.textContent || '';
  };

  /**
   * Setup escape key handler
   */
  const useEscapeKey = (callback: () => void) => {
    const handleEscape = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        callback();
      }
    };

    onMounted(() => {
      document.addEventListener('keydown', handleEscape);
    });

    onUnmounted(() => {
      document.removeEventListener('keydown', handleEscape);
    });

    return handleEscape;
  };

  /**
   * Check if reduced motion is preferred
   */
  const prefersReducedMotion = computed(() => {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  });

  /**
   * Check if high contrast is preferred
   */
  const prefersHighContrast = computed(() => {
    return window.matchMedia('(prefers-contrast: high)').matches;
  });

  /**
   * Get ARIA attributes for form field
   */
  const getFieldAriaAttrs = (
    fieldId: string,
    errorId?: string,
    descriptionId?: string,
    required = false
  ) => {
    const attrs: Record<string, any> = {
      'id': fieldId,
      'aria-required': required,
    };

    if (errorId) {
      attrs['aria-invalid'] = 'true';
      attrs['aria-describedby'] = errorId;
    } else if (descriptionId) {
      attrs['aria-describedby'] = descriptionId;
    }

    return attrs;
  };

  /**
   * Create live region element for announcements
   */
  const createLiveRegion = () => {
    const liveRegion = document.createElement('div');
    liveRegion.setAttribute('role', 'status');
    liveRegion.setAttribute('aria-live', 'polite');
    liveRegion.setAttribute('aria-atomic', 'true');
    liveRegion.className = 'sr-only'; // Screen reader only
    liveRegion.style.cssText = `
      position: absolute;
      left: -10000px;
      width: 1px;
      height: 1px;
      overflow: hidden;
    `;
    document.body.appendChild(liveRegion);
    return liveRegion;
  };

  return {
    announcements,
    generateId,
    announce,
    clearAnnouncements,
    trapFocus,
    useListKeyboardNav,
    useFocusRestore,
    isVisibleToUser,
    getAccessibleLabel,
    useEscapeKey,
    prefersReducedMotion,
    prefersHighContrast,
    getFieldAriaAttrs,
    createLiveRegion,
  };
}

/**
 * Setup global accessibility features
 */
export function setupAccessibility() {
  // Create global live region for announcements
  const liveRegion = document.createElement('div');
  liveRegion.id = 'a11y-live-region';
  liveRegion.setAttribute('role', 'status');
  liveRegion.setAttribute('aria-live', 'polite');
  liveRegion.setAttribute('aria-atomic', 'true');
  liveRegion.className = 'sr-only';
  liveRegion.style.cssText = `
    position: absolute;
    left: -10000px;
    width: 1px;
    height: 1px;
    overflow: hidden;
  `;
  document.body.appendChild(liveRegion);

  // Listen to announcement events
  const { announce } = useA11y();
  window.addEventListener('a11y-announce', ((event: CustomEvent) => {
    const { message, priority } = event.detail;
    announce(message, priority);
  }) as EventListener);

  // Add skip to main content link
  const skipLink = document.createElement('a');
  skipLink.href = '#main-content';
  skipLink.textContent = 'Skip to main content';
  skipLink.className = 'skip-link';
  skipLink.style.cssText = `
    position: absolute;
    top: -40px;
    left: 0;
    background: #000;
    color: #fff;
    padding: 8px;
    text-decoration: none;
    z-index: 9999;
  `;
  skipLink.addEventListener('focus', () => {
    skipLink.style.top = '0';
  });
  skipLink.addEventListener('blur', () => {
    skipLink.style.top = '-40px';
  });
  document.body.insertBefore(skipLink, document.body.firstChild);

  console.log('[A11y] Accessibility features initialized');
}
