import { defineStore } from 'pinia';
import { ref } from 'vue';
import { productService } from '../services/productService';

/**
 * Product Store
 * 
 * Manages Product module state (products, categories, units)
 */
export const useProductStore = defineStore('product', () => {
    // State
    const products = ref([]);
    const categories = ref([]);
    const units = ref([]);
    const loading = ref(false);
    const error = ref(null);

    // Actions - Products
    async function fetchProducts(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await productService.getAll(params);
            products.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function getProduct(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await productService.getById(id);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function createProduct(data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await productService.create(data);
            products.value.push(response.data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function updateProduct(id, data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await productService.update(id, data);
            const index = products.value.findIndex(p => p.id === id);
            if (index !== -1) {
                products.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function deleteProduct(id) {
        loading.value = true;
        error.value = null;
        try {
            await productService.delete(id);
            products.value = products.value.filter(p => p.id !== id);
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    // Actions - Categories
    async function fetchCategories(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await productService.categories.getAll(params);
            categories.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function createCategory(data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await productService.categories.create(data);
            categories.value.push(response.data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function updateCategory(id, data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await productService.categories.update(id, data);
            const index = categories.value.findIndex(c => c.id === id);
            if (index !== -1) {
                categories.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function deleteCategory(id) {
        loading.value = true;
        error.value = null;
        try {
            await productService.categories.delete(id);
            categories.value = categories.value.filter(c => c.id !== id);
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    // Actions - Units
    async function fetchUnits(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await productService.units.getAll(params);
            units.value = response.data;
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
        products,
        categories,
        units,
        loading,
        error,

        // Actions - Products
        fetchProducts,
        getProduct,
        createProduct,
        updateProduct,
        deleteProduct,

        // Actions - Categories
        fetchCategories,
        createCategory,
        updateCategory,
        deleteCategory,

        // Actions - Units
        fetchUnits,
    };
});
