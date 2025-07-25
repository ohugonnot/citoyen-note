import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

// Import views
import HomeView from '@/views/HomeView.vue'
import LoginView from '@/views/LoginView.vue'
import RegisterView from '@/views/RegisterView.vue'

const routes = [
  {
    path: '/',
    name: 'home',
    component: HomeView,
    meta: { requiresAuth: true },
  },
  {
    path: '/me',
    name: 'me',
    component: () => import('@/views/MeView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/users',
    name: 'users',
    component: () => import('@/views/UsersView.vue'),
    meta: { requiresAuth: true, requiresRole: 'ROLE_USER' },
  },
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { requiresGuest: true },
  },
  {
    path: '/register',
    name: 'register',
    component: RegisterView,
    meta: { requiresGuest: true },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to, from, next) => {
  const auth = useAuthStore()

  // Si la session est active mais user pas chargé, on fetch
  if (auth.isAuthenticated && !auth.user) {
    try {
      await auth.fetchUser()
    } catch {
      auth.logout()
      return next({ name: 'login' })
    }
  }

  // Auth requise ?
  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return next({ name: 'login' })
  }

  // Vérification rôle uniquement si user chargé
  if (to.meta.requiresRole) {
    if (!auth.user || !auth.user.roles.includes(to.meta.requiresRole)) {
      return next({ name: 'home' }) // ou page 403 personnalisée
    }
  }

  // Routes publiques si connecté ?
  if (to.meta.requiresGuest && auth.isAuthenticated) {
    return next({ name: 'home' })
  }

  next()
})

export default router
