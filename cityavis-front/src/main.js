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

import PrimeVue from 'primevue/config';
import 'primeicons/primeicons.css'
import Aura from '@primeuix/themes/aura'

import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Message from 'primevue/message'
import Tag from 'primevue/tag'
import DatePicker from 'primevue/datepicker'
import MultiSelect from 'primevue/multiselect'
import InputNumber from 'primevue/inputnumber'
import Checkbox from 'primevue/checkbox'
import Select from 'primevue/select'

(async () => {
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
      return next({
        path: '/login',
        query: { redirect: to.fullPath }
      })
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
      preset: Aura
    }
  })
  app.component('DataTable', DataTable)
  app.component('Column', Column)
  app.component('Button', Button)
  app.component('Dialog', Dialog)
  app.component('Message', Message)
  app.component('Tag', Tag)
  app.component('DatePicker',DatePicker)
  app.component('MultiSelect',MultiSelect)
  app.component('InputNumber',InputNumber)
  app.component('Checkbox',Checkbox)
  app.component('Select',Select)
  app.mount('#app')
})()
