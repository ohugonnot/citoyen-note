<template>
  <header
    class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4 py-3 fixed-top"
    role="banner"
  >
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <!-- Logo + titre -->
      <a href="#" class="navbar-brand d-flex align-items-center gap-2" style="font-size: 1.5rem">
        <span>🏛️</span>
        <span class="fw-bold text-primary fs-4">CitoyenNote</span>
      </a>

      <!-- Boutons / zone utilisateur -->
      <div class="d-flex align-items-center gap-3">
        <button v-if="!auth.isAuthenticated" class="btn btn-outline-danger" @click="goRegister">
          S'enregistrer
        </button>

        <button v-if="!auth.isAuthenticated" class="btn btn-outline-primary" @click="goLogin">
          Se connecter
        </button>

        <div v-else class="d-flex align-items-center gap-3">
          <span class="text-secondary">Bonjour, {{ auth.user?.email }}</span>
          <button class="btn btn-danger btn-sm" @click="logout">Déconnexion</button>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const goLogin = () => {
  router.push('/login')
}
const goRegister = () => {
  router.push('/register')
}
const logout = () => {
  auth.logout()
  router.push('/login')
}
</script>

<style scoped>
header {
  z-index: 1030; /* au-dessus du contenu */
}
body {
  padding-top: 80px; /* pour éviter que le contenu soit caché sous le header fixe */
}
</style>
