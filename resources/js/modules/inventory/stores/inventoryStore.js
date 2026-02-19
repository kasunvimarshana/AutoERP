import { defineStore } from 'pinia';
import { ref } from 'vue';
import { inventoryService } from '../services/inventoryService';

/**
 * Inventory Store
 * 
 * Manages Inventory module state (warehouses, stock, inventory counts)
 */
export const useInventoryStore = defineStore('inventory', () => {
    // State
    const warehouses = ref([]);
    const stock = ref([]);
    const inventoryCounts = ref([]);
    const movements = ref([]);
    const loading = ref(false);
    const error = ref(null);

    // Actions - Warehouses
    async function fetchWarehouses(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await inventoryService.warehouses.getAll(params);
            warehouses.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function createWarehouse(data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await inventoryService.warehouses.create(data);
            warehouses.value.push(response.data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function updateWarehouse(id, data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await inventoryService.warehouses.update(id, data);
            const index = warehouses.value.findIndex(w => w.id === id);
            if (index !== -1) {
                warehouses.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function deleteWarehouse(id) {
        loading.value = true;
        error.value = null;
        try {
            await inventoryService.warehouses.delete(id);
            warehouses.value = warehouses.value.filter(w => w.id !== id);
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function fetchWarehouseStock(id, params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await inventoryService.warehouses.getStock(id, params);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function activateWarehouse(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await inventoryService.warehouses.activate(id);
            const index = warehouses.value.findIndex(w => w.id === id);
            if (index !== -1) {
                warehouses.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function deactivateWarehouse(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await inventoryService.warehouses.deactivate(id);
            const index = warehouses.value.findIndex(w => w.id === id);
            if (index !== -1) {
                warehouses.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    // Actions - Stock
    async function fetchStock(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await inventoryService.stock.getAll(params);
            stock.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function fetchStockByProduct(productId, params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await inventoryService.stock.getByProduct(productId, params);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function adjustStock(data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await inventoryService.stock.adjustStock(data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function transferStock(data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await inventoryService.stock.transferStock(data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function fetchMovements(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await inventoryService.stock.getMovements(params);
            movements.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function reserveStock(data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await inventoryService.stock.reserveStock(data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function releaseStock(data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await inventoryService.stock.releaseStock(data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function receiveStock(data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await inventoryService.stock.receiveStock(data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function issueStock(data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await inventoryService.stock.issueStock(data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    // Actions - Inventory Counts
    async function fetchInventoryCounts(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await inventoryService.inventoryCounts.getAll(params);
            inventoryCounts.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function createInventoryCount(data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await inventoryService.inventoryCounts.create(data);
            inventoryCounts.value.push(response.data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function updateInventoryCount(id, data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await inventoryService.inventoryCounts.update(id, data);
            const index = inventoryCounts.value.findIndex(ic => ic.id === id);
            if (index !== -1) {
                inventoryCounts.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function validateInventoryCount(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await inventoryService.inventoryCounts.validate(id);
            const index = inventoryCounts.value.findIndex(ic => ic.id === id);
            if (index !== -1) {
                inventoryCounts.value[index] = response.data;
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
        warehouses,
        stock,
        inventoryCounts,
        movements,
        loading,
        error,

        // Actions - Warehouses
        fetchWarehouses,
        createWarehouse,
        updateWarehouse,
        deleteWarehouse,
        fetchWarehouseStock,
        activateWarehouse,
        deactivateWarehouse,

        // Actions - Stock
        fetchStock,
        fetchStockByProduct,
        adjustStock,
        transferStock,
        fetchMovements,
        reserveStock,
        releaseStock,
        receiveStock,
        issueStock,

        // Actions - Inventory Counts
        fetchInventoryCounts,
        createInventoryCount,
        updateInventoryCount,
        validateInventoryCount,
    };
});
