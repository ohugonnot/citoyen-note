// src/stores/authStore.js - Version finale avec support public
import { defineStore } from 'pinia'
import { nextTick } from 'vue'
import apiClient, { refreshClient } from '@/axios'
import router from '@/router'

const STORAGE_KEYS = {
  ACCESS_TOKEN: 'access_token',
  REFRESH_TOKEN: 'refresh_token',
  USER_DATA: 'user_data',
  PUBLIC_USER: 'public_user_data',
}

const secureStorage = {
  getItem(key) {
    try {
      return localStorage.getItem(key)
    } catch (error) {
      console.warn(`[Storage] Lecture échouée : ${key}`, error)
      return null
    }
  },
  setItem(key, value) {
    try {
      if (value) localStorage.setItem(key, value)
    } catch (error) {
      console.warn(`[Storage] Sauvegarde échouée : ${key}`, error)
    }
  },
  removeItem(key) {
    try {
      localStorage.removeItem(key)
    } catch (error) {
      console.warn(`[Storage] Suppression échouée : ${key}`, error)
    }
  },
  getObjectItem(key) {
    try {
      const item = this.getItem(key)
      return item ? JSON.parse(item) : null
    } catch (error) {
      console.warn(`[Storage] Parsing échoué : ${key}`, error)
      return null
    }
  },
  setObjectItem(key, value) {
    try {
      if (value) this.setItem(key, JSON.stringify(value))
    } catch (error) {
      console.warn(`[Storage] Stringify échoué : ${key}`, error)
    }
  },
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: secureStorage.getObjectItem(STORAGE_KEYS.USER_DATA),
    accessToken: secureStorage.getItem(STORAGE_KEYS.ACCESS_TOKEN),
    refreshToken: secureStorage.getItem(STORAGE_KEYS.REFRESH_TOKEN),
    isLoading: false,
    loginError: null,

    publicUser: secureStorage.getObjectItem(STORAGE_KEYS.PUBLIC_USER),
  }),

  getters: {
    isAuthenticated: (state) => !!(state.accessToken && state.user),
    currentUser: (state) => (state.user ? { ...state.user } : null),
    isAuthLoading: (state) => state.isLoading,
    lastLoginError: (state) => state.loginError,

    isPublicAuthenticated: (state) => !!state.publicUser,
    currentPublicUser: (state) => (state.publicUser ? { ...state.publicUser } : null),

    currentMode: (state) => {
      if (state.accessToken && state.user) return 'admin'
      if (state.publicUser) return 'public'
      return 'anonymous'
    },
    displayName: (state) => {
      if (state.user) return state.user.name || state.user.nom
      if (state.publicUser) return state.publicUser.name || state.publicUser.nom
      return 'Anonyme'
    },
  },

  actions: {
    async login(credentials, redirectPath = '/') {
      this.isLoading = true
      this.loginError = null
      try {
        const { data } = await apiClient.post('/api/login', credentials)

        if (!data.token || !data.refresh_token) throw new Error('Tokens manquants')

        this.setTokens(data.token, data.refresh_token)
        await this.fetchUser()
        await nextTick()
        await router.push(redirectPath)
        return true
      } catch (error) {
        this.handleAuthError('Erreur de connexion', error)
        return false
      } finally {
        this.isLoading = false
      }
    },

    async fetchUser() {
      if (!this.accessToken) throw new Error('Aucun token')

      try {
        this.isLoading = true
        const { data } = await apiClient.get('/api/me')
        this.setUser(data)
        return data
      } catch (error) {
        console.error('[fetchUser] Erreur complète:', error)
        throw error
      } finally {
        this.isLoading = false
      }
    },

    async saveUserProfile(updatedData) {
      if (!this.user?.id) {
        console.warn('[saveUserProfile] Aucun utilisateur connecté')
        return false
      }
      try {
        if (updatedData.dateNaissance && typeof updatedData.dateNaissance !== 'string') {
          updatedData.dateNaissance = updatedData.dateNaissance.toISOString().split('T')[0]
        }

        const { data } = await apiClient.put(`/api/users/${this.user.id}`, updatedData)
        this.setUser(data)
        return true
      } catch (error) {
        console.error('[saveUserProfile] Erreur update profil :', error)
        return false
      }
    },

    async logout(redirectPath = '/login', showMessage = false) {
      console.log('[logout] Début déconnexion, redirectPath:', redirectPath)

      try {
        if (this.accessToken) {
          await apiClient.post('/api/logout')
        }
      } catch (error) {
        console.warn('[logout] Erreur logout serveur:', error.response?.status)
      }

      this.clearAuthData()

      try {
        const currentPath = router.currentRoute.value.path
        console.log('[logout] Route actuelle:', currentPath)

        if (currentPath !== redirectPath) {
          await router.push(redirectPath)
          console.log('[logout] Redirection réussie vers:', redirectPath)
        }
      } catch (navigationError) {
        console.error('[logout] Erreur de navigation:', navigationError)
        setTimeout(() => {
          window.location.href = redirectPath
        }, 100)
      }

      if (showMessage) {
        console.info('Déconnexion réussie')
      }
    },

    async refreshTokenIfNeeded() {
      if (!this.refreshToken) {
        console.warn('[refreshToken] Aucun refresh token disponible')
        return false
      }

      try {
        console.log('[refreshToken] Tentative de rafraîchissement...')
        const { data } = await refreshClient.post('/api/token/refresh', {
          refresh_token: this.refreshToken,
        })

        if (!data.token) {
          console.error('[refreshToken] Nouveau token manquant dans la réponse')
          throw new Error('Nouveau token manquant')
        }

        this.setAccessToken(data.token)
        if (data.refresh_token) this.setRefreshToken(data.refresh_token)

        console.log('[refreshToken] Rafraîchissement réussi')
        return true
      } catch (error) {
        console.error(
          '[refreshToken] Erreur détaillée:',
          error.response?.status,
          error.response?.data,
        )

        if (error.response?.status === 401) {
          console.warn('[refreshToken] Refresh token invalide/expiré')
          this.clearAuthData()
        }

        return false
      }
    },

    async initializeAuth() {
      if (!this.accessToken) return false

      try {
        await this.fetchUser()
        return true
      } catch (error) {
        console.error('[initializeAuth] Erreur', error)
        return false
      }
    },

    setUser(user) {
      this.user = user ? { ...user } : null
      secureStorage.setObjectItem(STORAGE_KEYS.USER_DATA, this.user)
    },

    updateUser(userData) {
      if (userData) {
        this.user = { ...this.user, ...userData }
        secureStorage.setObjectItem(STORAGE_KEYS.USER_DATA, this.user)
      }
    },

    setTokens(accessToken, refreshToken) {
      if (accessToken) this.setAccessToken(accessToken)
      if (refreshToken) this.setRefreshToken(refreshToken)
    },

    setAccessToken(token) {
      this.accessToken = token
      secureStorage.setItem(STORAGE_KEYS.ACCESS_TOKEN, token)
    },

    setRefreshToken(token) {
      this.refreshToken = token
      secureStorage.setItem(STORAGE_KEYS.REFRESH_TOKEN, token)
    },

    setPublicUser(user) {
      this.publicUser = user ? { ...user } : null
      secureStorage.setObjectItem(STORAGE_KEYS.PUBLIC_USER, this.publicUser)
    },

    updatePublicUser(userData) {
      if (userData) {
        this.publicUser = { ...this.publicUser, ...userData }
        secureStorage.setObjectItem(STORAGE_KEYS.PUBLIC_USER, this.publicUser)
      }
    },

    clearAuthData() {
      this.user = null
      this.accessToken = null
      this.refreshToken = null
      this.isLoading = false
      this.loginError = null

      secureStorage.removeItem(STORAGE_KEYS.ACCESS_TOKEN)
      secureStorage.removeItem(STORAGE_KEYS.REFRESH_TOKEN)
      secureStorage.removeItem(STORAGE_KEYS.USER_DATA)
    },

    clearPublicData() {
      this.publicUser = null
      secureStorage.removeItem(STORAGE_KEYS.PUBLIC_USER)
    },

    clearAllData() {
      this.clearAuthData()
      this.clearPublicData()
    },

    handleAuthError(message, error) {
      console.error(`[Auth] ${message}:`, error, error?.response?.data)
      const responseData = error?.response?.data

      if (typeof responseData === 'string') {
        this.loginError = responseData
      } else if (typeof responseData?.error === 'string') {
        this.loginError = responseData.error
      } else if (typeof responseData?.message === 'string') {
        this.loginError = responseData.message
      } else if (error?.message) {
        this.loginError = error.message
      } else {
        this.loginError = message
      }
    },

    clearLoginError() {
      this.loginError = null
    },
  },
})
