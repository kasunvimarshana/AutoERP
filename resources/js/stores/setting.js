import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/composables/useApi';

export const useSettingStore = defineStore('setting', () => {
    const settings = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const saving = ref(false);
    const saveError = ref(null);

    async function fetchSettings() {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/settings');
            settings.value = data.data ?? data;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load settings.';
        } finally {
            loading.value = false;
        }
    }

    async function updateSetting(key, value) {
        saving.value = true;
        saveError.value = null;
        try {
            await api.put(`/api/v1/settings/${key}`, { value });
            const idx = settings.value.findIndex(s => s.key === key);
            if (idx !== -1) settings.value[idx].value = value;
        } catch (e) {
            saveError.value = e.response?.data?.message ?? 'Failed to save setting.';
            throw e;
        } finally {
            saving.value = false;
        }
    }

    return { settings, loading, error, saving, saveError, fetchSettings, updateSetting };
});
