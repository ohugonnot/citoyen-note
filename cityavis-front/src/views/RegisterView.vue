<template>
  <div class="register container py-4" style="max-width: 400px">
    <h1 class="mb-4 text-primary fw-bold text-center">Créer un compte</h1>

    <form novalidate autocomplete="off" @submit.prevent="handleRegister">
      <div class="mb-3">
        <input
          v-model="email"
          name="email"
          type="email"
          class="form-control"
          :class="{
            'is-invalid': email.length > 0 && !isEmailValid,
            'is-valid': email.length > 0 && isEmailValid,
          }"
          placeholder="Email"
          required
          autocomplete="username"
          :disabled="auth.isAuthLoading"
        />

        <div v-if="email.length > 0 && !isEmailValid" class="mt-1">
          <small class="text-danger">
            <i class="bi bi-x-circle"></i>
            Format d'email invalide
          </small>
        </div>

        <div v-if="email.length > 0 && isEmailValid" class="mt-1">
          <small class="text-success">
            <i class="bi bi-check-circle"></i>
            Email valide
          </small>
        </div>
      </div>

      <div class="mb-3">
        <div class="position-relative">
          <input
            v-model="password"
            name="password"
            :type="showPassword ? 'text' : 'password'"
            class="form-control pe-5"
            :class="{
              'is-invalid': password.length > 0 && !isPasswordValid,
              'is-valid': password.length > 0 && isPasswordValid,
            }"
            placeholder="Mot de passe"
            required
            autocomplete="new-password"
            :disabled="auth.isAuthLoading"
            @input="onPasswordInput"
          />

          <button
            type="button"
            class="btn btn-link position-absolute top-50 end-0 translate-middle-y me-1 p-1"
            :disabled="auth.isAuthLoading"
            style="border: none; z-index: 10"
            @click="showPassword = !showPassword"
          >
            <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'" class="text-muted"></i>
          </button>
        </div>

        <!-- Indicateurs de robustesse -->
        <div v-if="password.length > 0" class="mt-2">
          <small class="d-block mb-1">Force du mot de passe :</small>
          <div class="progress mb-2" style="height: 5px">
            <div
              class="progress-bar"
              :class="passwordStrengthClass"
              :style="{ width: passwordStrengthPercent + '%' }"
            ></div>
          </div>

          <div class="row g-1">
            <div class="col-6">
              <small :class="hasMinLength ? 'text-success' : 'text-danger'">
                <i :class="hasMinLength ? 'bi bi-check' : 'bi bi-x'"></i>
                8 caractères min
              </small>
            </div>
            <div class="col-6">
              <small :class="hasUpperCase ? 'text-success' : 'text-danger'">
                <i :class="hasUpperCase ? 'bi bi-check' : 'bi bi-x'"></i>
                Majuscule
              </small>
            </div>
            <div class="col-6">
              <small :class="hasLowerCase ? 'text-success' : 'text-danger'">
                <i :class="hasLowerCase ? 'bi bi-check' : 'bi bi-x'"></i>
                Minuscule
              </small>
            </div>
            <div class="col-6">
              <small :class="hasNumber ? 'text-success' : 'text-danger'">
                <i :class="hasNumber ? 'bi bi-check' : 'bi bi-x'"></i>
                Chiffre
              </small>
            </div>
            <div class="col-6">
              <small :class="hasSpecialChar ? 'text-success' : 'text-danger'">
                <i :class="hasSpecialChar ? 'bi bi-check' : 'bi bi-x'"></i>
                Caractère spécial
              </small>
            </div>
          </div>
        </div>
      </div>

      <div class="mb-3">
        <div class="position-relative">
          <input
            v-model="confirmPassword"
            name="password_confirm"
            :type="showConfirmPassword ? 'text' : 'password'"
            class="form-control"
            :class="{
              'pe-5': showCopyButton || !showCopyButton,
              'is-invalid': confirmPassword.length > 0 && !passwordsMatch,
              'is-valid': confirmPassword.length > 0 && passwordsMatch && isPasswordValid,
            }"
            placeholder="Confirmer le mot de passe"
            required
            autocomplete="off"
            :disabled="auth.isAuthLoading"
          />

          <div class="position-absolute top-50 end-0 translate-middle-y me-1 d-flex">
            <button
              v-if="showCopyButton"
              type="button"
              class="btn btn-link p-1 me-1"
              :disabled="auth.isAuthLoading || !isPasswordValid"
              title="Recopier le mot de passe"
              style="border: none"
              @click="copyPassword"
            >
              <i class="bi bi-copy" :class="isPasswordValid ? 'text-primary' : 'text-muted'"></i>
            </button>

            <button
              type="button"
              class="btn btn-link p-1"
              :disabled="auth.isAuthLoading"
              style="border: none"
              @click="showConfirmPassword = !showConfirmPassword"
            >
              <i
                :class="showConfirmPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"
                class="text-muted"
              ></i>
            </button>
          </div>
        </div>

        <div v-if="showHint && isPasswordValid" class="mt-1">
          <small class="text-primary">
            <i class="bi bi-lightbulb"></i>
            Cliquez sur l'icône pour recopier le mot de passe
          </small>
        </div>

        <div v-if="confirmPassword.length > 0 && !passwordsMatch" class="mt-1">
          <small class="text-danger">
            <i class="bi bi-x-circle"></i>
            Les mots de passe ne correspondent pas
          </small>
        </div>
      </div>

      <div class="mb-3">
        <div class="form-check">
          <input
            id="acceptPolicy"
            v-model="acceptPolicy"
            class="form-check-input"
            type="checkbox"
            required
          />
          <label class="form-check-label small" for="acceptPolicy">
            J'accepte la
            <Button
              label="politique de confidentialité"
              link
              size="small"
              class="p-0 text-decoration-underline"
              @click="privacyStore.openPolicy('general')"
            />
            et comprends l'utilisation de mes données personnelles.
          </label>
        </div>
      </div>

      <button
        type="submit"
        class="btn btn-primary w-100"
        :disabled="auth.isAuthLoading || !isFormValid || !acceptPolicy"
      >
        <span v-if="auth.isAuthLoading">
          <span
            class="spinner-border spinner-border-sm me-2"
            role="status"
            aria-hidden="true"
          ></span>
          Inscription...
        </span>
        <span v-else>S'inscrire</span>
      </button>

      <div v-if="localError" class="alert alert-danger mt-3" role="alert">
        {{ localError }}
      </div>

      <div v-if="auth.lastLoginError" class="alert alert-danger mt-3" role="alert">
        {{ auth.lastLoginError }}
      </div>

      <div v-if="success" class="alert alert-success mt-3" role="alert">
        {{ success }}
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import axios from '@/axios'
import { useAuthStore } from '@/stores/authStore'

import { usePrivacyStore } from '@/stores/privacyStore'

const acceptPolicy = ref(false)
const privacyStore = usePrivacyStore()

const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const localError = ref(null)
const success = ref(null)
const showHint = ref(false)
const showPassword = ref(false)
const showConfirmPassword = ref(false)

const auth = useAuthStore()

// Validation de l'email
const isEmailValid = computed(() => {
  const emailRegex = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/
  return emailRegex.test(email.value)
})

// Validation du mot de passe
const hasMinLength = computed(() => password.value.length >= 8)
const hasUpperCase = computed(() => /[A-Z]/.test(password.value))
const hasLowerCase = computed(() => /[a-z]/.test(password.value))
const hasNumber = computed(() => /\d/.test(password.value))
const hasSpecialChar = computed(() => /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password.value))

const isPasswordValid = computed(() => {
  return (
    hasMinLength.value &&
    hasUpperCase.value &&
    hasLowerCase.value &&
    hasNumber.value &&
    hasSpecialChar.value
  )
})

const passwordsMatch = computed(() => {
  return password.value === confirmPassword.value
})

const isFormValid = computed(() => {
  return isEmailValid.value && isPasswordValid.value && passwordsMatch.value
})

const passwordStrengthPercent = computed(() => {
  let score = 0
  if (hasMinLength.value) score += 20
  if (hasUpperCase.value) score += 20
  if (hasLowerCase.value) score += 20
  if (hasNumber.value) score += 20
  if (hasSpecialChar.value) score += 20
  return Math.round(score)
})

const passwordStrengthClass = computed(() => {
  const percent = passwordStrengthPercent.value
  if (percent < 34) return 'bg-danger'
  if (percent < 67) return 'bg-warning'
  return 'bg-success'
})

const showCopyButton = computed(() => {
  return password.value.length > 0 && confirmPassword.value.length === 0
})

const onPasswordInput = () => {
  setTimeout(() => {
    if (password.value.length > 6 && confirmPassword.value.length === 0 && isPasswordValid.value) {
      showHint.value = true
    }
  }, 250)
}

const copyPassword = () => {
  if (isPasswordValid.value) {
    confirmPassword.value = password.value
    showHint.value = false
  }
}

const handleRegister = async () => {
  localError.value = null
  success.value = null
  showHint.value = false
  auth.clearLoginError()

  if (!isEmailValid.value) {
    localError.value = 'Veuillez saisir un email valide.'
    return
  }

  if (!isPasswordValid.value) {
    localError.value = 'Le mot de passe ne respecte pas les critères de sécurité.'
    return
  }

  if (!passwordsMatch.value) {
    localError.value = 'Les mots de passe ne correspondent pas.'
    return
  }

  if (!acceptPolicy.value) {
    localError.value = 'Veuillez accepter la politique de confidentialité.'
    return
  }

  try {
    const form = document.querySelector('form')
    form.setAttribute('autocomplete', 'off')

    await axios.post('/api/register', {
      email: email.value,
      password: password.value,
    })

    const loggedIn = await auth.login({
      email: email.value,
      password: password.value,
    })

    if (loggedIn) {
      success.value = 'Inscription réussie ! Bienvenue.'

      setTimeout(() => {
        email.value = ''
        password.value = ''
        confirmPassword.value = ''
      }, 1000)
    }
  } catch (err) {
    const data = err?.response?.data
    if (typeof data?.error === 'string') {
      localError.value = data.error
    } else if (typeof data?.message === 'string') {
      localError.value = data.message
    } else {
      localError.value = "Erreur lors de l'inscription."
    }
  }
}
</script>
