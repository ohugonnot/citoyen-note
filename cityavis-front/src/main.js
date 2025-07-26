import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'

import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap-icons/font/bootstrap-icons.css'
import 'bootstrap/dist/js/bootstrap.bundle.min.js'

import './assets/globals.css'

const app = createApp(App)
const pinia = createPinia()

import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

// corrige le path des images Leaflet en prod
delete L.Icon.Default.prototype._getIconUrl

L.Icon.Default.mergeOptions({
  iconRetinaUrl: '/images/leaflet/marker-icon-2x.png',
  iconUrl: '/images/leaflet/marker-icon.png',
  shadowUrl: '/images/leaflet/marker-shadow.png',
})

app.use(pinia)
app.use(router)

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

app.mount('#app')
