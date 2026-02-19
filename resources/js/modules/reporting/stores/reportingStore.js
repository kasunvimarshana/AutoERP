import { defineStore } from 'pinia';
import { ref } from 'vue';
import { reportingService } from '../services/reportingService';

/**
 * Reporting Store
 * 
 * Manages Reporting module state (reports, dashboards, analytics)
 */
export const useReportingStore = defineStore('reporting', () => {
    // State
    const reports = ref([]);
    const dashboards = ref([]);
    const loading = ref(false);
    const error = ref(null);

    // Actions - Reports
    async function fetchReports(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await reportingService.reports.getAll(params);
            reports.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function createReport(data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await reportingService.reports.create(data);
            reports.value.push(response.data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function updateReport(id, data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await reportingService.reports.update(id, data);
            const index = reports.value.findIndex(r => r.id === id);
            if (index !== -1) {
                reports.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function deleteReport(id) {
        loading.value = true;
        error.value = null;
        try {
            await reportingService.reports.delete(id);
            reports.value = reports.value.filter(r => r.id !== id);
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function generateReport(id, params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await reportingService.reports.generate(id, params);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function exportReport(id, format = 'pdf', params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await reportingService.reports.export(id, format, params);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function scheduleReport(id, data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await reportingService.reports.schedule(id, data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function executeReport(id, params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await reportingService.reports.execute(id, params);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    // Actions - Dashboards
    async function fetchDashboards(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await reportingService.dashboards.getAll(params);
            dashboards.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function createDashboard(data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await reportingService.dashboards.create(data);
            dashboards.value.push(response.data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function updateDashboard(id, data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await reportingService.dashboards.update(id, data);
            const index = dashboards.value.findIndex(d => d.id === id);
            if (index !== -1) {
                dashboards.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function deleteDashboard(id) {
        loading.value = true;
        error.value = null;
        try {
            await reportingService.dashboards.delete(id);
            dashboards.value = dashboards.value.filter(d => d.id !== id);
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function setDefaultDashboard(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await reportingService.dashboards.setDefault(id);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function duplicateDashboard(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await reportingService.dashboards.duplicate(id);
            dashboards.value.push(response.data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function shareDashboard(id, data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await reportingService.dashboards.share(id, data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function fetchDashboardData(id, params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await reportingService.dashboards.getData(id, params);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    // Actions - Analytics
    async function fetchSalesAnalytics(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await reportingService.analytics.getSalesAnalytics(params);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function fetchRevenueAnalytics(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await reportingService.analytics.getRevenueAnalytics(params);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function fetchCustomerAnalytics(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await reportingService.analytics.getCustomerAnalytics(params);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function fetchProductAnalytics(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await reportingService.analytics.getProductAnalytics(params);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function fetchInventoryAnalytics(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await reportingService.analytics.getInventoryAnalytics(params);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    return {
        // State
        reports,
        dashboards,
        loading,
        error,

        // Actions - Reports
        fetchReports,
        createReport,
        updateReport,
        deleteReport,
        generateReport,
        exportReport,
        scheduleReport,
        executeReport,

        // Actions - Dashboards
        fetchDashboards,
        createDashboard,
        updateDashboard,
        deleteDashboard,
        setDefaultDashboard,
        duplicateDashboard,
        shareDashboard,
        fetchDashboardData,

        // Actions - Analytics
        fetchSalesAnalytics,
        fetchRevenueAnalytics,
        fetchCustomerAnalytics,
        fetchProductAnalytics,
        fetchInventoryAnalytics,
    };
});
