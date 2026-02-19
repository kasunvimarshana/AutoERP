import { defineStore } from 'pinia';
import { ref } from 'vue';
import { billingService } from '../services/billingService';

/**
 * Billing Store
 * 
 * Manages Billing module state (plans, subscriptions, payments)
 */
export const useBillingStore = defineStore('billing', () => {
    // State
    const plans = ref([]);
    const subscriptions = ref([]);
    const payments = ref([]);
    const invoices = ref([]);
    const loading = ref(false);
    const error = ref(null);

    // Actions - Plans
    async function fetchPlans(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await billingService.plans.getAll(params);
            plans.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function createPlan(data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await billingService.plans.create(data);
            plans.value.push(response.data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function updatePlan(id, data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await billingService.plans.update(id, data);
            const index = plans.value.findIndex(p => p.id === id);
            if (index !== -1) {
                plans.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function deletePlan(id) {
        loading.value = true;
        error.value = null;
        try {
            await billingService.plans.delete(id);
            plans.value = plans.value.filter(p => p.id !== id);
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function activatePlan(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await billingService.plans.activate(id);
            const index = plans.value.findIndex(p => p.id === id);
            if (index !== -1) {
                plans.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function deactivatePlan(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await billingService.plans.deactivate(id);
            const index = plans.value.findIndex(p => p.id === id);
            if (index !== -1) {
                plans.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    // Actions - Subscriptions
    async function fetchSubscriptions(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await billingService.subscriptions.getAll(params);
            subscriptions.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function createSubscription(data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await billingService.subscriptions.create(data);
            subscriptions.value.push(response.data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function updateSubscription(id, data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await billingService.subscriptions.update(id, data);
            const index = subscriptions.value.findIndex(s => s.id === id);
            if (index !== -1) {
                subscriptions.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function cancelSubscription(id, data = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await billingService.subscriptions.cancel(id, data);
            const index = subscriptions.value.findIndex(s => s.id === id);
            if (index !== -1) {
                subscriptions.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function renewSubscription(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await billingService.subscriptions.renew(id);
            const index = subscriptions.value.findIndex(s => s.id === id);
            if (index !== -1) {
                subscriptions.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function suspendSubscription(id, data = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await billingService.subscriptions.suspend(id, data);
            const index = subscriptions.value.findIndex(s => s.id === id);
            if (index !== -1) {
                subscriptions.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function resumeSubscription(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await billingService.subscriptions.resume(id);
            const index = subscriptions.value.findIndex(s => s.id === id);
            if (index !== -1) {
                subscriptions.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function changeSubscriptionPlan(id, data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await billingService.subscriptions.changePlan(id, data);
            const index = subscriptions.value.findIndex(s => s.id === id);
            if (index !== -1) {
                subscriptions.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    // Actions - Payments
    async function fetchPayments(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await billingService.payments.getAll(params);
            payments.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function processPayment(data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await billingService.payments.process(data);
            payments.value.push(response.data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function refundPayment(id, data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await billingService.payments.refund(id, data);
            const index = payments.value.findIndex(p => p.id === id);
            if (index !== -1) {
                payments.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    // Actions - Invoices
    async function fetchInvoices(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await billingService.invoices.getAll(params);
            invoices.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function downloadInvoice(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await billingService.invoices.download(id);
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
        plans,
        subscriptions,
        payments,
        invoices,
        loading,
        error,

        // Actions - Plans
        fetchPlans,
        createPlan,
        updatePlan,
        deletePlan,
        activatePlan,
        deactivatePlan,

        // Actions - Subscriptions
        fetchSubscriptions,
        createSubscription,
        updateSubscription,
        cancelSubscription,
        renewSubscription,
        suspendSubscription,
        resumeSubscription,
        changeSubscriptionPlan,

        // Actions - Payments
        fetchPayments,
        processPayment,
        refundPayment,

        // Actions - Invoices
        fetchInvoices,
        downloadInvoice,
    };
});
