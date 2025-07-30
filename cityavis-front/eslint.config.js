// eslint.config.js
import { defineConfig, globalIgnores } from 'eslint/config'
import js from '@eslint/js'
import vue from 'eslint-plugin-vue'
import globals from 'globals'
import skipFormatting from '@vue/eslint-config-prettier/skip-formatting'

export default defineConfig([
  // 📁 Cible les fichiers à analyser
  {
    name: 'app/files-to-lint',
    files: ['**/*.{js,jsx,vue}'],
  },

  // 📁 Ignore les dossiers de build/test
  globalIgnores(['**/node_modules/**', '**/dist*/**', '**/coverage/**']),

  // 🌐 Configuration du contexte JS (navigateur + ES2021)
  {
    languageOptions: {
      ecmaVersion: 'latest',
      sourceType: 'module',
      globals: {
        ...globals.browser,
        ...globals.es2021,
      },
    },
  },

  // 🔍 Règles JS standard
  js.configs.recommended,

  // 🔍 Règles Vue 3 essentielles (Flat config)
  ...vue.configs['flat/recommended'],

  // 🚫 Désactive les conflits de Prettier (évite les conflits de format)
  skipFormatting,

  // 🔧 Règles personnalisées
  {
    rules: {
      'vue/no-v-model-argument': 'off', // Autorise `v-model:xxx`
      'vue/multi-word-component-names': 'off', // Autorise `<Home.vue>` etc.
      'vue/require-default-prop': 'off', // Moins strict avec props
      'vue/no-mutating-props': 'warn', // Alerte si props modifiées
      'no-console': 'warn',
      'no-unused-vars': ['warn', { argsIgnorePattern: '^_' }],
    },
  },
])
