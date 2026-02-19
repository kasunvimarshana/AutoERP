import { defineStore } from 'pinia';
import { ref } from 'vue';
import { salesService } from '../services/salesService';

/**
 * Sales Store
 * 
 * Manages Sales module state (quotations, orders, invoices)
 */
export const useSalesStore = defineStore('sales', () => {
    // State
    const quotations = ref([]);
    const orders = ref([]);
    const invoices = ref([]);
    const loading = ref(false);
    const error = ref(null);

    // Actions - Quotations
    async function fetchQuotations(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await salesService.quotations.getAll(params);
            quotations.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function createQuotation(data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await salesService.quotations.create(data);
            quotations.value.push(response.data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function updateQuotation(id, data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await salesService.quotations.update(id, data);
            const index = quotations.value.findIndex(q => q.id === id);
            if (index !== -1) {
                quotations.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function deleteQuotation(id) {
        loading.value = true;
        error.value = null;
        try {
            await salesService.quotations.delete(id);
            quotations.value = quotations.value.filter(q => q.id !== id);
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function sendQuotation(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await salesService.quotations.sendEmail(id);
            const index = quotations.value.findIndex(q => q.id === id);
            if (index !== -1) {
                quotations.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function convertQuotationToOrder(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await salesService.quotations.convertToOrder(id);
            const index = quotations.value.findIndex(q => q.id === id);
            if (index !== -1) {
                quotations.value[index] = response.data;
            }
            await fetchOrders(); // Refresh orders list
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    // Actions - Orders
    async function fetchOrders(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await salesService.orders.getAll(params);
            orders.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function createOrder(data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await salesService.orders.create(data);
            orders.value.push(response.data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function updateOrder(id, data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await salesService.orders.update(id, data);
            const index = orders.value.findIndex(o => o.id === id);
            if (index !== -1) {
                orders.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function deleteOrder(id) {
        loading.value = true;
        error.value = null;
        try {
            await salesService.orders.delete(id);
            orders.value = orders.value.filter(o => o.id !== id);
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function confirmOrder(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await salesService.orders.confirm(id);
            const index = orders.value.findIndex(o => o.id === id);
            if (index !== -1) {
                orders.value[index] = response.data;
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
            const response = await salesService.invoices.getAll(params);
            invoices.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function createInvoice(data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await salesService.invoices.create(data);
            invoices.value.push(response.data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function updateInvoice(id, data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await salesService.invoices.update(id, data);
            const index = invoices.value.findIndex(i => i.id === id);
            if (index !== -1) {
                invoices.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function deleteInvoice(id) {
        loading.value = true;
        error.value = null;
        try {
            await salesService.invoices.delete(id);
            invoices.value = invoices.value.filter(i => i.id !== id);
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function markInvoiceAsPaid(id, data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await salesService.invoices.markAsPaid(id, data);
            const index = invoices.value.findIndex(i => i.id === id);
            if (index !== -1) {
                invoices.value[index] = response.data;
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
        quotations,
        orders,
        invoices,
        loading,
        error,

        // Actions - Quotations
        fetchQuotations,
        createQuotation,
        updateQuotation,
        deleteQuotation,
        sendQuotation,
        convertQuotationToOrder,

        // Actions - Orders
        fetchOrders,
        createOrder,
        updateOrder,
        deleteOrder,
        confirmOrder,

        // Actions - Invoices
        fetchInvoices,
        createInvoice,
        updateInvoice,
        deleteInvoice,
        markInvoiceAsPaid,
    };
});
