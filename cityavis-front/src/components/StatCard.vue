<template>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <h4>{{ value }}</h4>
      <p>{{ title }}</p>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: String,
  value: [String, Number],
})
</script>

<style scoped>
.stat-card {
  background: white;
  border-radius: 10px;
  padding: 1rem;
  box-shadow: 0 2px 6px rgb(0 0 0 / 0.1);
}
</style>
