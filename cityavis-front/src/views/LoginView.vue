<template>
  <div class="login container py-4" style="max-width: 400px">
    <h1 class="mb-4 text-primary fw-bold text-center">Connexion</h1>

    <form novalidate @submit.prevent="handleLogin">
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
          autocomplete="current-password"
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
          Connexion...
        </span>
        <span v-else>Se connecter</span>
      </button>

      <div
        v-if="auth.lastLoginError"
        class="alert alert-danger mt-3"
        role="alert"
        aria-live="assertive"
      >
        {{ auth.lastLoginError }}
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'

const email = ref('')
const password = ref('')
const error = ref(null)
const loading = ref(false)

const auth = useAuthStore()

const handleLogin = async () => {
  error.value = null
  loading.value = true

  try {
    await auth.login({
      email: email.value,
      password: password.value,
    })
  } catch (err) {
    error.value = err
  } finally {
    loading.value = false
  }
}
</script>
