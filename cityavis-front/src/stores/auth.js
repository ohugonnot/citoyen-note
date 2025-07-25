import { defineStore } from 'pinia'
import axios from '@/axios'
import router from '@/router'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    accessToken: localStorage.getItem('access_token') || null,
    refreshToken: localStorage.getItem('refresh_token') || null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.accessToken,
  },

  actions: {
    async login(credentials) {
      const { data } = await axios.post('/api/login', credentials)
      this.accessToken = data.token
      this.refreshToken = data.refresh_token
      localStorage.setItem('access_token', this.accessToken)
      localStorage.setItem('refresh_token', this.refreshToken)
      await this.fetchUser()
      router.push('/')
    },

    async fetchUser() {
      const { data } = await axios.get('/api/me')
      this.user = data
    },

    logout() {
      this.accessToken = null
      this.refreshToken = null
      this.user = null
      localStorage.removeItem('access_token')
      localStorage.removeItem('refresh_token')
      router.push('/login')
    },

    async refreshTokenIfNeeded() {
      if (!this.refreshToken) return this.logout()

      try {
        const { data } = await axios.post('/api/token/refresh', {
          refresh_token: this.refreshToken,
        })
        this.accessToken = data.token
        this.refreshToken = data.refresh_token
        localStorage.setItem('access_token', this.accessToken)
        return true
      } catch {
        this.logout()
        return false
      }
    },
  },
})
