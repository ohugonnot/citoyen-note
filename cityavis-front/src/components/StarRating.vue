<!-- components/StarRating.vue -->
<template>
  <div class="star-rating">
    <span v-for="star in maxStars" :key="star" class="star">
      <span class="star-bg">★</span>
      <span class="star-fill" :style="{ width: getStarFillWidth(star) + '%' }"> ★ </span>
    </span>
    <span v-if="showValue" class="rating-value">
      {{ rating.toFixed(1) }}
    </span>
  </div>
</template>

<script setup>
const props = defineProps({
  rating: { type: Number, default: 0 },
  maxStars: { type: Number, default: 5 },
  showValue: { type: Boolean, default: false },
})

const getStarFillWidth = (starIndex) => {
  const diff = props.rating - (starIndex - 1)
  return Math.max(0, Math.min(100, diff * 100))
}
</script>

<style scoped>
.star-rating {
  display: inline-flex;
  align-items: center;
  gap: 0.1rem;
}

.star {
  position: relative;
  font-size: 1rem;
  line-height: 1;
}

.star-bg {
  color: #e9ecef;
}

.star-fill {
  position: absolute;
  top: 0;
  left: 0;
  color: #ffc107;
  overflow: hidden;
}

.rating-value {
  margin-left: 0.25rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #6c757d;
}
</style>
