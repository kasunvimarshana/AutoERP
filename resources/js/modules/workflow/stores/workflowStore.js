import { defineStore } from 'pinia';
import { ref } from 'vue';
import { workflowService } from '../services/workflowService';

/**
 * Workflow Store
 * 
 * Manages Workflow module state (definitions, instances, tasks)
 */
export const useWorkflowStore = defineStore('workflow', () => {
    // State
    const definitions = ref([]);
    const instances = ref([]);
    const tasks = ref([]);
    const loading = ref(false);
    const error = ref(null);

    // Actions - Workflow Definitions
    async function fetchDefinitions(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await workflowService.definitions.getAll(params);
            definitions.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function createDefinition(data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await workflowService.definitions.create(data);
            definitions.value.push(response.data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function updateDefinition(id, data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await workflowService.definitions.update(id, data);
            const index = definitions.value.findIndex(d => d.id === id);
            if (index !== -1) {
                definitions.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function deleteDefinition(id) {
        loading.value = true;
        error.value = null;
        try {
            await workflowService.definitions.delete(id);
            definitions.value = definitions.value.filter(d => d.id !== id);
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function activateDefinition(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await workflowService.definitions.activate(id);
            const index = definitions.value.findIndex(d => d.id === id);
            if (index !== -1) {
                definitions.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function deactivateDefinition(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await workflowService.definitions.deactivate(id);
            const index = definitions.value.findIndex(d => d.id === id);
            if (index !== -1) {
                definitions.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function executeDefinition(id, data = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await workflowService.definitions.execute(id, data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    // Actions - Workflow Instances
    async function fetchInstances(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await workflowService.instances.getAll(params);
            instances.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function createInstance(data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await workflowService.instances.create(data);
            instances.value.push(response.data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function cancelInstance(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await workflowService.instances.cancel(id);
            const index = instances.value.findIndex(i => i.id === id);
            if (index !== -1) {
                instances.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function fetchInstanceHistory(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await workflowService.instances.getHistory(id);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    // Actions - Tasks
    async function fetchTasks(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await workflowService.tasks.getAll(params);
            tasks.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function completeTask(id, data = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await workflowService.tasks.complete(id, data);
            const index = tasks.value.findIndex(t => t.id === id);
            if (index !== -1) {
                tasks.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function assignTask(id, userId) {
        loading.value = true;
        error.value = null;
        try {
            const response = await workflowService.tasks.assign(id, userId);
            const index = tasks.value.findIndex(t => t.id === id);
            if (index !== -1) {
                tasks.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function reassignTask(id, userId) {
        loading.value = true;
        error.value = null;
        try {
            const response = await workflowService.tasks.reassign(id, userId);
            const index = tasks.value.findIndex(t => t.id === id);
            if (index !== -1) {
                tasks.value[index] = response.data;
            }
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
        definitions,
        instances,
        tasks,
        loading,
        error,

        // Actions - Workflow Definitions
        fetchDefinitions,
        createDefinition,
        updateDefinition,
        deleteDefinition,
        activateDefinition,
        deactivateDefinition,
        executeDefinition,

        // Actions - Workflow Instances
        fetchInstances,
        createInstance,
        cancelInstance,
        fetchInstanceHistory,

        // Actions - Tasks
        fetchTasks,
        completeTask,
        assignTask,
        reassignTask,
    };
});
