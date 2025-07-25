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
          :disabled="loading"
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
          :disabled="loading"
        />
      </div>

      <div class="mb-3">
        <input
          v-model="confirmPassword"
          type="password"
          class="form-control"
          placeholder="Confirmer le mot de passe"
          required
          :disabled="loading"
        />
      </div>

      <button type="submit" class="btn btn-primary w-100" :disabled="loading">
        <span v-if="loading">
          <span
            class="spinner-border spinner-border-sm me-2"
            role="status"
            aria-hidden="true"
          ></span>
          Inscription...
        </span>
        <span v-else>S’inscrire</span>
      </button>

      <div v-if="error" class="alert alert-danger mt-3" role="alert" aria-live="assertive">
        {{ error }}
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
const error = ref(null)
const success = ref(null)
const loading = ref(false)

const auth = useAuthStore()

const handleRegister = async () => {
  error.value = null
  success.value = null

  if (password.value !== confirmPassword.value) {
    error.value = 'Les mots de passe ne correspondent pas.'
    return
  }

  loading.value = true

  try {
    await axios.post('/api/register', {
      email: email.value,
      password: password.value,
    })

    // Connexion automatique après inscription
    await auth.login({ email: email.value, password: password.value })
    success.value = 'Inscription réussie ! Bienvenue.'

    // Optionnel : redirection après succès
    // router.push('/')
  } catch (err) {
    if (err.response?.data?.error) {
      error.value = err.response.data.error
    } else {
      error.value = 'Erreur lors de l’inscription.'
    }
  } finally {
    loading.value = false
  }
}
</script>
