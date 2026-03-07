import axios from 'axios';

// Base instance targeting our API Gateway or direct microservice routing proxy.
export const apiClient = axios.create({
    baseURL: 'http://localhost:8000/api', // e.g. Kong gateway or Nginx routing to services
    headers: {
        'Content-Type': 'application/json',
    },
});

/**
 * Configure standard authorization interceptor that appends JWT token
 * obtained via Keycloak inside the UI components.
 */
export const configureAxiosAuth = (token: string | null) => {
    apiClient.interceptors.request.use((config) => {
        if (token && config.headers) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    });
};

// --- Product Service Endpoints ---
export const productApi = {
    fetchProducts: async (params?: Record<string, any>) => {
        // Includes sophisticated filtering, sorting, pagination params
        return await apiClient.get('/products-service/products', { params });
    },
    createProduct: async (data: Record<string, any>) => {
        return await apiClient.post('/products-service/products', data);
    },
    deleteProduct: async (id: number) => {
        return await apiClient.delete(`/products-service/products/${id}`);
    }
};
