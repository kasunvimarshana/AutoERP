import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import './assets/styles/main.css';
import { configureEcho, setEcho } from './config/echo';
import { setupGlobalErrorHandler } from './composables/useErrorHandler';
import { setupOfflineDetection } from './composables/useOfflineDetection';
import { setupAccessibility } from './composables/useA11y';
import { setupPerformanceMonitoring } from './composables/usePerformance';

const app = createApp(App);
const pinia = createPinia();

// Setup global error handling
setupGlobalErrorHandler(app);

// Setup offline detection
setupOfflineDetection();

// Setup accessibility features
setupAccessibility();

// Setup performance monitoring (dev mode)
if (import.meta.env.DEV) {
  setupPerformanceMonitoring();
}

app.use(pinia);
app.use(router);

// Initialize module metadata before mounting
import { useModuleStore } from './stores/modules';
import { useAuthStore } from './stores/auth';
import { DynamicRouteGenerator } from './router/dynamicRoutes';

const initializeApp = async () => {
  const moduleStore = useModuleStore();
  
  try {
    // Load module metadata from backend
    await moduleStore.loadModules();
    
    // Register dynamic routes based on module metadata
    if (moduleStore.modules && Object.keys(moduleStore.modules).length > 0) {
      DynamicRouteGenerator.registerDynamicRoutes(router, moduleStore.modules);
      console.log('Dynamic routes registered successfully');
    }
    
    // Module metadata is now available for dynamic routing and navigation
    console.log('Module metadata loaded successfully');
  } catch (error) {
    console.error('Failed to initialize module metadata:', error);
    // Continue with app initialization even if module loading fails
  }
  
  // Initialize Laravel Echo if user is authenticated
  const authStore = useAuthStore();
  const authToken = authStore.token;
  
  if (authToken && typeof authToken === 'string') {
    try {
      const echo = configureEcho(authToken);
      setEcho(echo);
      window.Echo = echo;
      console.log('Laravel Echo initialized successfully');
    } catch (error) {
      console.error('Failed to initialize Laravel Echo:', error);
    }
  }
  
  // Mount the app
  app.mount('#app');
};

// Initialize the application
initializeApp();
