import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'

import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap-icons/font/bootstrap-icons.css'
import 'bootstrap/dist/js/bootstrap.bundle.min.js'

import './assets/globals.css'

import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

// Corrige le path des images Leaflet en prod
delete L.Icon.Default.prototype._getIconUrl
L.Icon.Default.mergeOptions({
  iconRetinaUrl: '/images/leaflet/marker-icon-2x.png',
  iconUrl: '/images/leaflet/marker-icon.png',
  shadowUrl: '/images/leaflet/marker-shadow.png',
})

// PrimeVue setup
import PrimeVue from 'primevue/config'
import 'primeicons/primeicons.css'
import Aura from '@primeuix/themes/aura'

// Importation individuelle des composants PrimeVue
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Message from 'primevue/message'
import Tag from 'primevue/tag'
import DatePicker from 'primevue/datepicker' // aliasé plus bas en DatePicker
import MultiSelect from 'primevue/multiselect'
import InputNumber from 'primevue/inputnumber'
import Checkbox from 'primevue/checkbox'
import Select from 'primevue/select' // aliasé plus bas en Select
import InputGroup from 'primevue/inputgroup'
import InputText from 'primevue/inputtext'
import Card from 'primevue/card'
import Badge from 'primevue/badge'
import Avatar from 'primevue/avatar'
import ProgressBar from 'primevue/progressbar'
import Paginator from 'primevue/paginator'
import Toast from 'primevue/toast'
import ToastService from 'primevue/toastservice'
import ProgressSpinner from 'primevue/progressspinner'
import Rating from 'primevue/rating'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Textarea from 'primevue/textarea'
import Tooltip from 'primevue/tooltip'
import AutoComplete from 'primevue/autocomplete'

// Enregistrement automatique des composants
const components = {
  DataTable,
  Column,
  Button,
  Dialog,
  Message,
  Tag,
  DatePicker, // alias pour cohérence avec ton code
  MultiSelect,
  InputNumber,
  Checkbox,
  Select, // alias également
  InputGroup,
  InputText,
  Card,
  Badge,
  Avatar,
  ProgressBar,
  Paginator,
  Toast,
  ProgressSpinner,
  Rating,
  IconField,
  InputIcon,
  Textarea,
  AutoComplete,
}

;(async () => {
  const app = createApp(App)
  const pinia = createPinia()
  app.use(pinia)

  const { useAuthStore } = await import('./stores/auth')
  const authStore = useAuthStore()

  router.beforeEach(async (to, from, next) => {
    const requiresAuth = to.meta.requiresAuth
    const requiresGuest = to.meta.requiresGuest
    const requiresAdmin = to.meta.requiresAdmin

    if (requiresAuth && !authStore.isAuthenticated) {
      return next({ path: '/login', query: { redirect: to.fullPath } })
    }

    if (requiresGuest && authStore.isAuthenticated) {
      return next('/')
    }

    if (requiresAdmin && (!authStore.isAuthenticated || !authStore.user?.is_admin)) {
      return next('/')
    }

    next()
  })

  try {
    await authStore.initializeAuth()
  } catch (error) {
    console.warn('Auth initialization failed:', error)
  }

  app.use(router)
  app.use(PrimeVue, {
    theme: {
      preset: Aura,
      options: {
        darkModeSelector: 'never', // Désactive complètement le dark mode
      },
    },
  })
  app.use(ToastService)
  Object.entries(components).forEach(([name, component]) => {
    app.component(name, component)
  })
  app.directive('tooltip', Tooltip)
  app.mount('#app')
})()
