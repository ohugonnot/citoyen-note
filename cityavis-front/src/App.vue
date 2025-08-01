<template>
  <div id="app" class="app-container">
    <Header v-if="showHeader" />

    <main class="main-content" :class="{ 'with-header': showHeader }">
      <div class="container-fluid">
        <router-view v-slot="{ Component }">
          <transition name="page" mode="out-in">
            <component :is="Component" :key="route.path" />
          </transition>
        </router-view>
      </div>
    </main>

    <Footer />
    <PrivacyPolicy />
    <CookieBanner />
    <NotificationToast ref="notificationRef" />
    <LoadingOverlay v-if="isGlobalLoading" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { useNotifications } from '@/composables/useNotifications.js'
import { usePrivacyStore } from '@/stores/privacyStore'

import Header from '@/components/Header.vue'
import NotificationToast from '@/components/NotificationToast.vue'
import LoadingOverlay from '@/components/LoadingOverlay.vue'
import { useCookieStore } from '@/stores/cookieStore'
import CookieBanner from '@/components/CookieBanner.vue'
import Footer from '@/components/Footer.vue'
import PrivacyPolicy from '@/components/PrivacyPolicy.vue'

const cookieStore = useCookieStore()
const privacyStore = usePrivacyStore()

onMounted(() => {
  cookieStore.checkCookieStatus()
})

const route = useRoute()
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
  padding-top: 103px;
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

.container,
.main-content {
  background-color: white !important;
}

.app-container {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.main-content {
  flex: 1;
  padding-bottom: 2rem; /* Espace avant le footer */
}
</style>
