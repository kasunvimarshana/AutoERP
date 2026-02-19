import { defineStore } from 'pinia';
import { ref } from 'vue';
import { purchaseService } from '../services/purchaseService';

/**
 * Purchase Store
 * 
 * Manages Purchase module state (vendors, purchase orders, bills)
 */
export const usePurchaseStore = defineStore('purchase', () => {
    // State
    const vendors = ref([]);
    const purchaseOrders = ref([]);
    const bills = ref([]);
    const loading = ref(false);
    const error = ref(null);

    // Actions - Vendors
    async function fetchVendors(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await purchaseService.vendors.getAll(params);
            vendors.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function createVendor(data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await purchaseService.vendors.create(data);
            vendors.value.push(response.data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function updateVendor(id, data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await purchaseService.vendors.update(id, data);
            const index = vendors.value.findIndex(v => v.id === id);
            if (index !== -1) {
                vendors.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function deleteVendor(id) {
        loading.value = true;
        error.value = null;
        try {
            await purchaseService.vendors.delete(id);
            vendors.value = vendors.value.filter(v => v.id !== id);
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function activateVendor(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await purchaseService.vendors.activate(id);
            const index = vendors.value.findIndex(v => v.id === id);
            if (index !== -1) {
                vendors.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function deactivateVendor(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await purchaseService.vendors.deactivate(id);
            const index = vendors.value.findIndex(v => v.id === id);
            if (index !== -1) {
                vendors.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    // Actions - Purchase Orders
    async function fetchPurchaseOrders(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await purchaseService.purchaseOrders.getAll(params);
            purchaseOrders.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function createPurchaseOrder(data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await purchaseService.purchaseOrders.create(data);
            purchaseOrders.value.push(response.data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function updatePurchaseOrder(id, data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await purchaseService.purchaseOrders.update(id, data);
            const index = purchaseOrders.value.findIndex(po => po.id === id);
            if (index !== -1) {
                purchaseOrders.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function deletePurchaseOrder(id) {
        loading.value = true;
        error.value = null;
        try {
            await purchaseService.purchaseOrders.delete(id);
            purchaseOrders.value = purchaseOrders.value.filter(po => po.id !== id);
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function confirmPurchaseOrder(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await purchaseService.purchaseOrders.confirm(id);
            const index = purchaseOrders.value.findIndex(po => po.id === id);
            if (index !== -1) {
                purchaseOrders.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function receivePurchaseOrder(id, data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await purchaseService.purchaseOrders.receive(id, data);
            const index = purchaseOrders.value.findIndex(po => po.id === id);
            if (index !== -1) {
                purchaseOrders.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function approvePurchaseOrder(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await purchaseService.purchaseOrders.approve(id);
            const index = purchaseOrders.value.findIndex(po => po.id === id);
            if (index !== -1) {
                purchaseOrders.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function sendPurchaseOrder(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await purchaseService.purchaseOrders.send(id);
            const index = purchaseOrders.value.findIndex(po => po.id === id);
            if (index !== -1) {
                purchaseOrders.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function cancelPurchaseOrder(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await purchaseService.purchaseOrders.cancel(id);
            const index = purchaseOrders.value.findIndex(po => po.id === id);
            if (index !== -1) {
                purchaseOrders.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    // Actions - Bills
    async function fetchBills(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await purchaseService.bills.getAll(params);
            bills.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function createBill(data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await purchaseService.bills.create(data);
            bills.value.push(response.data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function updateBill(id, data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await purchaseService.bills.update(id, data);
            const index = bills.value.findIndex(b => b.id === id);
            if (index !== -1) {
                bills.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function deleteBill(id) {
        loading.value = true;
        error.value = null;
        try {
            await purchaseService.bills.delete(id);
            bills.value = bills.value.filter(b => b.id !== id);
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function markBillAsPaid(id, data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await purchaseService.bills.markAsPaid(id, data);
            const index = bills.value.findIndex(b => b.id === id);
            if (index !== -1) {
                bills.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function approveBill(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await purchaseService.bills.approve(id);
            const index = bills.value.findIndex(b => b.id === id);
            if (index !== -1) {
                bills.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function recordBillPayment(id, data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await purchaseService.bills.recordPayment(id, data);
            const index = bills.value.findIndex(b => b.id === id);
            if (index !== -1) {
                bills.value[index] = response.data;
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
        vendors,
        purchaseOrders,
        bills,
        loading,
        error,

        // Actions - Vendors
        fetchVendors,
        createVendor,
        updateVendor,
        deleteVendor,
        activateVendor,
        deactivateVendor,

        // Actions - Purchase Orders
        fetchPurchaseOrders,
        createPurchaseOrder,
        updatePurchaseOrder,
        deletePurchaseOrder,
        confirmPurchaseOrder,
        approvePurchaseOrder,
        sendPurchaseOrder,
        cancelPurchaseOrder,
        receivePurchaseOrder,

        // Actions - Bills
        fetchBills,
        createBill,
        updateBill,
        deleteBill,
        approveBill,
        recordBillPayment,
        markBillAsPaid,
    };
});
