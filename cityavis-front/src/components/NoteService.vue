<template>
  <div>
    <h5 class="mb-3">📝 Notez un Service Public</h5>

    <div class="row g-3">
      <div class="col-12">
        <label>Type</label>
        <select v-model="selectedType" class="form-select">
          <option disabled value="">Sélectionnez un type</option>
          <option v-for="t in types" :key="t">{{ t }}</option>
        </select>
      </div>
      <div class="col-12">
        <label>Service</label>
        <select v-model="selectedService" class="form-select">
          <option disabled value="">Sélectionnez un service</option>
          <option v-for="s in services[selectedType] || []" :key="s">{{ s }}</option>
        </select>
      </div>
      <div class="col-12">
        <label>Critère</label>
        <select v-model="selectedCritere" class="form-select">
          <option disabled value="">Critère d'évaluation</option>
          <option v-for="c in criteres" :key="c">{{ c }}</option>
        </select>
      </div>
    </div>

    <div class="mt-3">
      <label>Votre évaluation</label><br />
      <span
        v-for="(emoji, index) in emojis"
        :key="index"
        class="emoji me-2"
        :class="{ selected: note === index + 1 }"
        @click="animateVote(index + 1)"
        >{{ emoji }}</span
      >
    </div>

    <div class="mt-3">
      <label>Commentaire (optionnel)</label>
      <textarea v-model="commentaire" class="form-control" rows="2"></textarea>
    </div>

    <div class="mt-3">
      <button class="btn btn-primary w-100" :disabled="!canSubmit" @click="submitNote">
        Envoyer ({{ note }}/5)
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const types = ['Mairie', 'Santé', 'Social']
const services = {
  Mairie: ['Mairie de Pau', 'Annexe de Billère'],
  Santé: ['Hôpital général', 'Centre COVID'],
  Social: ['CAF', 'Mission Locale'],
}
const criteres = ['Accueil', 'Rapidité', 'Efficacité']
const emojis = ['😡', '😕', '😐', '😊', '😍']

const selectedType = ref('')
const selectedService = ref('')
const selectedCritere = ref('')
const note = ref(null)
const commentaire = ref('')

const allVotes = ref([]) // Optionnel : émettre vers parent ou utiliser store

const canSubmit = computed(() => {
  return selectedType.value && selectedService.value && selectedCritere.value && note.value
})

function submitNote() {
  const newVote = {
    type: selectedType.value,
    service: selectedService.value,
    critere: selectedCritere.value,
    note: note.value,
    commentaire: commentaire.value.trim(),
    date: new Date().toLocaleString(),
    timestamp: Date.now(),
  }
  allVotes.value.push(newVote)

  // Réinitialiser
  selectedType.value = ''
  selectedService.value = ''
  selectedCritere.value = ''
  note.value = null
  commentaire.value = ''
}

function animateVote(value) {
  note.value = value
}
</script>

<style scoped>
.emoji {
  font-size: 2rem;
  cursor: pointer;
  transition:
    transform 0.2s,
    filter 0.2s;
  display: inline-block;
}
.emoji:hover {
  transform: scale(1.3);
  filter: brightness(1.2);
}
.emoji.selected {
  transform: scale(1.5);
  filter: drop-shadow(0 0 4px #0d6efd);
}
</style>
