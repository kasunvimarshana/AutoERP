/**
 * Utility for lazy loading components with loading and error states
 */
import { defineAsyncComponent, h } from 'vue';

/**
 * Create a lazy-loaded component with loading and error states
 */
export const lazyLoad = (importFunc, options = {}) => {
  const {
    loadingComponent = null,
    errorComponent = null,
    delay = 200,
    timeout = 30000,
    suspensible = false
  } = options;

  return defineAsyncComponent({
    loader: importFunc,
    loadingComponent,
    errorComponent,
    delay,
    timeout,
    suspensible
  });
};

/**
 * Lazy load a view component
 */
export const lazyLoadView = (viewName) => {
  return lazyLoad(() => import(`../views/${viewName}.vue`));
};

/**
 * Preload a component
 */
export const preloadComponent = async (importFunc) => {
  try {
    await importFunc();
    return true;
  } catch (error) {
    console.error('Failed to preload component:', error);
    return false;
  }
};

/**
 * Default loading component
 */
export const LoadingComponent = {
  name: 'LoadingComponent',
  render() {
    return h('div', { class: 'loading-container' }, [
      h('div', { class: 'loading-spinner' }, 'Loading...')
    ]);
  }
};

/**
 * Debounce function for performance
 */
export const debounce = (fn, delay = 300) => {
  let timeoutId;
  
  return function(...args) {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => fn.apply(this, args), delay);
  };
};

/**
 * Throttle function for performance
 */
export const throttle = (fn, limit = 300) => {
  let inThrottle;
  
  return function(...args) {
    if (!inThrottle) {
      fn.apply(this, args);
      inThrottle = true;
      setTimeout(() => inThrottle = false, limit);
    }
  };
};

/**
 * Memoization for expensive computations
 */
export const memoize = (fn) => {
  const cache = new Map();
  
  return (...args) => {
    const key = JSON.stringify(args);
    
    if (cache.has(key)) {
      return cache.get(key);
    }
    
    const result = fn(...args);
    cache.set(key, result);
    return result;
  };
};
