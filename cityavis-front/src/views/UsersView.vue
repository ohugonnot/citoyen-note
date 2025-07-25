<template>
  <div>
    <h1>Utilisateurs</h1>
    <ul>
      <li v-for="user in users" :key="user.id">{{ user.email }} — {{ user.roles.join(', ') }}</li>
    </ul>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '@/axios'

const users = ref([])

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/users')
    users.value = data
  } catch (e) {
    console.error('Erreur en récupérant les utilisateurs', e)
  }
})
</script>
