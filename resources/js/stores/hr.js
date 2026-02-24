import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/composables/useApi';

export const useHrStore = defineStore('hr', () => {
    const employees = ref([]);
    const departments = ref([]);
    const attendance = ref([]);
    const performanceGoals = ref([]);
    const salaryComponents = ref([]);
    const salaryStructures = ref([]);
    const loading = ref(false);
    const attendanceLoading = ref(false);
    const goalsLoading = ref(false);
    const componentsLoading = ref(false);
    const structuresLoading = ref(false);
    const error = ref(null);
    const meta = ref({ current_page: 1, last_page: 1, total: 0 });
    const attendanceMeta = ref({ current_page: 1, last_page: 1, total: 0 });
    const goalsMeta = ref({ current_page: 1, last_page: 1, total: 0 });
    const componentsMeta = ref({ current_page: 1, last_page: 1, total: 0 });
    const structuresMeta = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchEmployees(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/hr/employees', { params: { page } });
            employees.value = data.data ?? data;
            if (data.meta) meta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load employees.';
        } finally {
            loading.value = false;
        }
    }

    async function fetchDepartments() {
        try {
            const { data } = await api.get('/api/v1/hr/departments');
            departments.value = data.data ?? data;
        } catch {
            // non-critical; ignore
        }
    }

    async function fetchAttendance(page = 1, filters = {}) {
        attendanceLoading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/hr/attendance', { params: { page, ...filters } });
            attendance.value = data.data ?? data;
            if (data.meta) attendanceMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load attendance records.';
        } finally {
            attendanceLoading.value = false;
        }
    }

    async function fetchPerformanceGoals(page = 1, filters = {}) {
        goalsLoading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/hr/performance-goals', { params: { page, ...filters } });
            performanceGoals.value = data.data ?? data;
            if (data.meta) goalsMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load performance goals.';
        } finally {
            goalsLoading.value = false;
        }
    }

    async function fetchSalaryComponents(page = 1) {
        componentsLoading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/hr/salary-components', { params: { page } });
            salaryComponents.value = data.data ?? data;
            if (data.meta) componentsMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load salary components.';
        } finally {
            componentsLoading.value = false;
        }
    }

    async function fetchSalaryStructures(page = 1) {
        structuresLoading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/hr/salary-structures', { params: { page } });
            salaryStructures.value = data.data ?? data;
            if (data.meta) structuresMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load salary structures.';
        } finally {
            structuresLoading.value = false;
        }
    }

    return {
        employees, departments, attendance, performanceGoals, salaryComponents, salaryStructures,
        loading, attendanceLoading, goalsLoading, componentsLoading, structuresLoading, error,
        meta, attendanceMeta, goalsMeta, componentsMeta, structuresMeta,
        fetchEmployees, fetchDepartments, fetchAttendance, fetchPerformanceGoals,
        fetchSalaryComponents, fetchSalaryStructures,
    };
});
