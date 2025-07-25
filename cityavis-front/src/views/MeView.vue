<template>
  <div class="profile">
    <h1>Mon profil</h1>
    <p><strong>Email :</strong> {{ user.email }}</p>
    <p><strong>Rôle(s) :</strong> {{ user.roles.join(', ') }}</p>
  </div>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const user = auth.user
</script>

<style scoped>
.profile {
  max-width: 400px;
  margin: 2rem auto;
}
button {
  margin-top: 1rem;
  padding: 0.5rem 1rem;
}
</style>
