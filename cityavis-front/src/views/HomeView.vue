<template>
  <div class="container-fluid px-2 px-md-4 py-2 py-md-4">
    <div class="row mb-3 mb-md-4 g-2 g-md-3">
      <div class="col-lg-6 mb-2 mb-lg-0">
        <div class="card p-2 p-md-4 shadow-sm fade-in h-100">
          <NoteService />
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card p-2 p-md-4 shadow-sm fade-in h-100">
          <h5 class="fs-6 fs-md-5 mb-2 mb-md-3">🗺️ Cartographie des Services</h5>
          <MapLeafLet />
        </div>
      </div>
    </div>

    <div class="row text-center mb-3 mb-md-4 g-2 g-md-3">
      <StatCard title="Évaluations totales" :value="allVotes.length" />
      <StatCard title="Note moyenne" :value="moyenne.toFixed(1)" />
      <StatCard title="Services" :value="Object.keys(notesParService).length" />
      <StatCard title="Croissance" value="+15%" />
    </div>

    <div class="px-1 px-md-0">
      <EvaluationsList :votes="allVotes" />
    </div>
  </div>
</template>

<script setup>
import NoteService from '@/components/NoteService.vue'
import StatCard from '@/components/StatCard.vue'
import EvaluationsList from '@/components/EvaluationsList.vue'
import MapLeafLet from '@/components/MapLeafLet.vue'

import { ref, computed } from 'vue'
const allVotes = ref([])

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

<style scoped>
/* Ajustements pour mobile */
@media (max-width: 768px) {
  .card {
    border-radius: 0.5rem !important;
  }

  .container-fluid {
    max-width: 100vw;
    overflow-x: hidden;
  }
}

/* Animation fade-in optimisée */
.fade-in {
  animation: fadeIn 0.6s ease-in-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
