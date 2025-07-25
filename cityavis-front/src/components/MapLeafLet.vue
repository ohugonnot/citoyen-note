<template>
  <div id="map" style="height: 300px; border-radius: 10px"></div>
</template>

<script setup>
import { onMounted } from 'vue'
import L from 'leaflet'

onMounted(() => {
  const map = L.map('map').setView([43.2951, -0.3708], 13)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
  }).addTo(map)

  const markers = [
    { name: 'Mairie de Pau', lat: 43.2951, lon: -0.3708 },
    { name: 'Hôpital général', lat: 43.3, lon: -0.36 },
    { name: 'CAF', lat: 43.29, lon: -0.38 },
  ]

  markers.forEach((m) => {
    L.marker([m.lat, m.lon]).addTo(map).bindPopup(m.name)
  })
})
</script>
