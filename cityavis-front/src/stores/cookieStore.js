// stores/cookieStore.js
import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useCookieStore = defineStore('cookie', () => {
  // État
  const isInfoDisplayed = ref(false)
  const hasBeenAcknowledged = ref(false)

  // Actions
  const showCookieInfo = () => {
    isInfoDisplayed.value = true
  }

  const hideCookieInfo = () => {
    isInfoDisplayed.value = false
  }

  const acknowledgeCookies = () => {
    hasBeenAcknowledged.value = true
    isInfoDisplayed.value = false

    try {
      localStorage.setItem('cookie-acknowledged', 'true')
    } catch (error) {
      console.warn('Impossible de sauvegarder le statut des cookies:', error)
    }
  }

  const checkCookieStatus = () => {
    try {
      const acknowledged = localStorage.getItem('cookie-acknowledged')
      hasBeenAcknowledged.value = acknowledged === 'true'

      // Si pas encore validé, montrer l'info après 2 secondes
      if (!hasBeenAcknowledged.value) {
        setTimeout(() => {
          isInfoDisplayed.value = true
        }, 2000)
      }
    } catch (error) {
      console.warn('Impossible de lire le statut des cookies:', error)
      // Fallback : montrer le bandeau
      setTimeout(() => {
        isInfoDisplayed.value = true
      }, 2000)
    }
  }

  return {
    // État
    isInfoDisplayed,
    hasBeenAcknowledged,

    // Actions
    showCookieInfo,
    hideCookieInfo,
    acknowledgeCookies,
    checkCookieStatus,
  }
})
