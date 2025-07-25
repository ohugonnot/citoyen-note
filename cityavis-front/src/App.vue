<template>
  <div>
    <Header />
    <main class="main-content">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import Header from '@/components/Header.vue'
</script>

<style>
.fade-in {
  animation: fadeIn 0.6s ease-in-out;
}
.main-content {
  padding-top: 100px; /* marge pour laisser la place au header fixe */
}
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
