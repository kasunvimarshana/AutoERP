import apiClient from '@/services/apiClient';

/**
 * Accounting Service
 * 
 * API service for Accounting module operations (accounts, journal entries, reports)
 */
export const accountingService = {
    // Account Operations
    accounts: {
        async getAll(params = {}) {
            const response = await apiClient.get('/accounting/accounts', { params });
            return response.data;
        },

        async getById(id) {
            const response = await apiClient.get(`/accounting/accounts/${id}`);
            return response.data;
        },

        async create(data) {
            const response = await apiClient.post('/accounting/accounts', data);
            return response.data;
        },

        async update(id, data) {
            const response = await apiClient.put(`/accounting/accounts/${id}`, data);
            return response.data;
        },

        async delete(id) {
            const response = await apiClient.delete(`/accounting/accounts/${id}`);
            return response.data;
        },

        async getBalance(id, params = {}) {
            const response = await apiClient.get(`/accounting/accounts/${id}/balance`, { params });
            return response.data;
        },
    },

    // Journal Entry Operations
    journalEntries: {
        async getAll(params = {}) {
            const response = await apiClient.get('/accounting/journal-entries', { params });
            return response.data;
        },

        async getById(id) {
            const response = await apiClient.get(`/accounting/journal-entries/${id}`);
            return response.data;
        },

        async create(data) {
            const response = await apiClient.post('/accounting/journal-entries', data);
            return response.data;
        },

        async update(id, data) {
            const response = await apiClient.put(`/accounting/journal-entries/${id}`, data);
            return response.data;
        },

        async delete(id) {
            const response = await apiClient.delete(`/accounting/journal-entries/${id}`);
            return response.data;
        },

        async post(id) {
            const response = await apiClient.post(`/accounting/journal-entries/${id}/post`);
            return response.data;
        },

        async reverse(id) {
            const response = await apiClient.post(`/accounting/journal-entries/${id}/reverse`);
            return response.data;
        },
    },

    // Financial Reports
    reports: {
        async getBalanceSheet(params = {}) {
            const response = await apiClient.get('/accounting/reports/balance-sheet', { params });
            return response.data;
        },

        async getIncomeStatement(params = {}) {
            const response = await apiClient.get('/accounting/reports/income-statement', { params });
            return response.data;
        },

        async getCashFlow(params = {}) {
            const response = await apiClient.get('/accounting/reports/cash-flow', { params });
            return response.data;
        },

        async getTrialBalance(params = {}) {
            const response = await apiClient.get('/accounting/reports/trial-balance', { params });
            return response.data;
        },

        async getGeneralLedger(params = {}) {
            const response = await apiClient.get('/accounting/reports/general-ledger', { params });
            return response.data;
        },
    },
};
