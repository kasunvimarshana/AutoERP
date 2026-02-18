import { ref, computed, watch, onMounted } from 'vue';
import { useAuthStore } from '../stores/authStore';

const currentTheme = ref({
  name: 'default',
  colors: {
    primary: '#3b82f6',
    secondary: '#6b7280',
    success: '#10b981',
    warning: '#f59e0b',
    danger: '#ef4444',
    info: '#06b6d4',
    light: '#f9fafb',
    dark: '#111827'
  },
  fonts: {
    sans: 'system-ui, -apple-system, sans-serif',
    mono: 'monospace'
  },
  spacing: {
    unit: '0.25rem'
  },
  borderRadius: {
    sm: '0.25rem',
    md: '0.375rem',
    lg: '0.5rem',
    xl: '0.75rem'
  }
});

const darkMode = ref(false);

export const useTheme = () => {
  const authStore = useAuthStore();

  /**
   * Load tenant-specific theme
   */
  const loadTenantTheme = async () => {
    try {
      const tenantId = authStore.user?.tenant_id;
      if (!tenantId) return;

      // In a real app, fetch from API
      // const response = await fetch(`/api/v1/tenants/${tenantId}/theme`);
      // const themeData = await response.json();
      
      // For now, use default theme
      // applyTheme(themeData.data || currentTheme.value);
    } catch (error) {
      console.error('Failed to load tenant theme:', error);
    }
  };

  /**
   * Apply theme to DOM
   */
  const applyTheme = (theme) => {
    currentTheme.value = theme;
    
    if (typeof document === 'undefined') return;
    
    const root = document.documentElement;
    
    // Apply color variables
    Object.entries(theme.colors).forEach(([key, value]) => {
      root.style.setProperty(`--color-${key}`, value);
    });
    
    // Apply font variables
    if (theme.fonts) {
      root.style.setProperty('--font-sans', theme.fonts.sans);
      root.style.setProperty('--font-mono', theme.fonts.mono);
    }
    
    // Apply spacing
    if (theme.spacing) {
      root.style.setProperty('--spacing-unit', theme.spacing.unit);
    }
    
    // Apply border radius
    if (theme.borderRadius) {
      Object.entries(theme.borderRadius).forEach(([key, value]) => {
        root.style.setProperty(`--radius-${key}`, value);
      });
    }
    
    // Apply dark mode class
    if (darkMode.value) {
      root.classList.add('dark');
    } else {
      root.classList.remove('dark');
    }
  };

  /**
   * Toggle dark mode
   */
  const toggleDarkMode = () => {
    darkMode.value = !darkMode.value;
    localStorage.setItem('darkMode', darkMode.value.toString());
    applyTheme(currentTheme.value);
  };

  /**
   * Set specific theme
   */
  const setTheme = (theme) => {
    applyTheme(theme);
  };

  /**
   * Get color by name
   */
  const getColor = (colorName) => {
    return currentTheme.value.colors[colorName] || colorName;
  };

  /**
   * Generate lighter/darker shade
   */
  const shade = (colorName, amount = 0.1) => {
    const color = getColor(colorName);
    // Simple shade implementation
    return color;
  };

  /**
   * Check if dark mode
   */
  const isDark = computed(() => darkMode.value);

  /**
   * Get current theme colors
   */
  const colors = computed(() => currentTheme.value.colors);

  /**
   * Initialize theme
   */
  const initialize = async () => {
    // Load dark mode preference
    const savedDarkMode = localStorage.getItem('darkMode');
    if (savedDarkMode !== null) {
      darkMode.value = savedDarkMode === 'true';
    } else {
      // Check system preference
      if (typeof window !== 'undefined' && window.matchMedia) {
        darkMode.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
      }
    }
    
    // Load tenant theme
    await loadTenantTheme();
    
    // Apply theme
    applyTheme(currentTheme.value);
  };

  // Watch for dark mode changes
  watch(darkMode, () => {
    applyTheme(currentTheme.value);
  });

  return {
    // State
    currentTheme: computed(() => currentTheme.value),
    isDark,
    colors,
    
    // Methods
    setTheme,
    toggleDarkMode,
    getColor,
    shade,
    loadTenantTheme,
    initialize
  };
};
