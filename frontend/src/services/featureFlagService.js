/**
 * Feature Flag Service
 * 
 * Manages feature flags for runtime feature toggling,
 * A/B testing, and gradual feature rollouts.
 */

import metadataService from './metadataService';

class FeatureFlagService {
  constructor() {
    this.flags = {};
    this.loaded = false;
    this.listeners = [];
  }

  /**
   * Load feature flags from backend
   */
  async loadFlags() {
    try {
      const response = await metadataService.getFeatures();
      this.flags = this.transformFlags(response.data || []);
      this.loaded = true;
      this.notifyListeners();
      return this.flags;
    } catch (error) {
      console.error('Failed to load feature flags:', error);
      this.loaded = true; // Mark as loaded even on error
      return {};
    }
  }

  /**
   * Transform flags array to object
   */
  transformFlags(flagsArray) {
    const transformed = {};
    flagsArray.forEach(flag => {
      transformed[flag.name] = {
        enabled: flag.is_enabled || false,
        value: flag.value,
        metadata: flag.metadata || {},
      };
    });
    return transformed;
  }

  /**
   * Check if feature is enabled
   */
  isEnabled(featureName) {
    if (!this.loaded) {
      console.warn('Feature flags not loaded yet');
      return false;
    }

    const flag = this.flags[featureName];
    return flag ? flag.enabled : false;
  }

  /**
   * Get feature flag value
   */
  getValue(featureName, defaultValue = null) {
    if (!this.loaded) {
      console.warn('Feature flags not loaded yet');
      return defaultValue;
    }

    const flag = this.flags[featureName];
    return flag && flag.enabled ? (flag.value !== undefined ? flag.value : true) : defaultValue;
  }

  /**
   * Check multiple features (all must be enabled)
   */
  isAllEnabled(featureNames) {
    return featureNames.every(name => this.isEnabled(name));
  }

  /**
   * Check multiple features (at least one must be enabled)
   */
  isAnyEnabled(featureNames) {
    return featureNames.some(name => this.isEnabled(name));
  }

  /**
   * Get all enabled features
   */
  getEnabledFeatures() {
    return Object.keys(this.flags).filter(name => this.flags[name].enabled);
  }

  /**
   * Get features by module
   */
  getModuleFeatures(module) {
    return Object.entries(this.flags)
      .filter(([name, flag]) => flag.metadata?.module === module)
      .reduce((acc, [name, flag]) => {
        acc[name] = flag;
        return acc;
      }, {});
  }

  /**
   * Check if module is enabled
   */
  isModuleEnabled(module) {
    const moduleFeatures = this.getModuleFeatures(module);
    
    // If no features defined for module, assume enabled
    if (Object.keys(moduleFeatures).length === 0) {
      return true;
    }

    // Check if module's main feature is enabled
    const mainFeature = `${module}.enabled`;
    if (this.flags[mainFeature]) {
      return this.flags[mainFeature].enabled;
    }

    // If any module feature is enabled, consider module enabled
    return Object.values(moduleFeatures).some(flag => flag.enabled);
  }

  /**
   * Subscribe to flag changes
   */
  subscribe(callback) {
    this.listeners.push(callback);
    return () => {
      this.listeners = this.listeners.filter(cb => cb !== callback);
    };
  }

  /**
   * Notify all listeners
   */
  notifyListeners() {
    this.listeners.forEach(callback => {
      try {
        callback(this.flags);
      } catch (error) {
        console.error('Error in feature flag listener:', error);
      }
    });
  }

  /**
   * Update flag value (local only, doesn't persist to backend)
   */
  updateFlag(featureName, enabled, value = null) {
    if (!this.flags[featureName]) {
      this.flags[featureName] = { enabled, value, metadata: {} };
    } else {
      this.flags[featureName].enabled = enabled;
      if (value !== null) {
        this.flags[featureName].value = value;
      }
    }
    this.notifyListeners();
  }

  /**
   * Enable feature flag on backend
   */
  async enableFeature(featureName) {
    try {
      await metadataService.enableFeature(featureName);
      this.updateFlag(featureName, true);
      return true;
    } catch (error) {
      console.error('Failed to enable feature:', error);
      return false;
    }
  }

  /**
   * Disable feature flag on backend
   */
  async disableFeature(featureName) {
    try {
      await metadataService.disableFeature(featureName);
      this.updateFlag(featureName, false);
      return true;
    } catch (error) {
      console.error('Failed to disable feature:', error);
      return false;
    }
  }

  /**
   * Reload flags from backend
   */
  async reload() {
    this.loaded = false;
    return await this.loadFlags();
  }

  /**
   * Check if flags are loaded
   */
  isLoaded() {
    return this.loaded;
  }

  /**
   * Get all flags
   */
  getAllFlags() {
    return { ...this.flags };
  }

  /**
   * Clear all flags
   */
  clear() {
    this.flags = {};
    this.loaded = false;
    this.notifyListeners();
  }
}

// Create singleton instance
const featureFlagService = new FeatureFlagService();

export default featureFlagService;
