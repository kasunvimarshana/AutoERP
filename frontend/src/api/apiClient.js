import axios from 'axios';
import keycloak from '../keycloak';
import { toast } from 'react-toastify';

const createClient = (baseURL) => {
  const client = axios.create({ baseURL, headers: { 'Content-Type': 'application/json' } });

  client.interceptors.request.use(async (config) => {
    if (keycloak.authenticated) {
      try { await keycloak.updateToken(30); } catch { keycloak.login(); }
      config.headers.Authorization = `Bearer ${keycloak.token}`;
    }
    return config;
  });

  client.interceptors.response.use(
    (res) => res,
    (err) => {
      const status = err.response?.status;
      if (status === 401) keycloak.login();
      else if (status === 403) toast.error('Access denied.');
      else if (status >= 500) toast.error('Server error. Please try again.');
      return Promise.reject(err);
    }
  );
  return client;
};

export const productClient   = createClient(import.meta.env.VITE_PRODUCT_API_URL   || 'http://localhost:8001/api/v1');
export const inventoryClient = createClient(import.meta.env.VITE_INVENTORY_API_URL || 'http://localhost:8002/api/v1');
export const orderClient     = createClient(import.meta.env.VITE_ORDER_API_URL     || 'http://localhost:8003/api/v1');
export const userClient      = createClient(import.meta.env.VITE_USER_API_URL      || 'http://localhost:8004/api/v1');
