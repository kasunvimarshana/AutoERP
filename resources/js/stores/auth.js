import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/composables/useApi';

function parseStoredUser() {
    try {
        const raw = localStorage.getItem('user');
        if (!raw) return null;
        const parsed = JSON.parse(raw);
        // Basic structural validation — must be a plain object with a string id
        if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) {
            return parsed;
        }
        return null;
    } catch {
        return null;
    }
}

export const useAuthStore = defineStore('auth', () => {
    const user = ref(parseStoredUser());
    const token = ref(localStorage.getItem('token') ?? null);

    const isAuthenticated = computed(() => !!token.value);

    function setAuth(userData, accessToken) {
        user.value = userData;
        token.value = accessToken;
        localStorage.setItem('user', JSON.stringify(userData));
        localStorage.setItem('token', accessToken);
        api.defaults.headers.common['Authorization'] = `Bearer ${accessToken}`;
    }

    function clearAuth() {
        user.value = null;
        token.value = null;
        localStorage.removeItem('user');
        localStorage.removeItem('token');
        delete api.defaults.headers.common['Authorization'];
    }

    async function login(email, password) {
        const { data } = await api.post('/api/v1/auth/login', { email, password });
        setAuth(data.data.user, data.data.token);
    }

    async function register(name, email, password, passwordConfirmation, tenantId = null) {
        const payload = { name, email, password, password_confirmation: passwordConfirmation };
        if (tenantId) {
            payload.tenant_id = tenantId;
        }
        const { data } = await api.post('/api/v1/auth/register', payload);
        setAuth(data.data.user, data.data.token);
    }

    async function logout() {
        try {
            await api.post('/api/v1/auth/logout');
        } finally {
            clearAuth();
        }
    }

    // Restore token header on page load
    if (token.value) {
        api.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;
    }

    return { user, token, isAuthenticated, login, register, logout, clearAuth };
});
