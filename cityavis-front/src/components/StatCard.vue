<!-- StatCard.vue -->
<template>
  <div class="col-6 col-md-3 mb-2 mb-md-0">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-body text-center p-2 p-md-3">
        <h3 class="card-title fs-5 fs-md-4 fw-bold text-primary mb-1 mb-md-2">
          {{ value }}
        </h3>
        <p class="card-text text-muted small mb-0">
          {{ title }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: String,
  value: [String, Number]
})
</script>

<style scoped>
@media (max-width: 768px) {
  .card-body {
    padding: 0.75rem !important;
  }

  .card-title {
    font-size: 1.1rem !important;
  }

  .card-text {
    font-size: 0.75rem !important;
  }
}
</style>
