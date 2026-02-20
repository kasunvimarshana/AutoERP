import js from '@eslint/js';
import ts from '@typescript-eslint/eslint-plugin';
import tsParser from '@typescript-eslint/parser';
import vue from 'eslint-plugin-vue';
import vueParser from 'vue-eslint-parser';
import prettier from 'eslint-config-prettier';
import globals from 'globals';

const browserGlobals = {
  globals: {
    ...globals.browser,
    ...globals.es2021,
  },
};

export default [
  js.configs.recommended,
  ...vue.configs['flat/recommended'],
  // Plain JS files in resources/js
  {
    files: ['resources/js/**/*.js'],
    languageOptions: browserGlobals,
  },
  // TypeScript + Vue files
  {
    files: ['resources/js/**/*.{ts,tsx,vue}'],
    languageOptions: {
      parser: vueParser,
      parserOptions: {
        parser: tsParser,
        ecmaVersion: 'latest',
        sourceType: 'module',
        extraFileExtensions: ['.vue'],
      },
      ...browserGlobals,
    },
    plugins: {
      '@typescript-eslint': ts,
    },
    rules: {
      ...ts.configs['recommended'].rules,
      'vue/multi-word-component-names': 'off',
      'vue/require-default-prop': 'off',
      'vue/attributes-order': 'warn',
      '@typescript-eslint/no-explicit-any': 'warn',
      '@typescript-eslint/no-unused-vars': ['warn', { argsIgnorePattern: '^_' }],
      'no-console': ['warn', { allow: ['warn', 'error'] }],
    },
  },
  prettier,
  {
    ignores: ['node_modules/**', 'vendor/**', 'public/build/**', 'bootstrap/cache/**'],
  },
];
