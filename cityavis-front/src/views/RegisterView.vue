<template>
  <div class="register container py-4" style="max-width: 400px">
    <h1 class="mb-4 text-primary fw-bold text-center">Créer un compte</h1>

    <form @submit.prevent="handleRegister" novalidate>
      <div class="mb-3">
        <input
          v-model="email"
          type="email"
          class="form-control"
          placeholder="Email"
          required
          autocomplete="email"
          :disabled="auth.isAuthLoading"
        />
      </div>

      <div class="mb-3">
        <input
          v-model="password"
          type="password"
          class="form-control"
          placeholder="Mot de passe"
          required
          autocomplete="new-password"
          :disabled="auth.isAuthLoading"
        />
      </div>

      <div class="mb-3">
        <input
          v-model="confirmPassword"
          type="password"
          class="form-control"
          placeholder="Confirmer le mot de passe"
          required
          :disabled="auth.isAuthLoading"
        />
      </div>

      <button type="submit" class="btn btn-primary w-100" :disabled="auth.isAuthLoading">
        <span v-if="auth.isAuthLoading">
          <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
          Inscription...
        </span>
        <span v-else>S’inscrire</span>
      </button>

      <!-- Erreur de validation locale (mots de passe non identiques) -->
      <div v-if="localError" class="alert alert-danger mt-3" role="alert" aria-live="assertive">
        {{ localError }}
      </div>

      <!-- Erreur provenant de l'API via le store -->
      <div v-if="auth.lastLoginError" class="alert alert-danger mt-3" role="alert" aria-live="assertive">
        {{ auth.lastLoginError }}
      </div>

      <div v-if="success" class="alert alert-success mt-3" role="alert" aria-live="polite">
        {{ success }}
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from '@/axios'
import { useAuthStore } from '@/stores/auth'

const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const localError = ref(null)
const success = ref(null)

const auth = useAuthStore()

const handleRegister = async () => {
  localError.value = null
  success.value = null
  auth.clearLoginError()

  if (password.value !== confirmPassword.value) {
    localError.value = 'Les mots de passe ne correspondent pas.'
    return
  }

  try {
    // Appel à l'API de création de compte
    await axios.post('/api/register', {
      email: email.value,
      password: password.value,
    })

    // Connexion automatique
    const loggedIn = await auth.login({ email: email.value, password: password.value })

    if (loggedIn) {
      success.value = 'Inscription réussie ! Bienvenue.'
      // Optionnel : redirection automatique
      // router.push('/')
    }
  } catch (err) {
    // Gestion fallback d'erreur hors store (ex: API register)
    const data = err?.response?.data
    if (typeof data?.error === 'string') {
      localError.value = data.error
    } else if (typeof data?.message === 'string') {
      localError.value = data.message
    } else {
      localError.value = 'Erreur lors de l’inscription.'
    }
    console.error('[Register] Erreur API:', err)
  }
}
</script>
