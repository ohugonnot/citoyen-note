<template>
  <teleport to="body">
    <div class="toast-container" :class="{ 'mobile-container': isMobile }">
      <transition-group name="toast" tag="div">
        <div
          v-for="notification in notifications"
          :key="notification.id"
          class="toast-notification"
          :class="[`toast-${notification.type}`, { 'toast-mobile': isMobile }]"
          @click="removeNotification(notification.id)"
        >
          <div class="toast-content">
            <div class="toast-icon">
              {{ getIcon(notification.type) }}
            </div>
            <div class="toast-body">
              <div v-if="notification.title" class="toast-title">
                {{ notification.title }}
              </div>
              <div class="toast-message">
                {{ notification.message }}
              </div>
            </div>
            <button class="toast-close" @click.stop="removeNotification(notification.id)">
              <i class="bi bi-x"></i>
            </button>
          </div>
          <div
            class="toast-progress"
            :style="{ animationDuration: `${notification.duration}ms` }"
          ></div>
        </div>
      </transition-group>
    </div>
  </teleport>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const notifications = ref([])
const isMobile = ref(window.innerWidth < 768)

const handleResize = () => {
  isMobile.value = window.innerWidth < 768
}

onMounted(() => {
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})

const getIcon = (type) => {
  const icons = {
    success: '✅',
    error: '❌',
    warning: '⚠️',
    info: 'ℹ️',
  }
  return icons[type] || 'ℹ️'
}

const addNotification = (notification) => {
  const id = Date.now() + Math.random()
  const newNotification = {
    id,
    type: notification.type || 'info',
    title: notification.title,
    message: notification.message,
    duration: notification.duration || 5000,
    ...notification,
  }

  notifications.value.push(newNotification)

  setTimeout(() => {
    removeNotification(id)
  }, newNotification.duration)
}

const removeNotification = (id) => {
  const index = notifications.value.findIndex((n) => n.id === id)
  if (index > -1) {
    notifications.value.splice(index, 1)
  }
}

const clearAll = () => {
  notifications.value = []
}

defineExpose({
  addNotification,
  removeNotification,
  clearAll,
})
</script>

<style scoped>
.toast-container {
  position: fixed;
  top: 90px;
  right: 1rem;
  z-index: var(--z-tooltip);
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  max-width: 400px;
  pointer-events: none;
}

.mobile-container {
  top: 80px;
  left: 1rem;
  right: 1rem;
  max-width: none;
}

.toast-notification {
  background: white;
  border-radius: var(--border-radius-lg);
  box-shadow: var(--shadow-lg);
  overflow: hidden;
  position: relative;
  pointer-events: all;
  cursor: pointer;
  transition: var(--transition);
  border-left: 4px solid;
}

.toast-notification:hover {
  transform: scale(1.02);
  box-shadow: 0 12px 32px rgba(0, 0, 145, 0.25);
}

.toast-mobile {
  margin-bottom: 0.5rem;
}

.toast-success {
  border-left-color: var(--color-accent);
}

.toast-error {
  border-left-color: var(--color-secondary);
}

.toast-warning {
  border-left-color: #ffc107;
}

.toast-info {
  border-left-color: var(--color-primary);
}

.toast-content {
  display: flex;
  align-items: flex-start;
  padding: 1rem 1.25rem;
  gap: 0.75rem;
}

.toast-icon {
  font-size: 1.25rem;
  flex-shrink: 0;
  margin-top: 0.125rem;
}

.toast-body {
  flex: 1;
  min-width: 0;
}

.toast-title {
  font-weight: 600;
  font-size: 0.925rem;
  color: var(--color-text);
  margin-bottom: 0.25rem;
  line-height: 1.3;
}

.toast-message {
  font-size: 0.875rem;
  color: var(--color-text-light);
  line-height: 1.4;
  word-wrap: break-word;
}

.toast-close {
  background: none;
  border: none;
  color: var(--color-gray);
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 50%;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: var(--transition-fast);
  flex-shrink: 0;
}

.toast-close:hover {
  background: rgba(0, 0, 0, 0.1);
  color: var(--color-text);
}

.toast-progress {
  position: absolute;
  bottom: 0;
  left: 0;
  height: 3px;
  background: linear-gradient(90deg, var(--color-primary), var(--color-primary-light));
  width: 100%;
  transform-origin: left;
  animation: progress linear forwards;
}

@keyframes progress {
  from {
    transform: scaleX(1);
  }
  to {
    transform: scaleX(0);
  }
}

.toast-enter-active,
.toast-leave-active {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100%) scale(0.8);
}

.toast-move {
  transition: transform 0.3s ease;
}

@media (max-width: 576px) {
  .toast-content {
    padding: 0.875rem 1rem;
    gap: 0.5rem;
  }

  .toast-icon {
    font-size: 1.1rem;
  }

  .toast-title {
    font-size: 0.875rem;
  }

  .toast-message {
    font-size: 0.8rem;
  }

  .toast-close {
    width: 24px;
    height: 24px;
  }
}
</style>
