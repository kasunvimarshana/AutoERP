/**
 * Application Configuration Constants
 */

// LocalStorage Keys
export const STORAGE_KEYS = {
  AUTH_TOKEN: 'auth_token',
  AUTH_USER: 'auth_user',
  LOCALE: 'locale',
};

// API Configuration
export const API_CONFIG = {
  BASE_URL: '/api/v1',
  TIMEOUT: 30000, // 30 seconds
};

// Auth Events
export const AUTH_EVENTS = {
  LOGOUT: 'auth:logout',
};

export default {
  STORAGE_KEYS,
  API_CONFIG,
  AUTH_EVENTS,
};
