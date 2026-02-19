import axios from 'axios';
import router from '../router';

/**
 * API Client
 * 
 * Centralized HTTP client with:
 * - JWT token management
 * - Request/response interceptors
 * - Error handling
 * - Tenant context
 */

const apiClient = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

// Request interceptor - Add JWT token and tenant context
apiClient.interceptors.request.use(
    (config) => {
        // Add JWT token from localStorage
        const token = localStorage.getItem('jwt_token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }

        // Add tenant context
        const tenantId = localStorage.getItem('tenant_id');
        if (tenantId) {
            config.headers['X-Tenant-ID'] = tenantId;
        }

        // Add organization context
        const organizationId = localStorage.getItem('organization_id');
        if (organizationId) {
            config.headers['X-Organization-ID'] = organizationId;
        }

        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Response interceptor - Handle errors and token refresh
apiClient.interceptors.response.use(
    (response) => {
        return response;
    },
    async (error) => {
        const originalRequest = error.config;

        // Handle 401 Unauthorized - Token expired or invalid
        if (error.response?.status === 401 && !originalRequest._retry) {
            originalRequest._retry = true;

            try {
                // Attempt to refresh token
                const refreshToken = localStorage.getItem('refresh_token');
                if (refreshToken) {
                    const response = await axios.post('/api/auth/refresh', {
                        refresh_token: refreshToken,
                    });

                    const { token, refresh_token } = response.data.data;
                    
                    // Store new tokens
                    localStorage.setItem('jwt_token', token);
                    localStorage.setItem('refresh_token', refresh_token);

                    // Retry original request with new token
                    originalRequest.headers.Authorization = `Bearer ${token}`;
                    return apiClient(originalRequest);
                }
            } catch (refreshError) {
                // Refresh failed - redirect to login
                localStorage.clear();
                router.push({ name: 'login' });
                return Promise.reject(refreshError);
            }
        }

        // Handle 403 Forbidden - Insufficient permissions
        if (error.response?.status === 403) {
            console.error('Access denied:', error.response.data.message);
        }

        // Handle 404 Not Found
        if (error.response?.status === 404) {
            console.error('Resource not found:', error.config.url);
        }

        // Handle 422 Validation Error
        if (error.response?.status === 422) {
            console.error('Validation error:', error.response.data.errors);
        }

        // Handle 500 Server Error
        if (error.response?.status === 500) {
            console.error('Server error:', error.response.data.message);
        }

        return Promise.reject(error);
    }
);

export default apiClient;
