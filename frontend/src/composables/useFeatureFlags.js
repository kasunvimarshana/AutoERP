/**
 * useFeatureFlags Composable
 * 
 * Provides reactive access to feature flags in Vue components.
 */

import { ref, computed, onMounted, onUnmounted } from 'vue';
import featureFlagService from '../services/featureFlagService';

export function useFeatureFlags() {
  const flags = ref({});
  const loaded = ref(false);
  const loading = ref(false);
  const error = ref(null);

  let unsubscribe = null;

  /**
   * Initialize feature flags
   */
  async function initialize() {
    if (featureFlagService.isLoaded()) {
      flags.value = featureFlagService.getAllFlags();
      loaded.value = true;
      return;
    }

    loading.value = true;
    error.value = null;

    try {
      flags.value = await featureFlagService.loadFlags();
      loaded.value = true;
    } catch (err) {
      console.error('Failed to initialize feature flags:', err);
      error.value = 'Failed to load feature flags';
    } finally {
      loading.value = false;
    }
  }

  /**
   * Check if a feature is enabled
   */
  const isEnabled = computed(() => (featureName) => {
    return featureFlagService.isEnabled(featureName);
  });

  /**
   * Get feature flag value
   */
  const getValue = computed(() => (featureName, defaultValue = null) => {
    return featureFlagService.getValue(featureName, defaultValue);
  });

  /**
   * Check if all features are enabled
   */
  const isAllEnabled = computed(() => (featureNames) => {
    return featureFlagService.isAllEnabled(featureNames);
  });

  /**
   * Check if any feature is enabled
   */
  const isAnyEnabled = computed(() => (featureNames) => {
    return featureFlagService.isAnyEnabled(featureNames);
  });

  /**
   * Check if module is enabled
   */
  const isModuleEnabled = computed(() => (module) => {
    return featureFlagService.isModuleEnabled(module);
  });

  /**
   * Get all enabled features
   */
  const enabledFeatures = computed(() => {
    return featureFlagService.getEnabledFeatures();
  });

  /**
   * Get module features
   */
  const getModuleFeatures = computed(() => (module) => {
    return featureFlagService.getModuleFeatures(module);
  });

  /**
   * Enable a feature
   */
  async function enableFeature(featureName) {
    return await featureFlagService.enableFeature(featureName);
  }

  /**
   * Disable a feature
   */
  async function disableFeature(featureName) {
    return await featureFlagService.disableFeature(featureName);
  }

  /**
   * Reload feature flags
   */
  async function reload() {
    loading.value = true;
    error.value = null;

    try {
      flags.value = await featureFlagService.reload();
      loaded.value = true;
    } catch (err) {
      console.error('Failed to reload feature flags:', err);
      error.value = 'Failed to reload feature flags';
    } finally {
      loading.value = false;
    }
  }

  // Subscribe to flag changes
  onMounted(() => {
    unsubscribe = featureFlagService.subscribe((updatedFlags) => {
      flags.value = updatedFlags;
    });

    // Initialize if not already loaded
    if (!loaded.value && !loading.value) {
      initialize();
    } else if (featureFlagService.isLoaded()) {
      flags.value = featureFlagService.getAllFlags();
      loaded.value = true;
    }
  });

  // Cleanup subscription
  onUnmounted(() => {
    if (unsubscribe) {
      unsubscribe();
    }
  });

  return {
    flags,
    loaded,
    loading,
    error,
    isEnabled,
    getValue,
    isAllEnabled,
    isAnyEnabled,
    isModuleEnabled,
    enabledFeatures,
    getModuleFeatures,
    enableFeature,
    disableFeature,
    reload,
    initialize,
  };
}

/**
 * Directive for conditional rendering based on feature flags
 * 
 * Usage:
 * <div v-feature="'advanced-reporting'">...</div>
 * <div v-feature="['feature1', 'feature2']">...</div> (any)
 * <div v-feature.all="['feature1', 'feature2']">...</div> (all)
 */
export const vFeature = {
  mounted(el, binding) {
    const { value, modifiers } = binding;
    const isArray = Array.isArray(value);

    let isEnabled = false;

    if (isArray) {
      if (modifiers.all) {
        isEnabled = featureFlagService.isAllEnabled(value);
      } else {
        isEnabled = featureFlagService.isAnyEnabled(value);
      }
    } else {
      isEnabled = featureFlagService.isEnabled(value);
    }

    if (!isEnabled) {
      el.style.display = 'none';
      el.setAttribute('aria-hidden', 'true');
    }
  },

  updated(el, binding) {
    const { value, modifiers } = binding;
    const isArray = Array.isArray(value);

    let isEnabled = false;

    if (isArray) {
      if (modifiers.all) {
        isEnabled = featureFlagService.isAllEnabled(value);
      } else {
        isEnabled = featureFlagService.isAnyEnabled(value);
      }
    } else {
      isEnabled = featureFlagService.isEnabled(value);
    }

    if (isEnabled) {
      el.style.display = '';
      el.removeAttribute('aria-hidden');
    } else {
      el.style.display = 'none';
      el.setAttribute('aria-hidden', 'true');
    }
  },
};
