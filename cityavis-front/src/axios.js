// src/axios.js
import axios from 'axios'
import { useAuthStore } from '@/stores/authStore'

/**
 * Configuration de l'instance Axios avec gestion automatique de l'authentification
 * et rafraîchissement des tokens
 */

const BASE_URL = import.meta.env.VITE_API_URL || 'https://localhost:8000'

// Instance axios configurée
const apiClient = axios.create({
  baseURL: BASE_URL,
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
  },
})

// Instance séparée pour le refresh (sans intercepteurs)
const refreshClient = axios.create({
  baseURL: BASE_URL,
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
  },
})

// État du rafraîchissement des tokens
const tokenRefreshState = {
  isRefreshing: false,
  failedRequestsQueue: [],
}

/**
 * Traite la queue des requêtes en attente après un rafraîchissement de token
 * @param {Error|null} error - Erreur éventuelle
 * @param {string|null} token - Nouveau token d'accès
 */
const processFailedRequestsQueue = (error = null, token = null) => {
  tokenRefreshState.failedRequestsQueue.forEach(({ resolve, reject }) => {
    if (error) {
      reject(error)
    } else {
      resolve(token)
    }
  })

  tokenRefreshState.failedRequestsQueue = []
}

/**
 * Ajoute une requête à la queue d'attente
 * @param {Function} resolve - Fonction de résolution
 * @param {Function} reject - Fonction de rejet
 */
const addToFailedQueue = (resolve, reject) => {
  tokenRefreshState.failedRequestsQueue.push({ resolve, reject })
}

/**
 * Vérifie si une erreur est due à un token expiré
 * @param {Object} error - Erreur axios
 * @returns {boolean}
 */
const isTokenExpiredError = (error) => {
  return error.response?.status === 401
}

/**
 * Vérifie si une requête peut être retentée
 * @param {Object} config - Configuration de la requête
 * @returns {boolean}
 */
const canRetryRequest = (config) => {
  return !config._isRetry
}

/**
 * Marque une requête comme ayant été retentée
 * @param {Object} config - Configuration de la requête
 */
const markRequestAsRetried = (config) => {
  config._isRetry = true
}

/**
 * Ajoute le token d'autorisation à la requête
 * @param {Object} config - Configuration de la requête
 * @param {string} token - Token d'accès
 */
const addAuthorizationHeader = (config, token) => {
  config.headers.Authorization = `Bearer ${token}`
}

// Intercepteur de requête : ajoute automatiquement le token d'autorisation
apiClient.interceptors.request.use(
  (config) => {
    const authStore = useAuthStore()

    if (authStore.accessToken) {
      addAuthorizationHeader(config, authStore.accessToken)
    }

    return config
  },
  (error) => {
    return Promise.reject(error)
  },
)

// Intercepteur de réponse : gère le rafraîchissement automatique des tokens
apiClient.interceptors.response.use(
  (response) => response,
  async (error) => {
    const authStore = useAuthStore()
    const originalRequest = error.config

    // Conditions pour ne pas tenter un rafraîchissement
    if (
      !isTokenExpiredError(error) ||
      !canRetryRequest(originalRequest) ||
      !authStore.refreshToken
    ) {
      return Promise.reject(error)
    }

    markRequestAsRetried(originalRequest)

    // Si un rafraîchissement est déjà en cours, ajouter à la queue
    if (tokenRefreshState.isRefreshing) {
      return new Promise((resolve, reject) => {
        addToFailedQueue(
          (newToken) => {
            addAuthorizationHeader(originalRequest, newToken)
            resolve(apiClient(originalRequest))
          },
          (refreshError) => {
            reject(refreshError)
          },
        )
      })
    }

    // Démarrer le processus de rafraîchissement
    tokenRefreshState.isRefreshing = true

    try {
      console.log('[Axios] Tentative de refresh du token...')
      const refreshSuccess = await authStore.refreshTokenIfNeeded()

      if (!refreshSuccess) {
        console.error('[Axios] Refresh token échoué, déconnexion forcée')
        throw new Error('Token refresh failed')
      }

      const newAccessToken = authStore.accessToken
      console.log('[Axios] Refresh token réussi, relance des requêtes')

      // Traiter la queue avec le nouveau token
      processFailedRequestsQueue(null, newAccessToken)

      // Relancer la requête originale avec le nouveau token
      addAuthorizationHeader(originalRequest, newAccessToken)
      return apiClient(originalRequest)
    } catch (refreshError) {
      console.error('[Axios] Échec refresh token, déconnexion...', refreshError)

      // En cas d'échec, traiter la queue avec l'erreur
      processFailedRequestsQueue(refreshError)

      // Déconnecter l'utilisateur en cas d'échec du refresh
      console.log('[Axios] Déconnexion en cours...')
      try {
        await authStore.logout('/login', true)
        console.log('[Axios] Déconnexion réussie')
      } catch (logoutError) {
        console.error('[Axios] Erreur lors du logout:', logoutError)
        // Fallback : forcer le rechargement
        console.log('[Axios] Fallback - rechargement forcé')
        setTimeout(() => {
          window.location.href = '/login'
        }, 100)
      }

      return Promise.reject(refreshError)
    } finally {
      tokenRefreshState.isRefreshing = false
    }
  },
)

export default apiClient
export { refreshClient }
