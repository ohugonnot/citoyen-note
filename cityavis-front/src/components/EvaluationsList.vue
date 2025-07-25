<template>
  <div v-if="votes.length" class="card p-4 mb-4 shadow-sm fade-in">
    <h5>🧾 Evaluations</h5>
    <ul class="list-group">
      <li v-for="vote in votes.slice().reverse()" :key="vote.timestamp" class="list-group-item">
        <strong>{{ vote.service }}</strong> — {{ vote.critere }}<br />
        <span>{{ emojis[vote.note - 1] }} ({{ vote.note }}/5)</span>
        <span v-if="vote.commentaire"> — "{{ vote.commentaire }}"</span><br />
        <small class="text-muted">{{ vote.date }} à Pau</small>
      </li>
    </ul>
  </div>
</template>

<script setup>
const props = defineProps({
  votes: {
    type: Array,
    default: () => [],
  },
})

const emojis = ['😡', '😕', '😐', '😊', '😍']
</script>
