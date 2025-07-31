// composables/useUserSearch.js
import { ref } from 'vue'
import apiUser from '@/api/users' // Ton API actuelle

export function useUserSearch() {
  const userOptions = ref([])
  const loadingUsers = ref(false)
  const selectedUser = ref(null)

  // 👇 Fonction helper pour ajouter displayName
  const formatUsers = (users) => {
    return users.map((user) => ({
      ...user,
      displayName: `${user.pseudo} (${user.email})`,
    }))
  }

  const searchUsers = async (searchTerm, limit = 20) => {
    if (!searchTerm || searchTerm.length < 2) {
      userOptions.value = []
      return
    }

    loadingUsers.value = true
    try {
      const response = await apiUser.getAll({
        search: searchTerm,
        limit,
      })

      const users = response.data || response
      // 👇 Ajoute displayName à chaque utilisateur
      userOptions.value = formatUsers(users)
    } catch (error) {
      console.error('Erreur recherche users:', error)
      userOptions.value = []
    } finally {
      loadingUsers.value = false
    }
  }

  const loadInitialUsers = async (limit = 50) => {
    loadingUsers.value = true
    try {
      const response = await apiUser.getAll({ limit })
      const users = response.data || response
      // 👇 Ajoute displayName aux utilisateurs initiaux aussi
      userOptions.value = formatUsers(users)
    } finally {
      loadingUsers.value = false
    }
  }

  return {
    userOptions,
    loadingUsers,
    selectedUser,
    searchUsers,
    loadInitialUsers,
  }
}
