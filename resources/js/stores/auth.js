import { defineStore } from 'pinia';
import api from '../services/api';
import * as authService from '../services/auth';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        tenant: null,
        token: localStorage.getItem('token') || null,
        loading: false,
        error: null,
    }),

    getters: {
        isAuthenticated: (state) => !!state.token && !!state.user,
        currentUser: (state) => state.user,
        currentTenant: (state) => state.tenant,
    },

    actions: {
        async login(credentials) {
            this.loading = true;
            this.error = null;
            try {
                const response = await authService.login(credentials);
                this.token = response.token;
                this.user = response.user;
                this.tenant = response.tenant;
                
                localStorage.setItem('token', this.token);
                api.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
                
                return response;
            } catch (error) {
                this.error = error.response?.data?.message || 'Login failed';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async register(data) {
            this.loading = true;
            this.error = null;
            try {
                const response = await authService.register(data);
                this.token = response.token;
                this.user = response.user;
                this.tenant = response.tenant;
                
                localStorage.setItem('token', this.token);
                api.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
                
                return response;
            } catch (error) {
                this.error = error.response?.data?.message || 'Registration failed';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async logout() {
            try {
                await authService.logout();
            } catch (error) {
                console.error('Logout error:', error);
            } finally {
                this.user = null;
                this.tenant = null;
                this.token = null;
                this.error = null;
                localStorage.removeItem('token');
                delete api.defaults.headers.common['Authorization'];
            }
        },

        async checkAuth() {
            if (!this.token) {
                return false;
            }

            try {
                api.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
                const response = await authService.getMe();
                this.user = response.user;
                this.tenant = response.tenant;
                return true;
            } catch (error) {
                await this.logout();
                return false;
            }
        },

        async getMe() {
            try {
                const response = await authService.getMe();
                this.user = response.user;
                this.tenant = response.tenant;
                return response;
            } catch (error) {
                throw error;
            }
        },
    },
});
