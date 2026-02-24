import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/composables/useApi';

export const usePosStore = defineStore('pos', () => {
    const terminals = ref([]);
    const sessions = ref([]);
    const orders = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const terminalsMeta = ref({ current_page: 1, last_page: 1, total: 0 });
    const sessionsMeta = ref({ current_page: 1, last_page: 1, total: 0 });
    const ordersMeta = ref({ current_page: 1, last_page: 1, total: 0 });

    // ── Terminals ────────────────────────────────────────────────────────────

    async function fetchTerminals(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/pos/terminals', { params: { page } });
            terminals.value = data.data ?? data;
            if (data.meta) terminalsMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load terminals.';
        } finally {
            loading.value = false;
        }
    }

    async function createTerminal(payload) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.post('/api/v1/pos/terminals', payload);
            const record = data.data ?? data;
            terminals.value.unshift(record);
            terminalsMeta.value.total = (terminalsMeta.value.total || 0) + 1;
            return record;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to create terminal.';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function updateTerminal(id, payload) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.put(`/api/v1/pos/terminals/${id}`, payload);
            const record = data.data ?? data;
            const idx = terminals.value.findIndex(t => t.id === id);
            if (idx !== -1) terminals.value[idx] = record;
            return record;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to update terminal.';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function deleteTerminal(id) {
        loading.value = true;
        error.value = null;
        try {
            await api.delete(`/api/v1/pos/terminals/${id}`);
            terminals.value = terminals.value.filter(t => t.id !== id);
            terminalsMeta.value.total = Math.max(0, (terminalsMeta.value.total || 0) - 1);
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to delete terminal.';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    // ── Sessions ─────────────────────────────────────────────────────────────

    async function fetchSessions(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/pos/sessions', { params: { page } });
            sessions.value = data.data ?? data;
            if (data.meta) sessionsMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load sessions.';
        } finally {
            loading.value = false;
        }
    }

    async function openSession(payload) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.post('/api/v1/pos/sessions', payload);
            const record = data.data ?? data;
            sessions.value.unshift(record);
            sessionsMeta.value.total = (sessionsMeta.value.total || 0) + 1;
            return record;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to open session.';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function closeSession(id, payload) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.post(`/api/v1/pos/sessions/${id}/close`, payload);
            const record = data.data ?? data;
            const idx = sessions.value.findIndex(s => s.id === id);
            if (idx !== -1) sessions.value[idx] = record;
            return record;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to close session.';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    // ── Orders ────────────────────────────────────────────────────────────────

    async function fetchOrders(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/pos/orders', { params: { page } });
            orders.value = data.data ?? data;
            if (data.meta) ordersMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load POS orders.';
        } finally {
            loading.value = false;
        }
    }

    async function placeOrder(payload) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.post('/api/v1/pos/orders', payload);
            const record = data.data ?? data;
            orders.value.unshift(record);
            ordersMeta.value.total = (ordersMeta.value.total || 0) + 1;
            return record;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to place order.';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function refundOrder(id) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.post(`/api/v1/pos/orders/${id}/refund`);
            const record = data.data ?? data;
            const idx = orders.value.findIndex(o => o.id === id);
            if (idx !== -1) orders.value[idx] = record;
            return record;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to refund order.';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    // ── Loyalty Programs ──────────────────────────────────────────────────────

    const loyaltyPrograms = ref([]);
    const loyaltyProgramsLoading = ref(false);
    const loyaltyProgramsMeta = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchLoyaltyPrograms(page = 1) {
        loyaltyProgramsLoading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/pos/loyalty-programs', { params: { page } });
            loyaltyPrograms.value = data.data ?? data;
            if (data.meta) loyaltyProgramsMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load loyalty programs.';
        } finally {
            loyaltyProgramsLoading.value = false;
        }
    }

    async function createLoyaltyProgram(payload) {
        loyaltyProgramsLoading.value = true;
        error.value = null;
        try {
            const { data } = await api.post('/api/v1/pos/loyalty-programs', payload);
            const record = data.data ?? data;
            loyaltyPrograms.value.unshift(record);
            loyaltyProgramsMeta.value.total = (loyaltyProgramsMeta.value.total || 0) + 1;
            return record;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to create loyalty program.';
            throw e;
        } finally {
            loyaltyProgramsLoading.value = false;
        }
    }

    async function updateLoyaltyProgram(id, payload) {
        loyaltyProgramsLoading.value = true;
        error.value = null;
        try {
            const { data } = await api.put(`/api/v1/pos/loyalty-programs/${id}`, payload);
            const record = data.data ?? data;
            const idx = loyaltyPrograms.value.findIndex(p => p.id === id);
            if (idx !== -1) loyaltyPrograms.value[idx] = record;
            return record;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to update loyalty program.';
            throw e;
        } finally {
            loyaltyProgramsLoading.value = false;
        }
    }

    async function deleteLoyaltyProgram(id) {
        loyaltyProgramsLoading.value = true;
        error.value = null;
        try {
            await api.delete(`/api/v1/pos/loyalty-programs/${id}`);
            loyaltyPrograms.value = loyaltyPrograms.value.filter(p => p.id !== id);
            loyaltyProgramsMeta.value.total = Math.max(0, (loyaltyProgramsMeta.value.total || 0) - 1);
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to delete loyalty program.';
            throw e;
        } finally {
            loyaltyProgramsLoading.value = false;
        }
    }

    // ── Loyalty Cards ─────────────────────────────────────────────────────────

    const loyaltyCards = ref([]);
    const loyaltyCardsLoading = ref(false);
    const loyaltyCardsMeta = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchLoyaltyCards(page = 1, filters = {}) {
        loyaltyCardsLoading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/pos/loyalty-cards', { params: { page, ...filters } });
            loyaltyCards.value = data.data ?? data;
            if (data.meta) loyaltyCardsMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load loyalty cards.';
        } finally {
            loyaltyCardsLoading.value = false;
        }
    }

    // ── Discounts ─────────────────────────────────────────────────────────────

    const discounts = ref([]);
    const discountsLoading = ref(false);
    const discountsMeta = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchDiscounts(page = 1, filters = {}) {
        discountsLoading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/pos/discounts', { params: { page, ...filters } });
            discounts.value = data.data ?? data;
            if (data.meta) discountsMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load discounts.';
        } finally {
            discountsLoading.value = false;
        }
    }

    async function createDiscount(payload) {
        discountsLoading.value = true;
        error.value = null;
        try {
            const { data } = await api.post('/api/v1/pos/discounts', payload);
            const record = data.data ?? data;
            discounts.value.unshift(record);
            discountsMeta.value.total = (discountsMeta.value.total || 0) + 1;
            return record;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to create discount.';
            throw e;
        } finally {
            discountsLoading.value = false;
        }
    }

    async function updateDiscount(id, payload) {
        discountsLoading.value = true;
        error.value = null;
        try {
            const { data } = await api.put(`/api/v1/pos/discounts/${id}`, payload);
            const record = data.data ?? data;
            const idx = discounts.value.findIndex(d => d.id === id);
            if (idx !== -1) discounts.value[idx] = record;
            return record;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to update discount.';
            throw e;
        } finally {
            discountsLoading.value = false;
        }
    }

    async function deleteDiscount(id) {
        discountsLoading.value = true;
        error.value = null;
        try {
            await api.delete(`/api/v1/pos/discounts/${id}`);
            discounts.value = discounts.value.filter(d => d.id !== id);
            discountsMeta.value.total = Math.max(0, (discountsMeta.value.total || 0) - 1);
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to delete discount.';
            throw e;
        } finally {
            discountsLoading.value = false;
        }
    }

    // ── Order Payments ────────────────────────────────────────────────────────

    const orderPayments = ref([]);
    const orderPaymentsLoading = ref(false);
    const orderPaymentsOrderId = ref(null);

    async function fetchOrderPayments(orderId) {
        orderPaymentsLoading.value = true;
        orderPaymentsOrderId.value = orderId;
        error.value = null;
        try {
            const { data } = await api.get(`/api/v1/pos/orders/${orderId}/payments`);
            orderPayments.value = data.data ?? data;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load order payments.';
        } finally {
            orderPaymentsLoading.value = false;
        }
    }

    // ── Product Catalog (for Terminal view) ───────────────────────────────────

    const catalogProducts = ref([]);
    const catalogLoading = ref(false);
    const catalogMeta = ref({ current_page: 1, last_page: 1, total: 0 });
    const catalogCategories = ref([]);
    const catalogCategoriesLoading = ref(false);
    const catalogVariants = ref([]);
    const catalogVariantsLoading = ref(false);
    const catalogLots = ref([]);
    const catalogLotsLoading = ref(false);

    async function fetchCatalogProducts(params = {}) {
        catalogLoading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/inventory/products', { params: { per_page: 50, status: 'active', ...params } });
            catalogProducts.value = data.data ?? data;
            if (data.meta) catalogMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load products.';
        } finally {
            catalogLoading.value = false;
        }
    }

    async function fetchCatalogCategories() {
        catalogCategoriesLoading.value = true;
        try {
            const { data } = await api.get('/api/v1/inventory/categories', { params: { per_page: 100 } });
            catalogCategories.value = data.data ?? data;
        } catch {
            // non-critical
        } finally {
            catalogCategoriesLoading.value = false;
        }
    }

    async function fetchProductVariants(productId) {
        catalogVariantsLoading.value = true;
        catalogVariants.value = [];
        try {
            const { data } = await api.get('/api/v1/inventory/variants', { params: { product_id: productId, per_page: 100 } });
            catalogVariants.value = data.data ?? data;
        } catch {
            // non-critical
        } finally {
            catalogVariantsLoading.value = false;
        }
    }

    async function fetchProductLots(productId) {
        catalogLotsLoading.value = true;
        catalogLots.value = [];
        try {
            const { data } = await api.get('/api/v1/inventory/lots', { params: { product_id: productId, status: 'active', per_page: 100 } });
            catalogLots.value = data.data ?? data;
        } catch {
            // non-critical
        } finally {
            catalogLotsLoading.value = false;
        }
    }

    // ── Discount Validation ───────────────────────────────────────────────────

    const discountValidation = ref(null);
    const discountValidationLoading = ref(false);

    async function validateDiscountCode(code, subtotal) {
        discountValidationLoading.value = true;
        discountValidation.value = null;
        error.value = null;
        try {
            const { data } = await api.post(`/api/v1/pos/discounts/${encodeURIComponent(code)}/validate`, { subtotal });
            discountValidation.value = data.data ?? data;
            return discountValidation.value;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Invalid discount code.';
            throw e;
        } finally {
            discountValidationLoading.value = false;
        }
    }

    function clearDiscountValidation() {
        discountValidation.value = null;
    }

    // ── Loyalty Points Accrual / Redemption ───────────────────────────────────

    const loyaltyCardsActionLoading = ref(false);

    async function accruePoints(payload) {
        loyaltyCardsActionLoading.value = true;
        error.value = null;
        try {
            const { data } = await api.post('/api/v1/pos/loyalty-cards/accrue', payload);
            const record = data.data ?? data;
            // Update card in list if present
            const idx = loyaltyCards.value.findIndex(c => c.id === record.id);
            if (idx !== -1) loyaltyCards.value[idx] = record;
            return record;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to accrue loyalty points.';
            throw e;
        } finally {
            loyaltyCardsActionLoading.value = false;
        }
    }

    async function redeemPoints(cardId, payload) {
        loyaltyCardsActionLoading.value = true;
        error.value = null;
        try {
            const { data } = await api.post(`/api/v1/pos/loyalty-cards/${cardId}/redeem`, payload);
            const record = data.data ?? data;
            // Update card in list if present
            const idx = loyaltyCards.value.findIndex(c => c.id === cardId);
            if (idx !== -1) loyaltyCards.value[idx] = record;
            return record;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to redeem loyalty points.';
            throw e;
        } finally {
            loyaltyCardsActionLoading.value = false;
        }
    }

    return {
        // Terminals
        terminals, loading, error, terminalsMeta,
        fetchTerminals, createTerminal, updateTerminal, deleteTerminal,
        // Sessions
        sessions, sessionsMeta,
        fetchSessions, openSession, closeSession,
        // Orders
        orders, ordersMeta,
        fetchOrders, placeOrder, refundOrder,
        // Loyalty programs
        loyaltyPrograms, loyaltyProgramsLoading, loyaltyProgramsMeta,
        fetchLoyaltyPrograms, createLoyaltyProgram, updateLoyaltyProgram, deleteLoyaltyProgram,
        // Loyalty cards
        loyaltyCards, loyaltyCardsLoading, loyaltyCardsMeta,
        fetchLoyaltyCards,
        loyaltyCardsActionLoading, accruePoints, redeemPoints,
        // Discounts
        discounts, discountsLoading, discountsMeta,
        fetchDiscounts, createDiscount, updateDiscount, deleteDiscount,
        discountValidation, discountValidationLoading,
        validateDiscountCode, clearDiscountValidation,
        // Order payments
        orderPayments, orderPaymentsLoading, orderPaymentsOrderId,
        fetchOrderPayments,
        // Product catalog (terminal view)
        catalogProducts, catalogLoading, catalogMeta,
        catalogCategories, catalogCategoriesLoading,
        catalogVariants, catalogVariantsLoading,
        catalogLots, catalogLotsLoading,
        fetchCatalogProducts, fetchCatalogCategories,
        fetchProductVariants, fetchProductLots,
    };
});
