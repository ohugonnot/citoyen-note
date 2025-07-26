// src/axios.js
import axios from 'axios';
import { useAuthStore } from '@/stores/auth';

/**
 * Configuration de l'instance Axios avec gestion automatique de l'authentification
 * et rafraîchissement des tokens
 */

const BASE_URL = import.meta.env.VITE_API_URL || 'https://localhost:8000';

// Instance axios configurée
const apiClient = axios.create({
  baseURL: BASE_URL,
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
  },
});

// État du rafraîchissement des tokens
const tokenRefreshState = {
  isRefreshing: false,
  failedRequestsQueue: [],
};

/**
 * Traite la queue des requêtes en attente après un rafraîchissement de token
 * @param {Error|null} error - Erreur éventuelle
 * @param {string|null} token - Nouveau token d'accès
 */
const processFailedRequestsQueue = (error = null, token = null) => {
  tokenRefreshState.failedRequestsQueue.forEach(({ resolve, reject }) => {
    if (error) {
      reject(error);
    } else {
      resolve(token);
    }
  });

  tokenRefreshState.failedRequestsQueue = [];
};

/**
 * Ajoute une requête à la queue d'attente
 * @param {Function} resolve - Fonction de résolution
 * @param {Function} reject - Fonction de rejet
 */
const addToFailedQueue = (resolve, reject) => {
  tokenRefreshState.failedRequestsQueue.push({ resolve, reject });
};

/**
 * Vérifie si une erreur est due à un token expiré
 * @param {Object} error - Erreur axios
 * @returns {boolean}
 */
const isTokenExpiredError = (error) => {
  return error.response?.status === 401;
};

/**
 * Vérifie si une requête peut être retentée
 * @param {Object} config - Configuration de la requête
 * @returns {boolean}
 */
const canRetryRequest = (config) => {
  return !config._isRetry;
};

/**
 * Marque une requête comme ayant été retentée
 * @param {Object} config - Configuration de la requête
 */
const markRequestAsRetried = (config) => {
  config._isRetry = true;
};

/**
 * Ajoute le token d'autorisation à la requête
 * @param {Object} config - Configuration de la requête
 * @param {string} token - Token d'accès
 */
const addAuthorizationHeader = (config, token) => {
  config.headers.Authorization = `Bearer ${token}`;
};

// Intercepteur de requête : ajoute automatiquement le token d'autorisation
apiClient.interceptors.request.use(
  (config) => {
    const authStore = useAuthStore();

    if (authStore.accessToken) {
      addAuthorizationHeader(config, authStore.accessToken);
    }

    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Intercepteur de réponse : gère le rafraîchissement automatique des tokens
apiClient.interceptors.response.use(
  (response) => response,
  async (error) => {
    const authStore = useAuthStore();
    const originalRequest = error.config;

    // Conditions pour ne pas tenter un rafraîchissement
    if (
      !isTokenExpiredError(error) ||
      !canRetryRequest(originalRequest) ||
      !authStore.refreshToken
    ) {
      return Promise.reject(error);
    }

    markRequestAsRetried(originalRequest);

    // Si un rafraîchissement est déjà en cours, ajouter à la queue
    if (tokenRefreshState.isRefreshing) {
      return new Promise((resolve, reject) => {
        addToFailedQueue(
          (newToken) => {
            addAuthorizationHeader(originalRequest, newToken);
            resolve(apiClient(originalRequest));
          },
          (refreshError) => {
            reject(refreshError);
          }
        );
      });
    }

    // Démarrer le processus de rafraîchissement
    tokenRefreshState.isRefreshing = true;

    try {
      const refreshSuccess = await authStore.refreshTokenIfNeeded();

      if (!refreshSuccess) {
        throw new Error('Token refresh failed');
      }

      const newAccessToken = authStore.accessToken;

      // Traiter la queue avec le nouveau token
      processFailedRequestsQueue(null, newAccessToken);

      // Relancer la requête originale avec le nouveau token
      addAuthorizationHeader(originalRequest, newAccessToken);
      return apiClient(originalRequest);

    } catch (refreshError) {
      // En cas d'échec, traiter la queue avec l'erreur
      processFailedRequestsQueue(refreshError);

      // Optionnel : déconnecter l'utilisateur en cas d'échec du refresh
      await authStore.logout();

      return Promise.reject(refreshError);

    } finally {
      tokenRefreshState.isRefreshing = false;
    }
  }
);

export default apiClient;
