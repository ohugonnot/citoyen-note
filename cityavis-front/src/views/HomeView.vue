<template>
  <div class="container py-4">
    <header class="text-center mb-4">
      <h1 class="text-primary fw-bold">🏛️ CitoyenNote</h1>
      <p class="text-muted">Évaluez et améliorez vos services publics locaux</p>
    </header>

    <div class="row mb-4">
      <div class="col-lg-6 mb-3 mb-lg-0">
        <div class="card p-4 shadow-sm fade-in h-100">
          <NoteService />
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card p-4 shadow-sm fade-in h-100">
          <h5>🗺️ Cartographie des Services</h5>
          <MapLeafLet />
        </div>
      </div>
    </div>

    <div class="row text-center mb-4">
      <StatCard title="Évaluations totales" :value="allVotes.length" />
      <StatCard title="Note moyenne" :value="moyenne.toFixed(1)" />
      <StatCard title="Services" :value="Object.keys(notesParService).length" />
      <StatCard title="Croissance" value="+15%" />
    </div>

    <EvaluationsList :votes="allVotes" />
  </div>
</template>

<script setup>
import NoteService from '@/components/NoteService.vue'
import StatCard from '@/components/StatCard.vue'
import EvaluationsList from '@/components/EvaluationsList.vue'
import MapLeafLet from '@/components/MapLeafLet.vue'

import { ref, computed } from 'vue'
const allVotes = ref([])

// Pass props down or use provide/inject, or Pinia store for global state
const moyenne = computed(() => {
  if (!allVotes.value.length) return 0
  return allVotes.value.reduce((a, b) => a + b.note, 0) / allVotes.value.length
})

const notesParService = computed(() => {
  const grouped = {}
  for (const v of allVotes.value) {
    if (!grouped[v.service]) grouped[v.service] = []
    grouped[v.service].push(v)
  }
  return grouped
})
</script>
