<template>
  <header class="navbar navbar-expand-lg bg-white shadow-sm fixed-top" role="banner">
    <div class="container-fluid">
      <div class="container d-flex justify-content-between align-items-center">
        <router-link
          to="/"
          class="navbar-brand d-flex align-items-center gap-2"
          :class="{ 'mobile-brand': isMobile }"
        >
          <img src="/logo.png" alt="CityAvis" height="75" class="logo" />
        </router-link>

        <nav v-if="!auth.isAuthenticated" class="d-flex align-items-center gap-2">
          <button
            class="btn btn-outline-primary d-sm-inline-flex align-items-center gap-2"
            @click="goRegister"
          >
            <i class="bi bi-person-plus"></i>
            <span class="d-none d-lg-inline">S'enregistrer</span>
          </button>
          <button class="btn btn-primary d-flex align-items-center gap-2" @click="goLogin">
            <i class="bi bi-box-arrow-in-right"></i>
            <span class="d-none d-lg-inline">Connexion</span>
          </button>
        </nav>

        <div v-else class="user-menu">
          <div class="dropdown">
            <button
              id="userDropdown"
              class="btn btn-light dropdown-toggle user-btn"
              type="button"
              data-bs-toggle="dropdown"
              aria-expanded="false"
            >
              <div class="user-avatar">
                {{ userInitials }}
                <div v-if="auth.user?.is_verified" class="user-status"></div>
              </div>
              <div class="user-info d-none d-md-block">
                <div class="user-name">{{ displayName }}</div>
                <div class="user-role">{{ userRole }}</div>
              </div>
              <!-- SUPPRIMÉ : <i class="bi bi-chevron-down ms-1"></i> -->
            </button>

            <ul class="dropdown-menu dropdown-menu-end">
              <li class="dropdown-header">
                <div class="d-flex align-items-center gap-3">
                  <div class="user-avatar-lg">
                    {{ userInitials }}
                    <div v-if="auth.user?.is_verified" class="user-status"></div>
                  </div>
                  <div>
                    <div class="fw-semibold">{{ displayName }}</div>
                    <small class="text-muted">{{ auth.user?.email }}</small>
                    <div class="user-badge">{{ userRole }}</div>
                  </div>
                </div>
              </li>
              <li><hr class="dropdown-divider" /></li>

              <li>
                <router-link class="dropdown-item d-flex align-items-center gap-2" to="/profile">
                  <i class="bi bi-person"></i>
                  Mon profil
                </router-link>
              </li>

              <li>
                <router-link
                  class="dropdown-item d-flex align-items-center gap-2"
                  to="/mes-evaluations"
                >
                  <i class="bi bi-star"></i>
                  Mes évaluations
                </router-link>
              </li>

              <li>
                <router-link class="dropdown-item d-flex align-items-center gap-2" to="/settings">
                  <i class="bi bi-gear"></i>
                  Paramètres
                </router-link>
              </li>

              <template v-if="isAdmin">
                <li><hr class="dropdown-divider" /></li>
                <li>
                  <h6 class="dropdown-header admin-header">
                    <i class="bi bi-shield-check me-1"></i>
                    Administration
                  </h6>
                </li>
                <li>
                  <router-link
                    class="dropdown-item d-flex align-items-center gap-2 pl-4"
                    to="/admin/users"
                  >
                    <i class="bi bi-people"></i>
                    Gestion utilisateurs
                  </router-link>
                </li>
                <li>
                  <router-link
                    class="dropdown-item d-flex align-items-center gap-2 pl-4"
                    to="/admin/services-publiques"
                  >
                    <i class="bi bi-building"></i>
                    Gestion services
                  </router-link>
                </li>
                <li>
                  <router-link
                    class="dropdown-item d-flex align-items-center gap-2 pl-4"
                    to="/admin/analytics"
                  >
                    <i class="bi bi-graph-up"></i>
                    Statistiques
                  </router-link>
                </li>
              </template>

              <li><hr class="dropdown-divider" /></li>
              <li>
                <button
                  class="dropdown-item d-flex align-items-center gap-2 text-danger"
                  :disabled="isLoggingOut"
                  @click="logout"
                >
                  <span v-if="isLoggingOut">Déconnexion...</span>
                  <span v-else>Déconnexion</span>
                </button>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()
const isLoggingOut = ref(false)
const isMobile = ref(window.innerWidth < 768)

const handleResize = () => {
  isMobile.value = window.innerWidth < 768
}

onMounted(() => {
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})

const userInitials = computed(() => {
  const user = auth.user
  if (!user) return '?'

  if (user.name) {
    return user.name
      .split(' ')
      .map((n) => n[0])
      .join('')
      .toUpperCase()
      .slice(0, 2)
  }

  return user.email?.[0]?.toUpperCase() || '?'
})

const displayName = computed(() => {
  return auth.user?.pseudo || auth.user?.email?.split('@')[0] || 'Citoyen'
})

const userRole = computed(() => {
  if (auth.user?.roles.includes('admin')) return 'Administrateur'
  if (auth.user?.roles.includes('moderator')) return 'Modérateur'
  return 'Citoyen'
})

const isAdmin = computed(() => {
  return auth.user?.roles.includes('ROLE_USER') || auth.user?.is_admin
})

const goLogin = () => router.push('/login')
const goRegister = () => router.push('/register')

const logout = async () => {
  isLoggingOut.value = true
  try {
    await auth.logout('/login')
  } finally {
    isLoggingOut.value = false
  }
}
</script>

<style scoped>
header {
  z-index: var(--z-fixed);
  border-bottom: 3px solid var(--color-primary);
  min-height: 70px;
}

.navbar-brand {
  font-size: 1.5rem;
  color: var(--color-primary) !important;
  text-decoration: none;
  transition: var(--transition-fast);
}

.navbar-brand:hover {
  transform: scale(1.02);
}

.brand-icon {
  font-size: 1.8rem;
}

.brand-text {
  font-size: 1.5rem;
  letter-spacing: -0.02em;
}

.brand-subtitle {
  font-size: 0.7rem;
  color: var(--color-gray);
  font-weight: 400;
  text-transform: uppercase;
  letter-spacing: 0.1em;
}

.mobile-brand .brand-text {
  font-size: 1.2rem;
}

.user-btn {
  border: 1px solid var(--color-border);
  background: white;
  padding: 0.5rem 1rem;
  border-radius: var(--border-radius);
  transition: var(--transition);
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.user-btn:hover {
  border-color: var(--color-primary);
  box-shadow: var(--shadow-sm);
}

.user-avatar {
  width: 32px;
  height: 32px;
  background: linear-gradient(135deg, var(--color-primary), var(--color-primary-light));
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 600;
  position: relative;
}

.user-avatar-lg {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, var(--color-primary), var(--color-primary-light));
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
  font-weight: 600;
  position: relative;
}

.user-status {
  position: absolute;
  bottom: -2px;
  right: -2px;
  width: 12px;
  height: 12px;
  background: var(--color-accent);
  border: 2px solid white;
  border-radius: 50%;
}

.user-info {
  text-align: left;
}

.user-name {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-text);
  line-height: 1.2;
}

.user-role {
  font-size: 0.75rem;
  color: var(--color-gray);
  line-height: 1.2;
}

.user-badge {
  background: var(--color-gray-light);
  color: var(--color-primary);
  font-size: 0.65rem;
  font-weight: 500;
  border-radius: 12px;
  margin-top: 0.25rem;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.dropdown-menu {
  min-width: 280px;
  border: 0;
  box-shadow: var(--shadow-lg);
  border-radius: var(--border-radius-lg);
  padding: 0.5rem 0;
  margin-top: 0.5rem;
}

.dropdown-item {
  padding: 0.25rem 1.25rem;
  border-radius: 0;
  transition: var(--transition-fast);
  font-size: 0.875rem;
}

.dropdown-item:hover {
  background-color: var(--color-gray-light);
  color: var(--color-primary);
  transform: translateX(4px);
}

.dropdown-item.text-danger:hover {
  background-color: rgba(225, 0, 15, 0.1);
  color: var(--color-secondary) !important;
}

.dropdown-header {
  padding: 1rem 1.25rem 0.75rem;
  font-size: 0.875rem;
  border: 0;
}

.admin-header {
  color: var(--color-primary);
  font-weight: 600;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 0.5rem 1.25rem;
}

.dropdown-divider {
  margin: 0.5rem 0;
  border-color: var(--color-border);
}

@media (max-width: 576px) {
  .dropdown-menu {
    min-width: 250px;
    margin-top: 0.25rem;
  }

  .user-avatar {
    width: 28px;
    height: 28px;
    font-size: 0.7rem;
  }

  .dropdown-item {
    padding: 0.65rem 1rem;
  }

  .dropdown-header {
    padding: 0.75rem 1rem 0.5rem;
  }
}

.pl-4 {
  padding-left: 2.5rem;
}

header {
  z-index: var(--z-fixed);
  border-bottom: 3px solid var(--color-primary);
  min-height: 70px;
}

.navbar-brand {
  font-size: 1.5rem;
  color: var(--color-primary) !important;
  text-decoration: none;
  transition: var(--transition-fast);
}

.navbar-brand:hover {
  transform: scale(1.02);
}

.brand-icon {
  font-size: 1.8rem;
}

.brand-text {
  font-size: 1.5rem;
  letter-spacing: -0.02em;
}

.brand-subtitle {
  font-size: 0.7rem;
  color: var(--color-gray);
  font-weight: 400;
  text-transform: uppercase;
  letter-spacing: 0.1em;
}

.mobile-brand .brand-text {
  font-size: 1.2rem;
}

.user-btn {
  border: 1px solid var(--color-border);
  background: white;
  padding: 0.5rem 1rem;
  border-radius: var(--border-radius);
  transition: var(--transition);
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.user-btn:hover {
  border-color: var(--color-primary);
  box-shadow: var(--shadow-sm);
}

.user-btn:hover .user-avatar {
  transform: scale(1.1);
  box-shadow: 0 4px 12px rgba(var(--color-primary-rgb), 0.3);
}

.user-btn:hover .user-info {
  transform: translateX(2px);
}

.user-avatar {
  width: 32px;
  height: 32px;
  background: linear-gradient(135deg, var(--color-primary), var(--color-primary-light));
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 600;
  position: relative;
  transition: all 0.3s ease;
}

.user-avatar-lg {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, var(--color-primary), var(--color-primary-light));
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
  font-weight: 600;
  position: relative;
  transition: transform 0.3s ease;
}

.user-status {
  position: absolute;
  bottom: -2px;
  right: -2px;
  width: 12px;
  height: 12px;
  background: var(--color-accent);
  border: 2px solid white;
  border-radius: 50%;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%,
  100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.1);
  }
}

.user-info {
  text-align: left;
  transition: transform 0.3s ease;
}

.user-name {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-text);
  line-height: 1.2;
}

.user-role {
  font-size: 0.75rem;
  color: var(--color-gray);
  line-height: 1.2;
}

.user-badge {
  background: var(--color-gray-light);
  color: var(--color-primary);
  font-size: 0.65rem;
  font-weight: 500;
  border-radius: 12px;
  margin-top: 0.25rem;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.dropdown-menu {
  min-width: 280px;
  border: 0;
  box-shadow: var(--shadow-lg);
  border-radius: var(--border-radius-lg);
  padding: 0.5rem 0;
  margin-top: 0.5rem;
  animation: dropdownFadeIn 0.2s ease-out;
  transform-origin: top right;
}

@keyframes dropdownFadeIn {
  0% {
    opacity: 0;
    transform: translateY(-10px) scale(0.95);
  }
  100% {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.dropdown-item {
  padding: 0.75rem 1.25rem;
  border-radius: 0;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  font-size: 0.875rem;
  position: relative;
  overflow: hidden;
}

.dropdown-item::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 0;
  height: 0;
  border-radius: 50%;
  background: rgba(var(--color-primary-rgb), 0.2);
  transition:
    width 0.4s,
    height 0.4s,
    top 0.4s,
    left 0.4s;
  transform: translate(-50%, -50%);
  z-index: 0;
}

.dropdown-item:active::before {
  width: 300px;
  height: 300px;
}

.dropdown-item * {
  position: relative;
  z-index: 1;
}

.dropdown-item:hover {
  background: linear-gradient(90deg, var(--color-gray-light), rgba(var(--color-primary-rgb), 0.1));
  color: var(--color-primary);
  transform: translateX(8px);
  padding-left: 1.5rem;
  box-shadow: inset 3px 0 0 var(--color-primary);
}

.dropdown-item:hover i {
  transform: scale(1.1) rotate(5deg);
  color: var(--color-primary);
  transition: all 0.3s ease;
}

.dropdown-item.text-danger:hover {
  background: linear-gradient(90deg, rgba(220, 53, 69, 0.1), rgba(220, 53, 69, 0.05));
  color: var(--color-secondary) !important;
  box-shadow: inset 3px 0 0 var(--color-secondary);
}

.dropdown-item.text-danger:hover i {
  transform: scale(1.1) translateX(2px);
  color: var(--color-secondary);
}

.dropdown-header {
  padding: 1rem 1.25rem 0.75rem;
  font-size: 0.875rem;
  border: 0;
}

.dropdown-header:hover .user-avatar-lg {
  transform: scale(1.05);
}

.admin-header {
  color: var(--color-primary);
  font-weight: 600;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 0.5rem 1.25rem;
  transition: all 0.3s ease;
}

.admin-header:hover {
  color: var(--color-primary);
  transform: scale(1.02);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.dropdown-divider {
  margin: 0.5rem 0;
  border-color: var(--color-border);
}

.pl-4 {
  padding-left: 2.5rem;
  transition: all 0.3s ease;
}

.pl-4:hover {
  background: linear-gradient(90deg, rgba(var(--color-primary-rgb), 0.08), transparent);
  border-left: 3px solid var(--color-primary);
  padding-left: 2.25rem;
}

@media (max-width: 576px) {
  .dropdown-menu {
    min-width: 250px;
    margin-top: 0.25rem;
    animation: dropdownSlideIn 0.25s ease-out;
  }

  @keyframes dropdownSlideIn {
    0% {
      opacity: 0;
      transform: translateY(-15px);
    }
    100% {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .user-avatar {
    width: 28px;
    height: 28px;
    font-size: 0.7rem;
  }

  .dropdown-item {
    padding: 0.65rem 1rem;
  }

  .dropdown-item:hover {
    transform: translateX(4px);
    padding-left: 1.25rem;
  }

  .dropdown-header {
    padding: 0.75rem 1rem 0.5rem;
  }
}
</style>
