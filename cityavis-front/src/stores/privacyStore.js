// stores/privacyStore.js
import { defineStore } from 'pinia'
import { ref } from 'vue'

export const usePrivacyStore = defineStore('privacy', () => {
  const showPolicyModal = ref(false)
  const currentSection = ref('general')

  const sections = ref([
    { id: 'general', label: 'Informations générales', icon: 'bi-info-circle' },
    { id: 'data', label: 'Données collectées', icon: 'bi-database' },
    { id: 'purposes', label: 'Finalités', icon: 'bi-target' },
    { id: 'retention', label: 'Conservation', icon: 'bi-clock-history' },
    { id: 'rights', label: 'Vos droits', icon: 'bi-shield-check' },
    { id: 'cookies', label: 'Cookies', icon: 'bi-gear' },
    { id: 'contact', label: 'Contact', icon: 'bi-envelope' },
  ])

  const openPolicy = (section = 'general') => {
    currentSection.value = section
    showPolicyModal.value = true
  }

  const closePolicy = () => {
    showPolicyModal.value = false
  }

  const goToSection = (sectionId) => {
    currentSection.value = sectionId
  }

  return {
    showPolicyModal,
    currentSection,
    sections,
    openPolicy,
    closePolicy,
    goToSection,
  }
})
