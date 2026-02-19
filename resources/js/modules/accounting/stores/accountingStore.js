import { defineStore } from 'pinia';
import { ref } from 'vue';
import { accountingService } from '../services/accountingService';

/**
 * Accounting Store
 * 
 * Manages Accounting module state (accounts, journal entries, reports)
 */
export const useAccountingStore = defineStore('accounting', () => {
    // State
    const accounts = ref([]);
    const journalEntries = ref([]);
    const loading = ref(false);
    const error = ref(null);

    // Actions - Accounts
    async function fetchAccounts(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await accountingService.accounts.getAll(params);
            accounts.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function createAccount(data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await accountingService.accounts.create(data);
            accounts.value.push(response.data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function updateAccount(id, data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await accountingService.accounts.update(id, data);
            const index = accounts.value.findIndex(a => a.id === id);
            if (index !== -1) {
                accounts.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function deleteAccount(id) {
        loading.value = true;
        error.value = null;
        try {
            await accountingService.accounts.delete(id);
            accounts.value = accounts.value.filter(a => a.id !== id);
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function activateAccount(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await accountingService.accounts.activate(id);
            const index = accounts.value.findIndex(a => a.id === id);
            if (index !== -1) {
                accounts.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function deactivateAccount(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await accountingService.accounts.deactivate(id);
            const index = accounts.value.findIndex(a => a.id === id);
            if (index !== -1) {
                accounts.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function fetchAccountBalance(id, params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await accountingService.accounts.getBalance(id, params);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    // Actions - Journal Entries
    async function fetchJournalEntries(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await accountingService.journalEntries.getAll(params);
            journalEntries.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function createJournalEntry(data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await accountingService.journalEntries.create(data);
            journalEntries.value.push(response.data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function updateJournalEntry(id, data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await accountingService.journalEntries.update(id, data);
            const index = journalEntries.value.findIndex(je => je.id === id);
            if (index !== -1) {
                journalEntries.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function deleteJournalEntry(id) {
        loading.value = true;
        error.value = null;
        try {
            await accountingService.journalEntries.delete(id);
            journalEntries.value = journalEntries.value.filter(je => je.id !== id);
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function postJournalEntry(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await accountingService.journalEntries.post(id);
            const index = journalEntries.value.findIndex(je => je.id === id);
            if (index !== -1) {
                journalEntries.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function reverseJournalEntry(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await accountingService.journalEntries.reverse(id);
            journalEntries.value.push(response.data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    // Actions - Reports
    async function fetchBalanceSheet(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await accountingService.reports.getBalanceSheet(params);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function fetchIncomeStatement(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await accountingService.reports.getIncomeStatement(params);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function fetchCashFlow(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await accountingService.reports.getCashFlow(params);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function fetchTrialBalance(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await accountingService.reports.getTrialBalance(params);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function fetchGeneralLedger(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await accountingService.reports.getGeneralLedger(params);
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
        accounts,
        journalEntries,
        loading,
        error,

        // Actions - Accounts
        fetchAccounts,
        createAccount,
        updateAccount,
        deleteAccount,
        activateAccount,
        deactivateAccount,
        fetchAccountBalance,

        // Actions - Journal Entries
        fetchJournalEntries,
        createJournalEntry,
        updateJournalEntry,
        deleteJournalEntry,
        postJournalEntry,
        reverseJournalEntry,

        // Actions - Reports
        fetchBalanceSheet,
        fetchIncomeStatement,
        fetchCashFlow,
        fetchTrialBalance,
        fetchGeneralLedger,
    };
});
