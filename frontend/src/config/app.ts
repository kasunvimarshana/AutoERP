/**
 * Application Configuration
 * Centralized configuration for the entire application
 */

export const appConfig = {
  name: import.meta.env.VITE_APP_NAME || 'AutoERP',
  version: import.meta.env.VITE_APP_VERSION || '1.0.0',
  apiUrl: import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1',
  apiVersion: 'v1',
  
  // Pagination defaults
  pagination: {
    perPage: 15,
    maxPerPage: 100,
  },
  
  // Auth configuration
  auth: {
    tokenKey: 'auth_token',
    userKey: 'auth_user',
    refreshTokenKey: 'refresh_token',
    tokenExpiration: 60 * 60 * 24, // 24 hours in seconds
  },
  
  // Multi-tenancy configuration
  tenant: {
    headerKey: 'X-Tenant-ID',
    storageKey: 'current_tenant',
  },
  
  // Localization
  i18n: {
    defaultLocale: 'en',
    fallbackLocale: 'en',
    availableLocales: ['en', 'es', 'fr', 'de'],
  },
  
  // Notification settings
  notifications: {
    duration: 5000, // 5 seconds
    position: 'top-right' as const,
  },
  
  // Date/Time formats
  formats: {
    date: 'YYYY-MM-DD',
    dateTime: 'YYYY-MM-DD HH:mm:ss',
    time: 'HH:mm:ss',
    displayDate: 'MMM DD, YYYY',
    displayDateTime: 'MMM DD, YYYY hh:mm A',
  },
}

export default appConfig
