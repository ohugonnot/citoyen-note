<template>
  <div id="app" class="app-container">
    <Header v-if="showHeader" />

    <main class="main-content" :class="{ 'with-header': showHeader }">
      <div class="container">
        <router-view v-slot="{ Component, route }">
          <transition name="page" mode="out-in">
            <component :is="Component" :key="route.path" />
          </transition>
        </router-view>
      </div>
    </main>

    <NotificationToast ref="notificationRef" />

    <LoadingOverlay v-if="isGlobalLoading" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useNotifications } from '@/composables/useNotifications.js'

import Header from '@/components/Header.vue'
import NotificationToast from '@/components/NotificationToast.vue'
import LoadingOverlay from '@/components/LoadingOverlay.vue'

const route = useRoute()
const auth = useAuthStore()
const { setNotificationInstance } = useNotifications()

const notificationRef = ref(null)
const isGlobalLoading = ref(false)

const showHeader = computed(() => {
  const hideHeaderRoutes = []
  return !hideHeaderRoutes.includes(route.path)
})

onMounted(async () => {
  await nextTick()
  if (notificationRef.value) {
    setNotificationInstance(notificationRef.value)
  }
})
</script>

<style>
.app-container {
  display: flex;
  flex-direction: column;
}

.main-content {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.main-content.with-header {
  padding-top: 15px;
}

.page-enter-active,
.page-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.page-enter-from {
  opacity: 0;
  transform: translateY(20px);
}

.page-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}

.container, .main-content {
  background-color: white !important;
}
</style>
