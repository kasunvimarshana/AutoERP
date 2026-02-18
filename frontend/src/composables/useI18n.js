import { ref, computed } from 'vue';
import { useAuthStore } from '../stores/authStore';

// Simple i18n implementation
const translations = ref({});
const currentLocale = ref('en');
const fallbackLocale = ref('en');
const loading = ref(false);

export const useI18n = () => {
  const authStore = useAuthStore();

  /**
   * Load translations for a specific locale
   */
  const loadTranslations = async (locale) => {
    loading.value = true;
    try {
      // In a real app, this would fetch from an API
      // For now, we'll use a mock implementation
      const response = await fetch(`/locales/${locale}.json`).catch(() => null);
      
      if (response && response.ok) {
        const data = await response.json();
        translations.value[locale] = data;
      } else {
        // Fallback to embedded translations
        translations.value[locale] = getDefaultTranslations(locale);
      }
      
      return translations.value[locale];
    } finally {
      loading.value = false;
    }
  };

  /**
   * Set the current locale
   */
  const setLocale = async (locale) => {
    if (!translations.value[locale]) {
      await loadTranslations(locale);
    }
    currentLocale.value = locale;
    
    // Update HTML lang attribute
    if (typeof document !== 'undefined') {
      document.documentElement.lang = locale;
    }
  };

  /**
   * Get translation for a key
   */
  const t = (key, params = {}) => {
    const keys = key.split('.');
    let value = translations.value[currentLocale.value];
    
    // Try current locale
    for (const k of keys) {
      value = value?.[k];
    }
    
    // Fallback to fallback locale
    if (value === undefined && currentLocale.value !== fallbackLocale.value) {
      value = translations.value[fallbackLocale.value];
      for (const k of keys) {
        value = value?.[k];
      }
    }
    
    // If still not found, return the key
    if (value === undefined) {
      return key;
    }
    
    // Replace parameters
    if (typeof value === 'string' && Object.keys(params).length > 0) {
      return value.replace(/\{(\w+)\}/g, (match, param) => {
        return params[param] !== undefined ? params[param] : match;
      });
    }
    
    return value;
  };

  /**
   * Check if a translation key exists
   */
  const has = (key) => {
    return t(key) !== key;
  };

  /**
   * Format number according to locale
   */
  const n = (number, options = {}) => {
    return new Intl.NumberFormat(currentLocale.value, options).format(number);
  };

  /**
   * Format currency according to locale
   */
  const c = (amount, currency = 'USD') => {
    return new Intl.NumberFormat(currentLocale.value, {
      style: 'currency',
      currency: currency
    }).format(amount);
  };

  /**
   * Format date according to locale
   */
  const d = (date, options = {}) => {
    const defaultOptions = {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    };
    return new Intl.DateTimeFormat(
      currentLocale.value,
      { ...defaultOptions, ...options }
    ).format(new Date(date));
  };

  /**
   * Format relative time
   */
  const rt = (date) => {
    const rtf = new Intl.RelativeTimeFormat(currentLocale.value, { numeric: 'auto' });
    const now = new Date();
    const target = new Date(date);
    const diff = target - now;
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    
    if (Math.abs(days) < 1) {
      const hours = Math.floor(diff / (1000 * 60 * 60));
      if (Math.abs(hours) < 1) {
        const minutes = Math.floor(diff / (1000 * 60));
        return rtf.format(minutes, 'minute');
      }
      return rtf.format(hours, 'hour');
    }
    if (Math.abs(days) < 30) {
      return rtf.format(days, 'day');
    }
    if (Math.abs(days) < 365) {
      const months = Math.floor(days / 30);
      return rtf.format(months, 'month');
    }
    const years = Math.floor(days / 365);
    return rtf.format(years, 'year');
  };

  /**
   * Get available locales
   */
  const availableLocales = computed(() => {
    return Object.keys(translations.value);
  });

  /**
   * Get direction (LTR or RTL)
   */
  const direction = computed(() => {
    const rtlLocales = ['ar', 'he', 'fa', 'ur'];
    return rtlLocales.includes(currentLocale.value) ? 'rtl' : 'ltr';
  });

  /**
   * Initialize with user's preferred locale
   */
  const initialize = async () => {
    const userLocale = authStore.user?.locale || 
                      navigator.language.split('-')[0] || 
                      'en';
    await setLocale(userLocale);
  };

  return {
    // State
    currentLocale: computed(() => currentLocale.value),
    availableLocales,
    loading: computed(() => loading.value),
    direction,
    
    // Methods
    t,
    has,
    n,
    c,
    d,
    rt,
    setLocale,
    loadTranslations,
    initialize
  };
};

/**
 * Get default translations (embedded fallback)
 */
function getDefaultTranslations(locale) {
  const defaultTranslations = {
    en: {
      common: {
        save: 'Save',
        cancel: 'Cancel',
        delete: 'Delete',
        edit: 'Edit',
        create: 'Create',
        search: 'Search',
        filter: 'Filter',
        loading: 'Loading...',
        error: 'An error occurred',
        success: 'Operation successful',
        confirm: 'Are you sure?',
        yes: 'Yes',
        no: 'No'
      },
      validation: {
        required: '{field} is required',
        email: 'Please enter a valid email',
        min: '{field} must be at least {min} characters',
        max: '{field} must not exceed {max} characters'
      },
      modules: {
        inventory: 'Inventory',
        crm: 'CRM',
        pos: 'Point of Sale',
        procurement: 'Procurement',
        manufacturing: 'Manufacturing',
        finance: 'Finance',
        reporting: 'Reporting'
      }
    }
  };
  
  return defaultTranslations[locale] || defaultTranslations.en;
}
