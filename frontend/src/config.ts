// API base URL configuration
export const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'

// API endpoints
export const API_ENDPOINTS = {
  auth: {
    login: '/auth/login',
    register: '/auth/register',
    logout: '/auth/logout',
    user: '/auth/user',
    refresh: '/auth/refresh',
  },
  iam: {
    users: '/iam/users',
    roles: '/iam/roles',
  },
  inventory: {
    products: '/inventory/products',
    warehouses: '/inventory/warehouses',
  },
}

// App configuration
export const APP_CONFIG = {
  name: 'ERP System',
  version: '0.1.0',
  locale: 'en',
  theme: 'default',
}
