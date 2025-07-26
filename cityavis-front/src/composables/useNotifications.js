import { ref } from 'vue'

const notificationInstance = ref(null)

export const useNotifications = () => {
  const setNotificationInstance = (instance) => {
    notificationInstance.value = instance
  }

  const notify = {
    success: (message, title = null, options = {}) => {
      if (notificationInstance.value) {
        notificationInstance.value.addNotification({
          type: 'success',
          title,
          message,
          duration: 4000,
          ...options
        })
      }
    },

    error: (message, title = 'Erreur', options = {}) => {
      if (notificationInstance.value) {
        notificationInstance.value.addNotification({
          type: 'error',
          title,
          message,
          duration: 6000,
          ...options
        })
      }
    },

    warning: (message, title = 'Attention', options = {}) => {
      if (notificationInstance.value) {
        notificationInstance.value.addNotification({
          type: 'warning',
          title,
          message,
          duration: 5000,
          ...options
        })
      }
    },

    info: (message, title = null, options = {}) => {
      if (notificationInstance.value) {
        notificationInstance.value.addNotification({
          type: 'info',
          title,
          message,
          duration: 4000,
          ...options
        })
      }
    }
  }

  const clearAll = () => {
    if (notificationInstance.value) {
      notificationInstance.value.clearAll()
    }
  }

  return {
    setNotificationInstance,
    notify,
    clearAll
  }
}
