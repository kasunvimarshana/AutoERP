import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/composables/useApi';

export const useProjectsStore = defineStore('projects', () => {
    const projects = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const meta = ref({ current_page: 1, last_page: 1, total: 0 });

    const tasks = ref([]);
    const tasksLoading = ref(false);
    const tasksError = ref(null);
    const tasksMeta = ref({ current_page: 1, last_page: 1, total: 0 });

    const milestones = ref([]);
    const milestonesLoading = ref(false);
    const milestonesError = ref(null);
    const milestonesMeta = ref({ current_page: 1, last_page: 1, total: 0 });

    const timeEntries = ref([]);
    const timeEntriesLoading = ref(false);
    const timeEntriesError = ref(null);
    const timeEntriesMeta = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchProjects(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/pm/projects', { params: { page } });
            projects.value = data.data ?? data;
            if (data.meta) meta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load projects.';
        } finally {
            loading.value = false;
        }
    }

    async function fetchTasks(page = 1) {
        tasksLoading.value = true;
        tasksError.value = null;
        try {
            const { data } = await api.get('/api/v1/pm/tasks', { params: { page } });
            tasks.value = data.data ?? data;
            if (data.meta) tasksMeta.value = data.meta;
        } catch (e) {
            tasksError.value = e.response?.data?.message ?? 'Failed to load tasks.';
        } finally {
            tasksLoading.value = false;
        }
    }

    async function fetchMilestones(page = 1) {
        milestonesLoading.value = true;
        milestonesError.value = null;
        try {
            const { data } = await api.get('/api/v1/pm/milestones', { params: { page } });
            milestones.value = data.data ?? data;
            if (data.meta) milestonesMeta.value = data.meta;
        } catch (e) {
            milestonesError.value = e.response?.data?.message ?? 'Failed to load milestones.';
        } finally {
            milestonesLoading.value = false;
        }
    }

    async function fetchTimeEntries(page = 1) {
        timeEntriesLoading.value = true;
        timeEntriesError.value = null;
        try {
            const { data } = await api.get('/api/v1/pm/time-entries', { params: { page } });
            timeEntries.value = data.data ?? data;
            if (data.meta) timeEntriesMeta.value = data.meta;
        } catch (e) {
            timeEntriesError.value = e.response?.data?.message ?? 'Failed to load time entries.';
        } finally {
            timeEntriesLoading.value = false;
        }
    }

    return {
        projects, loading, error, meta, fetchProjects,
        tasks, tasksLoading, tasksError, tasksMeta, fetchTasks,
        milestones, milestonesLoading, milestonesError, milestonesMeta, fetchMilestones,
        timeEntries, timeEntriesLoading, timeEntriesError, timeEntriesMeta, fetchTimeEntries,
    };
});

