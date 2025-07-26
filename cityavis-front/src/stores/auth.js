// src/stores/auth.js
import { defineStore } from 'pinia';
import { nextTick } from 'vue';
import apiClient from '@/axios';
import router from '@/router';

const STORAGE_KEYS = {
  ACCESS_TOKEN: 'access_token',
  REFRESH_TOKEN: 'refresh_token',
  USER_DATA: 'user_data',
};

const secureStorage = {
  /**
   * Récupère une valeur du localStorage avec gestion d'erreur
   * @param {string} key - Clé de stockage
   * @returns {string|null}
   */
  getItem(key) {
    try {
      return localStorage.getItem(key);
    } catch (error) {
      console.warn(`[Storage] Impossible de lire ${key}:`, error);
      return null;
    }
  },

  /**
   * Stocke une valeur dans le localStorage avec gestion d'erreur
   * @param {string} key - Clé de stockage
   * @param {string} value - Valeur à stocker
   */
  setItem(key, value) {
    try {
      if (value) {
        localStorage.setItem(key, value);
      }
    } catch (error) {
      console.warn(`[Storage] Impossible de sauvegarder ${key}:`, error);
    }
  },

  /**
   * Supprime une valeur du localStorage
   * @param {string} key - Clé de stockage
   */
  removeItem(key) {
    try {
      localStorage.removeItem(key);
    } catch (error) {
      console.warn(`[Storage] Impossible de supprimer ${key}:`, error);
    }
  },

  /**
   * Récupère et parse un objet JSON du localStorage
   * @param {string} key - Clé de stockage
   * @returns {Object|null}
   */
  getObjectItem(key) {
    try {
      const item = this.getItem(key);
      return item ? JSON.parse(item) : null;
    } catch (error) {
      console.warn(`[Storage] Impossible de parser ${key}:`, error);
      return null;
    }
  },

  /**
   * Stringify et stocke un objet dans le localStorage
   * @param {string} key - Clé de stockage
   * @param {Object} value - Objet à stocker
   */
  setObjectItem(key, value) {
    try {
      if (value) {
        this.setItem(key, JSON.stringify(value));
      }
    } catch (error) {
      console.warn(`[Storage] Impossible de stringify ${key}:`, error);
    }
  },
};

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: secureStorage.getObjectItem(STORAGE_KEYS.USER_DATA) || null,
    accessToken: secureStorage.getItem(STORAGE_KEYS.ACCESS_TOKEN) || null,
    refreshToken: secureStorage.getItem(STORAGE_KEYS.REFRESH_TOKEN) || null,
    isLoading: false,
    loginError: null,
  }),

  getters: {
    /**
     * Vérifie si l'utilisateur est authentifié
     * @param {Object} state - État du store
     * @returns {boolean}
     */
    isAuthenticated: (state) => {
      return !!(state.accessToken && state.user);
    },

    /**
     * Récupère les informations utilisateur de manière sécurisée
     * @param {Object} state - État du store
     * @returns {Object|null}
     */
    currentUser: (state) => {
      return state.user ? { ...state.user } : null;
    },

    /**
     * Vérifie si une action de chargement est en cours
     * @param {Object} state - État du store
     * @returns {boolean}
     */
    isAuthLoading: (state) => state.isLoading,

    /**
     * Récupère la dernière erreur de connexion
     * @param {Object} state - État du store
     * @returns {string|null}
     */
    lastLoginError: (state) => state.loginError,
  },

  actions: {
    /**
     * Connecte un utilisateur avec ses identifiants
     * @param {Object} credentials - Identifiants de connexion
     * @param {string} redirectPath - Chemin de redirection après connexion
     * @returns {Promise<boolean>}
     */
    async login(credentials, redirectPath = '/') {
      this.isLoading = true;
      this.loginError = null;

      try {
        const { data } = await apiClient.post('/api/login', credentials);

        if (!data.token || !data.refresh_token) {
          throw new Error('Tokens manquants dans la réponse');
        }

        this.setTokens(data.token, data.refresh_token);
        await this.fetchUser();

        // Redirection après mise à jour du DOM
        await nextTick();
        await router.push(redirectPath);

        return true;
      } catch (error) {
        this.handleAuthError('Erreur de connexion', error);
        return false;
      } finally {
        this.isLoading = false;
      }
    },

    /**
     * Récupère les informations de l'utilisateur connecté
     * @returns {Promise<Object|null>}
     */
    async fetchUser() {
      if (!this.accessToken) {
        throw new Error('Aucun token d\'accès disponible');
      }

      try {
        this.isLoading = true;
        const { data } = await apiClient.get('/api/me');

        this.user = data;
        secureStorage.setObjectItem(STORAGE_KEYS.USER_DATA, data);

        return data;
      } catch (error) {
        if (error.response?.status === 401) {
          await this.logout();
          throw new Error('Session expirée');
        }

        console.error('[fetchUser] Erreur:', error);
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    /**
     * Définit les tokens d'authentification
     * @param {string} accessToken - Token d'accès
     * @param {string} refreshToken - Token de rafraîchissement
     */
    setTokens(accessToken, refreshToken) {
      if (accessToken) {
        this.setAccessToken(accessToken);
      }
      if (refreshToken) {
        this.setRefreshToken(refreshToken);
      }
    },

    /**
     * Définit le token d'accès
     * @param {string} token - Token d'accès
     */
    setAccessToken(token) {
      if (!token) return;

      this.accessToken = token;
      secureStorage.setItem(STORAGE_KEYS.ACCESS_TOKEN, token);
    },

    /**
     * Définit le token de rafraîchissement
     * @param {string} token - Token de rafraîchissement
     */
    setRefreshToken(token) {
      if (!token) return;

      this.refreshToken = token;
      secureStorage.setItem(STORAGE_KEYS.REFRESH_TOKEN, token);
    },

    /**
     * Déconnecte l'utilisateur et nettoie les données
     * @param {string} redirectPath - Chemin de redirection
     * @param {boolean} showMessage - Afficher un message de déconnexion
     */
    async logout(redirectPath = '/login', showMessage = false) {
      try {
        // Optionnel : appeler l'API de déconnexion
        if (this.accessToken) {
          await apiClient.post('/api/logout').catch(() => {
            // Ignorer les erreurs de déconnexion côté serveur
          });
        }
      } catch (error) {
        console.warn('[logout] Erreur lors de la déconnexion:', error);
      }

      // Nettoyer l'état local
      this.clearAuthData();

      // Redirection
      if (router.currentRoute.value.path !== redirectPath) {
        await router.push(redirectPath);
      }

      if (showMessage) {
        console.info('Déconnexion réussie');
      }
    },

    /**
     * Rafraîchit le token d'accès si nécessaire
     * @returns {Promise<boolean>}
     */
    async refreshTokenIfNeeded() {
      if (!this.refreshToken) {
        await this.logout();
        return false;
      }

      try {
        const { data } = await apiClient.post('/api/token/refresh', {
          refresh_token: this.refreshToken,
        });

        if (!data.token) {
          throw new Error('Nouveau token manquant dans la réponse');
        }

        this.setAccessToken(data.token);

        // Mettre à jour le refresh token si fourni
        if (data.refresh_token) {
          this.setRefreshToken(data.refresh_token);
        }

        return true;
      } catch (error) {
        console.error('[refreshToken] Erreur:', error);
        await this.logout();
        return false;
      }
    },

    /**
     * Vérifie et initialise l'état d'authentification au démarrage
     * @returns {Promise<boolean>}
     */
    async initializeAuth() {
      if (!this.accessToken) {
        return false;
      }

      try {
        await this.fetchUser();
        return true;
      } catch (error) {
        console.warn('[initializeAuth] Échec de l\'initialisation:', error);
        await this.logout();
        return false;
      }
    },

    /**
     * Met à jour les informations utilisateur
     * @param {Object} userData - Nouvelles données utilisateur
     */
    updateUser(userData) {
      if (userData) {
        this.user = { ...this.user, ...userData };
        secureStorage.setObjectItem(STORAGE_KEYS.USER_DATA, this.user);
      }
    },

    /**
     * Nettoie toutes les données d'authentification
     * @private
     */
    clearAuthData() {
      // État du store
      this.user = null;
      this.accessToken = null;
      this.refreshToken = null;
      this.isLoading = false;
      this.loginError = null;

      // Stockage local
      secureStorage.removeItem(STORAGE_KEYS.ACCESS_TOKEN);
      secureStorage.removeItem(STORAGE_KEYS.REFRESH_TOKEN);
      secureStorage.removeItem(STORAGE_KEYS.USER_DATA);
    },

    /**
     * Gère les erreurs d'authentification
     * @param {string} message - Message d'erreur
     * @param {Error} error - Erreur originale
     * @private
     */
    handleAuthError(message, error) {
      console.error(`[Auth] ${message}:`, error);
      this.loginError = error.response?.data?.message || error.message || message;
    },

    /**
     * Réinitialise l'erreur de connexion
     */
    clearLoginError() {
      this.loginError = null;
    },
  },
});
