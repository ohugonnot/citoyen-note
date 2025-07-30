<template>
  <teleport to="body">
    <transition name="overlay">
      <div v-if="show" class="loading-overlay">
        <div class="loading-content">
          <div class="loading-spinner-lg"></div>
          <h5 class="loading-title">{{ title }}</h5>
          <p v-if="message" class="loading-message">{{ message }}</p>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
defineProps({
  show: {
    type: Boolean,
    default: true,
  },
  title: {
    type: String,
    default: 'Chargement...',
  },
  message: {
    type: String,
    default: null,
  },
})
</script>

<style scoped>
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: var(--z-modal);
}

.loading-content {
  text-align: center;
  max-width: 300px;
  padding: 2rem;
}

.loading-spinner-lg {
  width: 60px;
  height: 60px;
  border: 4px solid var(--color-gray-light);
  border-top: 4px solid var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1.5rem;
}

.loading-title {
  color: var(--color-text);
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.loading-message {
  color: var(--color-text-light);
  font-size: 0.9rem;
  margin: 0;
}

.overlay-enter-active,
.overlay-leave-active {
  transition: opacity 0.3s ease;
}

.overlay-enter-from,
.overlay-leave-to {
  opacity: 0;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}
</style>
