import axios from 'axios';

const BASE_URL = process.env.REACT_APP_API_URL || '/api/v1';

const api = axios.create({ baseURL: BASE_URL });

// Attach Authorization header from localStorage/Keycloak token
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('kc_token') || sessionStorage.getItem('kc_token');
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

api.interceptors.response.use(
  (res) => res,
  (err) => {
    if (err.response?.status === 401) window.location.href = '/login';
    return Promise.reject(err);
  }
);

// ── Products ──────────────────────────────────────────────────────────────────
export const productApi = {
  list: (params) => api.get('/products', { params }),
  get: (id) => api.get(`/products/${id}`),
  create: (data) => api.post('/products', data),
  update: (id, data) => api.put(`/products/${id}`, data),
  delete: (id) => api.delete(`/products/${id}`),
  categories: {
    list: (params) => api.get('/categories', { params }),
    create: (data) => api.post('/categories', data),
    update: (id, data) => api.put(`/categories/${id}`, data),
    delete: (id) => api.delete(`/categories/${id}`),
  },
};

// ── Inventory ─────────────────────────────────────────────────────────────────
export const inventoryApi = {
  list: (params) => api.get('/inventory', { params }),
  get: (id) => api.get(`/inventory/${id}`),
  create: (data) => api.post('/inventory', data),
  update: (id, data) => api.put(`/inventory/${id}`, data),
  adjustStock: (id, data) => api.post(`/inventory/${id}/adjust`, data),
  reserveStock: (id, data) => api.post(`/inventory/${id}/reserve`, data),
  releaseStock: (id, data) => api.post(`/inventory/${id}/release`, data),
  warehouses: {
    list: (params) => api.get('/warehouses', { params }),
    create: (data) => api.post('/warehouses', data),
    update: (id, data) => api.put(`/warehouses/${id}`, data),
    delete: (id) => api.delete(`/warehouses/${id}`),
  },
};

// ── Orders ────────────────────────────────────────────────────────────────────
export const orderApi = {
  list: (params) => api.get('/orders', { params }),
  get: (id) => api.get(`/orders/${id}`),
  create: (data) => api.post('/orders', data),
  updateStatus: (id, data) => api.patch(`/orders/${id}/status`, data),
  cancel: (id, data) => api.post(`/orders/${id}/cancel`, data),
};

// ── Users ─────────────────────────────────────────────────────────────────────
export const userApi = {
  list: (params) => api.get('/users', { params }),
  get: (id) => api.get(`/users/${id}`),
  create: (data) => api.post('/users', data),
  update: (id, data) => api.put(`/users/${id}`, data),
  delete: (id) => api.delete(`/users/${id}`),
  tenants: {
    list: (params) => api.get('/tenants', { params }),
    create: (data) => api.post('/tenants', data),
    update: (id, data) => api.put(`/tenants/${id}`, data),
    delete: (id) => api.delete(`/tenants/${id}`),
  },
};

export default api;
