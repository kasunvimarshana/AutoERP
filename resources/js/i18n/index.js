import { createI18n } from 'vue-i18n';
import { STORAGE_KEYS } from '@/config/constants';
import en from './locales/en';
import es from './locales/es';
import fr from './locales/fr';

const messages = {
  en,
  es,
  fr,
};

const i18n = createI18n({
  legacy: false,
  locale: localStorage.getItem(STORAGE_KEYS.LOCALE) || 'en',
  fallbackLocale: 'en',
  messages,
});

export default i18n;
