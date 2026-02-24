import axios from 'axios';

const api = axios.create({
    baseURL: window.location.origin,
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    },
});

api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            // Dispatch a custom event so the auth store (or app root) can react
            // without creating a circular import between this module and the store.
            window.dispatchEvent(new CustomEvent('auth:unauthorized'));
        }
        return Promise.reject(error);
    },
);

export default api;
