import axios from 'axios'
import { useAuthStore } from '@/stores/auth'

const instance = axios.create({
  baseURL: 'https://localhost:8000', // ou l’URL de ton backend
})

// Ajoute le token JWT
instance.interceptors.request.use((config) => {
  const auth = useAuthStore()
  if (auth.accessToken) {
    config.headers.Authorization = `Bearer ${auth.accessToken}`
  }
  return config
})

// Rafraîchir le token si 401
instance.interceptors.response.use(
  (response) => response,
  async (error) => {
    const auth = useAuthStore()

    if (error.response?.status === 401 && !error.config._retry && auth.refreshToken) {
      error.config._retry = true
      const refreshed = await auth.refreshTokenIfNeeded()

      if (refreshed) {
        error.config.headers.Authorization = `Bearer ${auth.accessToken}`
        return instance(error.config)
      }
    }

    return Promise.reject(error)
  },
)

export default instance
