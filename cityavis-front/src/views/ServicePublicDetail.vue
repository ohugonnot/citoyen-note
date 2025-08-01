<template>
  <div v-cloak>
    <!-- Service non trouvé -->
    <div v-show="!service && !isLoading" class="container py-5">
      <div class="row justify-content-center">
        <div class="col-md-6">
          <div class="card border-0 shadow text-center py-4">
            <div class="card-body">
              <i class="bi bi-exclamation-triangle text-warning mb-3" style="font-size: 3rem"></i>
              <h4 class="fw-bold mb-3">Service non trouvé</h4>
              <p class="text-muted mb-4">Ce service n'existe plus ou l'adresse est incorrecte.</p>
              <router-link :to="{ name: 'Home' }" class="btn btn-primary">
                <i class="bi bi-arrow-left me-2"></i>Retour à l'accueil
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Contenu principal -->
    <div v-show="service">
      <!-- En-tête minimaliste -->
      <div class="bg-light border-bottom py-4">
        <div class="container">
          <!-- Breadcrumb -->
          <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb mb-0 small">
              <li class="breadcrumb-item">
                <router-link :to="{ name: 'Home' }" class="text-decoration-none">
                  <i class="bi bi-house me-1"></i>Accueil
                </router-link>
              </li>
              <li class="breadcrumb-item text-muted">{{ service?.categorie.nom }}</li>
              <li class="breadcrumb-item active" aria-current="page">{{ service?.nom }}</li>
            </ol>
          </nav>

          <!-- Titre et infos principales -->
          <div class="service-header py-1">
            <div class="container-fluid">
              <div class="row align-items-start g-3">
                <!-- Info service -->
                <div class="col-12 col-md">
                  <div class="d-flex align-items-start">
                    <!-- Icône service -->
                    <div
                      class="service-icon p-2 rounded me-3 flex-shrink-0"
                      :style="{
                        backgroundColor: service?.categorie.couleur + '20',
                        border: `1px solid ${service?.categorie.couleur}50`,
                      }"
                    >
                      <i
                        :class="`bi ${service?.categorie.icone}`"
                        :style="{ color: service?.categorie.couleur }"
                        class="fs-4"
                      ></i>
                    </div>

                    <!-- Infos textuelles -->
                    <div class="flex-grow-1 min-width-0">
                      <h1 class="service-title h3 fw-bold mb-2">{{ service?.nom }}</h1>

                      <!-- Infos service -->
                      <div
                        class="service-meta d-flex flex-column flex-sm-row gap-1 gap-sm-3 text-muted small"
                      >
                        <!-- Localisation -->
                        <span class="meta-item">
                          <i class="bi bi-geo-alt me-1"></i>
                          {{ service?.ville }}
                        </span>

                        <!-- Statut ouverture -->
                        <span v-if="currentStatus" class="meta-item">
                          <i
                            :class="
                              currentStatus.isOpen
                                ? 'bi-circle-fill text-success'
                                : 'bi-circle-fill text-danger'
                            "
                            class="me-1"
                            style="font-size: 0.7rem"
                          ></i>
                          {{ currentStatus.isOpen ? 'Ouvert' : 'Fermé' }}
                          <span v-if="currentStatus.nextChange" class="ms-1">
                            · {{ currentStatus.nextChange }}
                          </span>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Actions Desktop -->
                <div class="col-auto d-none d-md-flex">
                  <div class="d-flex align-items-center gap-2">
                    <!-- Bouton favoris -->
                    <button
                      class="btn btn-outline-secondary btn-sm"
                      :class="{ 'btn-danger': isFavorite, 'text-danger': isFavorite }"
                      :title="isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris'"
                      @click="toggleFavorite"
                    >
                      <i :class="isFavorite ? 'bi bi-heart-fill' : 'bi bi-heart'"></i>
                      <span class="ms-1 d-none d-lg-inline">
                        {{ isFavorite ? 'Favori' : 'Favoris' }}
                      </span>
                    </button>

                    <!-- Dropdown partage -->
                    <div class="dropdown">
                      <button
                        class="btn btn-outline-secondary btn-sm dropdown-toggle"
                        type="button"
                        data-bs-toggle="dropdown"
                        title="Partager ce service"
                      >
                        <i class="bi bi-share me-1"></i>
                        <span class="d-none d-lg-inline">Partager</span>
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                          <a class="dropdown-item" href="#" @click="shareToFacebook">
                            <i class="bi bi-facebook text-primary me-2"></i>
                            Facebook
                          </a>
                        </li>
                        <li>
                          <a class="dropdown-item" href="#" @click="shareToTwitter">
                            <i class="bi bi-twitter text-info me-2"></i>
                            Twitter
                          </a>
                        </li>
                        <li>
                          <a class="dropdown-item" href="#" @click="shareToLinkedIn">
                            <i class="bi bi-linkedin text-primary me-2"></i>
                            LinkedIn
                          </a>
                        </li>
                        <li><hr class="dropdown-divider" /></li>
                        <li>
                          <a class="dropdown-item" href="#" @click="copyLink">
                            <i class="bi bi-link-45deg me-2"></i>
                            Copier le lien
                          </a>
                        </li>
                      </ul>
                    </div>

                    <!-- Bouton retour desktop -->
                    <router-link
                      :to="{ name: 'Home' }"
                      class="btn btn-outline-secondary btn-sm ms-2"
                    >
                      <i class="bi bi-arrow-left me-1"></i>
                      Retour
                    </router-link>
                  </div>
                </div>

                <!-- Actions Mobile -->
                <div class="col-12 d-md-none order-first">
                  <div class="d-flex justify-content-between align-items-center">
                    <!-- Bouton retour mobile -->
                    <router-link
                      :to="{ name: 'Home' }"
                      class="btn btn-outline-secondary btn-sm"
                      title="Retour à l'accueil"
                    >
                      <i class="bi bi-arrow-left me-1"></i>
                      <span class="d-none d-sm-inline">Retour</span>
                    </router-link>

                    <!-- Actions mobile -->
                    <div class="d-flex gap-1">
                      <!-- Bouton favoris mobile -->
                      <button
                        class="btn btn-link btn-sm p-2"
                        :class="{ 'text-danger': isFavorite, 'text-secondary': !isFavorite }"
                        :title="isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris'"
                        @click="toggleFavorite"
                      >
                        <i
                          :class="isFavorite ? 'bi bi-heart-fill' : 'bi bi-heart'"
                          class="fs-5"
                        ></i>
                      </button>

                      <!-- Dropdown partage mobile -->
                      <div class="dropdown">
                        <button
                          class="btn btn-link btn-sm text-secondary p-2"
                          type="button"
                          data-bs-toggle="dropdown"
                          title="Partager ce service"
                        >
                          <i class="bi bi-share fs-5"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                          <!-- Partage natif mobile -->
                          <li v-if="hasNativeShare">
                            <a
                              class="dropdown-item fw-bold text-primary"
                              href="#"
                              @click="shareNative"
                            >
                              <i class="bi bi-phone me-2"></i>
                              Partager via...
                            </a>
                          </li>
                          <li v-if="hasNativeShare"><hr class="dropdown-divider" /></li>

                          <!-- Options classiques -->
                          <li>
                            <a class="dropdown-item" href="#" @click="shareToFacebook">
                              <i class="bi bi-facebook text-primary me-2"></i>
                              Facebook
                            </a>
                          </li>
                          <li>
                            <a class="dropdown-item" href="#" @click="shareToTwitter">
                              <i class="bi bi-twitter text-info me-2"></i>
                              Twitter
                            </a>
                          </li>
                          <li>
                            <a class="dropdown-item" href="#" @click="shareToLinkedIn">
                              <i class="bi bi-linkedin text-primary me-2"></i>
                              LinkedIn
                            </a>
                          </li>
                          <li><hr class="dropdown-divider" /></li>
                          <li>
                            <a class="dropdown-item" href="#" @click="copyLink">
                              <i class="bi bi-link-45deg me-2"></i>
                              Copier le lien
                            </a>
                          </li>
                        </ul>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Contenu principal -->
      <div class="container py-4">
        <div class="row g-4">
          <!-- Colonne principale - Avis (Gauche) -->
          <div class="col-12 col-lg-8">
            <!-- Carte -->
            <div v-show="service?.coordinates" class="card border-0 shadow-sm">
              <div class="card-body p-0">
                <div class="p-3 border-bottom">
                  <h3 class="h6 fw-bold mb-0 text-primary">
                    <i class="bi bi-map me-1"></i>Localisation
                  </h3>
                </div>
                <div ref="mapContainer" style="height: 350px; width: 100%"></div>
              </div>
            </div>

            <!-- Section avis -->
            <div class="card border-0 shadow-sm">
              <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-4">
                  <h2 class="h4 fw-bold mb-0">
                    <i class="bi bi-star me-2 text-warning"></i>Avis et évaluations
                  </h2>
                  <button class="btn btn-primary btn-sm" @click="openReviewModal">
                    <i class="bi bi-plus me-1"></i>
                    <span class="d-none d-sm-inline">Laisser un avis</span>
                    <span class="d-sm-none">Avis</span>
                  </button>
                </div>

                <!-- Stats d'évaluation -->
                <div v-if="evaluations.total > 0">
                  <!-- Note moyenne compacte -->
                  <div class="row align-items-center mb-4 p-3 bg-light rounded">
                    <div class="col-auto">
                      <div class="text-center">
                        <div class="fs-2 fw-bold text-primary mb-1">
                          {{ evaluations.moyenne.toFixed(1) }}
                        </div>
                        <div class="star-rating justify-content-center mb-1">
                          <i
                            v-for="i in 5"
                            :key="i"
                            :class="[
                              'bi me-1',
                              i <= Math.round(evaluations.moyenne)
                                ? 'bi-star-fill text-warning'
                                : 'bi-star text-muted',
                            ]"
                            style="font-size: 1.1rem"
                          ></i>
                        </div>
                        <small class="text-muted">{{ evaluations.total }} avis</small>
                      </div>
                    </div>
                    <div class="col">
                      <div class="ms-3">
                        <div
                          v-for="note in [5, 4, 3, 2, 1]"
                          :key="note"
                          class="d-flex align-items-center mb-1"
                        >
                          <span class="me-2 small fw-medium" style="width: 15px">{{ note }}</span>
                          <div class="progress flex-grow-1 me-2" style="height: 6px">
                            <div
                              class="progress-bar bg-warning"
                              :style="{
                                width: (evaluations.repartition[note]?.percentage ?? 0) + '%',
                              }"
                            ></div>
                          </div>
                          <span class="text-muted small" style="width: 20px">
                            {{ evaluations.repartition[note]?.count || 0 }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Liste des avis -->
                  <div v-if="evaluations.liste.length > 0">
                    <h5 class="fw-bold mb-3">Avis récents</h5>
                    <div class="max-height-400 overflow-auto">
                      <div
                        v-for="avis in evaluations.liste"
                        :key="avis.id"
                        class="border-bottom pb-3 mb-3 last-child-no-border"
                      >
                        <div class="d-flex">
                          <div class="flex-grow-1">
                            <div class="d-flex align-items-start justify-content-between mb-1">
                              <div>
                                <h6 class="fw-semibold mb-0 small">
                                  {{ avis.pseudo || 'Utilisateur anonyme' }}
                                </h6>
                                <div class="star-rating mb-1">
                                  <i
                                    v-for="i in 5"
                                    :key="i"
                                    :class="[
                                      'bi me-1',
                                      i <= avis.note
                                        ? 'bi-star-fill text-warning'
                                        : 'bi-star text-muted',
                                    ]"
                                    style="font-size: 0.85rem"
                                  ></i>
                                </div>
                              </div>
                              <small class="text-muted">
                                {{ formatDate(avis.createdAt) }}
                              </small>
                            </div>
                            <p v-if="avis.commentaire" class="mb-0 text-muted small">
                              {{ avis.commentaire }}
                            </p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Aucun avis -->
                <div v-else class="text-center py-5">
                  <i class="bi bi-star text-muted mb-3" style="font-size: 3rem"></i>
                  <h5 class="fw-bold mb-2">Aucun avis pour le moment</h5>
                  <p class="text-muted mb-3">Soyez le premier à partager votre expérience !</p>
                  <button class="btn btn-primary" @click="openReviewModal">
                    <i class="bi bi-star me-1"></i>Premier avis
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Colonne latérale - Informations (Droite) -->
          <div class="col-12 col-lg-4">
            <!-- Description -->
            <div class="card border-0 shadow-sm mb-3">
              <div class="card-body p-3">
                <h3 class="h6 fw-bold mb-2 text-primary">
                  <i class="bi bi-info-circle me-1"></i>Description
                </h3>
                <p class="mb-0 text-muted small">{{ service?.description }}</p>
              </div>
            </div>

            <!-- Contact -->
            <div class="card border-0 shadow-sm mb-3">
              <div class="card-body p-3">
                <h3 class="h6 fw-bold mb-3 text-primary">
                  <i class="bi bi-telephone me-1"></i>Contact
                </h3>

                <!-- Adresse -->
                <div class="mb-3">
                  <div class="d-flex align-items-start">
                    <i class="bi bi-geo-alt text-primary me-2 mt-1"></i>
                    <div class="flex-grow-1">
                      <div class="fw-medium small">{{ service?.adresse }}</div>
                      <div class="text-muted small">
                        {{ service?.code_postal }} {{ service?.ville }}
                      </div>
                      <a
                        :href="`https://maps.google.com/?q=${encodeURIComponent(service?.adresse + ' ' + service?.code_postal + ' ' + service?.ville)}`"
                        target="_blank"
                        class="btn btn-outline-primary btn-sm mt-2"
                      >
                        <i class="bi bi-map me-1"></i>Itinéraire
                      </a>
                    </div>
                  </div>
                </div>

                <!-- Téléphone -->
                <div v-if="service?.telephone" class="mb-3">
                  <div class="d-flex align-items-center">
                    <i class="bi bi-telephone text-success me-2"></i>
                    <div class="flex-grow-1">
                      <a
                        :href="`tel:${service?.telephone}`"
                        class="text-decoration-none fw-medium text-success"
                      >
                        {{ service?.telephone }}
                      </a>
                    </div>
                  </div>
                </div>

                <!-- Email -->
                <div v-if="service?.email">
                  <div class="d-flex align-items-center">
                    <i class="bi bi-envelope text-info me-2"></i>
                    <div class="flex-grow-1">
                      <a
                        :href="`mailto:${service?.email}`"
                        class="text-decoration-none fw-medium text-info small"
                      >
                        {{ service?.email }}
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Horaires -->
            <div v-if="service?.horaires_ouverture" class="card border-0 shadow-sm mb-3">
              <div class="card-body p-3">
                <h3 class="h6 fw-bold mb-3 text-primary">
                  <i class="bi bi-clock me-1"></i>Horaires d'ouverture
                </h3>

                <div class="d-flex flex-column gap-2">
                  <div
                    v-for="(horaire, jour) in service?.horaires_ouverture"
                    :key="jour"
                    class="d-flex justify-content-between align-items-center py-2 px-3 rounded"
                    :class="{
                      'bg-light': !horaire.ouvert && !isToday(jour),
                      'bg-success bg-opacity-10 border border-success':
                        horaire.ouvert && isToday(jour),
                      'bg-danger bg-opacity-10 border border-danger':
                        !horaire.ouvert && isToday(jour),
                      border: isToday(jour),
                    }"
                  >
                    <div class="fw-medium small">
                      {{ formatDayName(jour) }}
                      <span
                        v-if="isToday(jour)"
                        class="badge bg-primary ms-1"
                        style="font-size: 0.65rem"
                      >
                        Aujourd'hui
                      </span>
                    </div>
                    <div class="text-end">
                      <span v-if="!horaire.ouvert" class="text-muted small fw-medium">Fermé</span>
                      <div v-else>
                        <div
                          v-for="creneau in horaire.creneaux"
                          :key="creneau.ouverture"
                          class="fw-medium small"
                          :class="isToday(jour) ? 'text-success' : 'text-dark'"
                        >
                          {{ creneau.ouverture }} - {{ creneau.fermeture }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal avis -->
    <div
      v-if="showReviewModal"
      class="modal fade show d-block"
      tabindex="-1"
      style="background-color: rgba(0, 0, 0, 0.6)"
    >
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
          <!-- Modal connecté -->
          <div v-if="isAuthenticated">
            <div class="modal-header border-0">
              <h5 class="modal-title fw-bold">
                <i class="bi bi-star me-2 text-warning"></i>Laisser un avis
              </h5>
              <button type="button" class="btn-close" @click="showReviewModal = false"></button>
            </div>
            <div class="modal-body">
              <!-- Rating -->
              <div class="text-center mb-4">
                <label class="form-label fw-bold mb-3">Votre note</label>
                <div class="star-rating justify-content-center mb-2">
                  <i
                    v-for="i in 5"
                    :key="i"
                    :class="[
                      'bi me-2',
                      i <= (hoverRating || newReview.rating)
                        ? 'bi-star-fill text-warning'
                        : 'bi-star text-muted',
                    ]"
                    style="cursor: pointer; font-size: 2rem"
                    @click="newReview.rating = i"
                    @mouseover="hoverRating = i"
                    @mouseleave="hoverRating = 0"
                  ></i>
                </div>
                <small class="text-muted">
                  {{
                    newReview.rating === 0
                      ? 'Cliquez sur les étoiles'
                      : `${newReview.rating}/5 étoiles`
                  }}
                </small>
              </div>

              <!-- Commentaire -->
              <div>
                <label class="form-label fw-bold">Commentaire (optionnel)</label>
                <textarea
                  v-model="newReview.comment"
                  class="form-control"
                  rows="3"
                  placeholder="Partagez votre expérience..."
                  maxlength="500"
                ></textarea>
                <div class="form-text text-end">{{ newReview.comment.length }}/500</div>
              </div>
            </div>
            <div class="modal-footer border-0">
              <button class="btn btn-outline-secondary" @click="showReviewModal = false">
                Annuler
              </button>
              <button
                class="btn btn-primary"
                :disabled="newReview.rating === 0"
                @click="submitReview"
              >
                <i class="bi bi-send me-1"></i>Publier
              </button>
            </div>
          </div>

          <!-- Modal non connecté -->
          <div v-else>
            <div class="modal-header border-0">
              <h5 class="modal-title fw-bold">Laisser un avis</h5>
              <button type="button" class="btn-close" @click="showReviewModal = false"></button>
            </div>
            <div v-if="showLoginRegister" class="modal-body text-center py-4">
              <i class="bi bi-person-circle text-primary mb-3" style="font-size: 3rem"></i>
              <h5 class="fw-bold mb-3">Connectez-vous pour laisser un avis</h5>
              <p class="text-muted mb-4">Votre avis aide la communauté à mieux choisir.</p>
              <div class="d-grid gap-2">
                <router-link :to="{ name: 'Login' }" class="btn btn-primary">
                  <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
                </router-link>
                <router-link :to="{ name: 'Register' }" class="btn btn-outline-primary">
                  <i class="bi bi-person-plus me-2"></i>Créer un compte
                </router-link>
                <hr class="my-2" />
                <button class="btn btn-warning" @click="showLoginRegister = false">
                  <i class="bi bi-incognito me-2"></i>Continuer sans compte
                </button>
              </div>
            </div>
            <!-- Formulaire anonyme -->
            <template v-else>
              <div class="modal-body">
                <div class="alert alert-info d-flex align-items-center mb-3">
                  <i class="bi bi-info-circle me-2"></i>
                  <small>Vous postez en tant qu'utilisateur anonyme</small>
                </div>

                <!-- Rating -->
                <div class="text-center mb-4">
                  <label class="form-label fw-bold mb-3">Votre note</label>
                  <div class="star-rating justify-content-center mb-2">
                    <i
                      v-for="i in 5"
                      :key="i"
                      :class="[
                        'bi me-2',
                        i <= (hoverRating || newReview.rating)
                          ? 'bi-star-fill text-warning'
                          : 'bi-star text-muted',
                      ]"
                      style="cursor: pointer; font-size: 2rem"
                      @click="newReview.rating = i"
                      @mouseover="hoverRating = i"
                      @mouseleave="hoverRating = 0"
                    ></i>
                  </div>
                  <small class="text-muted">
                    {{
                      newReview.rating === 0
                        ? 'Cliquez sur les étoiles'
                        : `${newReview.rating}/5 étoiles`
                    }}
                  </small>
                </div>

                <!-- Commentaire -->
                <div class="mb-3">
                  <label class="form-label fw-bold">Commentaire (optionnel)</label>
                  <textarea
                    v-model="newReview.comment"
                    class="form-control"
                    rows="3"
                    placeholder="Partagez votre expérience..."
                    maxlength="500"
                  ></textarea>
                  <div class="form-text text-end">{{ newReview.comment.length }}/500</div>
                </div>

                <!-- Nom optionnel pour anonyme -->
                <div>
                  <label class="form-label fw-bold">Nom d'affichage (optionnel)</label>
                  <input
                    v-model="newReview.anonymousName"
                    type="text"
                    class="form-control"
                    placeholder="Ex: Client satisfait"
                    maxlength="50"
                  />
                  <div class="form-text">Laissez vide pour "Utilisateur anonyme"</div>
                </div>
              </div>
              <div class="modal-footer border-0">
                <button class="btn btn-outline-secondary" @click="showLoginRegister = true">
                  <i class="bi bi-arrow-left me-1"></i>Retour
                </button>
                <button
                  class="btn btn-primary"
                  :disabled="newReview.rating === 0"
                  @click="submitAnonymousReview"
                >
                  <i class="bi bi-send me-1"></i>Publier
                </button>
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>
    <Toast />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch, computed, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useServicePublicStore } from '@/stores/servicePublicStore'
import { useAuthStore } from '@/stores/authStore'
import { storeToRefs } from 'pinia'
import { useToast } from 'primevue/usetoast'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const serviceStore = useServicePublicStore()
const authStore = useAuthStore()
const { currentService, isLoading } = storeToRefs(serviceStore)
const { isAuthenticated, user } = storeToRefs(authStore)

const service = computed(() => currentService.value?.service || null)
const evaluations = computed(() => {
  const raw = currentService.value?.evaluations
  if (!raw) {
    return { moyenne: 0, total: 0, repartition: [], liste: [] }
  }

  return {
    moyenne: raw.moyenne,
    total: raw.total,
    repartition: raw.repartition,
    liste: raw.liste.filter((e) => e.service != null),
  }
})

// État actuel du service (ouvert/fermé)
const currentStatus = computed(() => {
  if (!service.value?.horaires_ouverture) return null

  const now = new Date()
  const currentDay = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'][
    now.getDay()
  ]
  const currentTime = now.getHours() * 100 + now.getMinutes()

  const todaySchedule = service.value.horaires_ouverture[currentDay]
  if (!todaySchedule || !todaySchedule.ouvert) {
    return { isOpen: false, nextChange: findNextOpening() }
  }

  // Vérifier si actuellement ouvert
  const isCurrentlyOpen = todaySchedule.creneaux.some((creneau) => {
    const [openHour, openMin] = creneau.ouverture.split(':').map(Number)
    const [closeHour, closeMin] = creneau.fermeture.split(':').map(Number)
    const openTime = openHour * 100 + openMin
    const closeTime = closeHour * 100 + closeMin
    return currentTime >= openTime && currentTime <= closeTime
  })

  return {
    isOpen: isCurrentlyOpen,
    nextChange: isCurrentlyOpen ? findNextClosing() : findNextOpening(),
  }
})

const mapContainer = ref(null)
const map = ref(null)
const showReviewModal = ref(false)
const showLoginRegister = ref(false)
const hoverRating = ref(0)
const submittingReview = ref(false)
const isFavorite = ref(false)
const newReview = ref({
  rating: 0,
  comment: '',
  anonymousName: '',
})

// Computed
const hasNativeShare = computed(() => {
  return typeof navigator !== 'undefined' && navigator.share
})

const loadData = async () => {
  try {
    isLoading.value = true
    await serviceStore.fetchServiceBySlug(route.params.slug)

    if (!service.value) {
      console.warn(`Service introuvable: ${route.params.slug}`)
      return
    }

    if (mapContainer.value && service.value?.coordinates) {
      await nextTick()
      initMap()
    }
  } catch (err) {
    console.error('Erreur lors du chargement:', err)
  } finally {
    isLoading.value = false
  }
}

// Ouvrir le modal d'avis
const openReviewModal = () => {
  // Si pas connecté, proposer directement l'option anonyme
  showReviewModal.value = true
  if (!isAuthenticated.value) {
    showLoginRegister.value = true
  }

  newReview.value = { rating: 0, comment: '', anonymousName: '' }
  hoverRating.value = 0
}

// Soumettre un avis (utilisateur connecté)
const submitReview = async () => {
  if (newReview.value.rating === 0) {
    toast.add({
      severity: 'warn',
      summary: 'Attention',
      detail: 'Veuillez attribuer une note',
      life: 3000,
    })
    return
  }

  if (!isAuthenticated.value) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Vous devez être connecté pour laisser un avis',
      life: 3000,
    })
    return
  }

  submittingReview.value = true

  try {
    await serviceStore.submitEvaluation({
      serviceId: service.value.id,
      note: newReview.value.rating,
      commentaire: newReview.value.comment.trim(),
      anonyme: false,
    })

    toast.add({
      severity: 'success',
      summary: 'Merci !',
      detail: 'Votre avis a été publié avec succès',
      life: 4000,
    })

    // Réinitialiser et fermer
    closeReviewModal()

    // Recharger les données pour afficher le nouvel avis
    await serviceStore.fetchServiceBySlug(route.params.slug)
  } catch (err) {
    console.error("Erreur lors de l'envoi de l'avis:", err)

    const errorMessage =
      err.response?.data?.message ||
      err.response?.data?.error ||
      "Erreur lors de l'envoi de votre avis"

    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: errorMessage,
      life: 5000,
    })
  } finally {
    submittingReview.value = false
  }
}

// Soumettre un avis anonyme
const submitAnonymousReview = async () => {
  if (newReview.value.rating === 0) {
    toast.add({
      severity: 'warn',
      summary: 'Attention',
      detail: 'Veuillez attribuer une note',
      life: 3000,
    })
    return
  }

  // Validation du nom anonyme
  const anonymousName = newReview.value.anonymousName.trim()
  submittingReview.value = true

  try {
    await serviceStore.submitEvaluation({
      serviceId: service.value.id,
      note: newReview.value.rating,
      commentaire: newReview.value.comment.trim(),
      anonyme: true,
      nomAnonyme: anonymousName,
    })

    toast.add({
      severity: 'success',
      summary: 'Merci !',
      detail: 'Votre avis anonyme a été publié avec succès',
      life: 4000,
    })

    // Réinitialiser et fermer
    closeReviewModal()

    // Recharger les données
    await serviceStore.fetchServiceBySlug(route.params.slug)
  } catch (err) {
    console.error("Erreur lors de l'envoi de l'avis anonyme:", err)

    const errorMessage =
      err.response?.data?.message ||
      err.response?.data?.error ||
      "Erreur lors de l'envoi de votre avis"

    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: errorMessage,
      life: 5000,
    })
  } finally {
    submittingReview.value = false
  }
}

// Fermer le modal et réinitialiser
const closeReviewModal = () => {
  showReviewModal.value = false
  showLoginRegister.value = false
  newReview.value = { rating: 0, comment: '', anonymousName: '' }
  hoverRating.value = 0
}

const initMap = async () => {
  try {
    if (!service.value?.coordinates || !mapContainer.value) return

    if (map.value) {
      map.value.remove()
    }

    const { latitude, longitude } = service.value.coordinates

    map.value = L.map(mapContainer.value).setView([latitude, longitude], 15)

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap',
      maxZoom: 18,
    }).addTo(map.value)

    const customIcon = L.divIcon({
      html: `<div class="custom-marker">
               <i class="bi ${service.value.categorie.icone}"></i>
             </div>`,
      className: 'custom-marker-container',
      iconSize: [30, 30],
      iconAnchor: [15, 30],
    })

    L.marker([latitude, longitude], {
      icon: customIcon,
    }).addTo(map.value)
  } catch (error) {
    console.error("Erreur lors de l'initialisation de la carte:", error)
  }
}

const formatDayName = (dayName) => {
  const days = {
    lundi: 'Lundi',
    mardi: 'Mardi',
    mercredi: 'Mercredi',
    jeudi: 'Jeudi',
    vendredi: 'Vendredi',
    samedi: 'Samedi',
    dimanche: 'Dimanche',
  }
  return days[dayName] || dayName
}

const isToday = (dayName) => {
  const today = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'][
    new Date().getDay()
  ]
  return dayName === today
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
  })
}

const findNextOpening = () => {
  // Logique simplifiée - à implémenter selon vos besoins
  return 'Demain 08:00'
}

const findNextClosing = () => {
  // Logique simplifiée - à implémenter selon vos besoins
  return 'Ferme à 14:00'
}

const toggleFavorite = () => {
  isFavorite.value = !isFavorite.value
  // TODO: Sauvegarder en base/localStorage
  console.log('Favori:', isFavorite.value)
}

// Partage social
const shareToFacebook = () => {
  const url = encodeURIComponent(window.location.href)
  window.open(
    `https://www.facebook.com/sharer/sharer.php?u=${url}`,
    '_blank',
    'width=600,height=400',
  )
}

const shareToTwitter = () => {
  const url = encodeURIComponent(window.location.href)
  const text = encodeURIComponent(
    `Découvrez le service ${service.value.nom} à ${service.value.ville}`,
  )
  window.open(
    `https://twitter.com/intent/tweet?url=${url}&text=${text}`,
    '_blank',
    'width=600,height=400',
  )
}

const shareToLinkedIn = () => {
  const url = encodeURIComponent(window.location.href)
  window.open(
    `https://www.linkedin.com/sharing/share-offsite/?url=${url}`,
    '_blank',
    'width=600,height=400',
  )
}

const copyLink = async () => {
  try {
    await navigator.clipboard.writeText(window.location.href)
    toast.add({
      severity: 'success',
      summary: 'Succès',
      detail: 'Lien copié dans le presse-papier !',
      life: 3000,
    })
  } catch (err) {
    // Fallback pour navigateurs anciens
    const textArea = document.createElement('textarea')
    textArea.value = window.location.href
    document.body.appendChild(textArea)
    textArea.select()
    document.execCommand('copy')
    document.body.removeChild(textArea)

    toast.add({
      severity: 'success',
      summary: 'Succès',
      detail: 'Lien copié dans le presse-papier !',
      life: 3000,
    })
  }
}

// Partage natif mobile
const shareNative = async () => {
  if (navigator.share) {
    try {
      await navigator.share({
        title: `Service ${service.value.nom}`,
        text: `Découvrez le service ${service.value.nom} à ${service.value.ville}`,
        url: window.location.href,
      })
    } catch (err) {
      if (err.name !== 'AbortError') {
        console.error('Erreur de partage:', err)
      }
    }
  } else {
    // Fallback
    copyLink()
  }
}

onMounted(() => {
  loadData()
})

watch(
  () => route.params.slug,
  () => {
    loadData()
  },
)

onUnmounted(() => {
  if (map.value) {
    map.value.remove()
  }
})
</script>

<style scoped>
.star-rating {
  display: flex;
}

.star-rating i {
  transition: all 0.2s ease;
}

.star-rating i:hover {
  transform: scale(1.1);
}

.card {
  transition: all 0.3s ease;
}

.max-height-400 {
  max-height: 400px;
}

.last-child-no-border > div:last-child {
  border-bottom: none !important;
  padding-bottom: 0 !important;
  margin-bottom: 0 !important;
}

:deep(.custom-marker-container) {
  background: transparent;
  border: none;
}

:deep(.custom-marker) {
  width: 30px;
  height: 30px;
  border-radius: 50% 50% 50% 0;
  background: #0d6efd;
  border: 2px solid white;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  transform: rotate(-45deg);
  color: white;
  font-size: 12px;
}

:deep(.custom-marker i) {
  transform: rotate(45deg);
}

.progress {
  background-color: #e9ecef;
  border-radius: 3px;
}

.progress-bar {
  border-radius: 3px;
}

@media (max-width: 768px) {
  .container {
    padding-left: 0.75rem;
    padding-right: 0.75rem;
  }

  .card-body {
    padding: 1rem !important;
  }

  .h3 {
    font-size: 1.5rem;
  }
}

.bg-light {
  background-color: white !important;
}

/* Responsive adjustments */
.service-icon {
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.service-title {
  line-height: 1.2;
  word-break: break-word;
}

.service-meta {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  font-size: 0.875rem;
  color: #6c757d;
}

.meta-item {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
}

.status-dot {
  font-size: 0.7rem;
  flex-shrink: 0;
}

.next-change {
  opacity: 0.8;
}

/* Tablettes et plus */
@media (min-width: 768px) {
  .service-meta {
    flex-direction: row;
    gap: 1rem;
  }

  .meta-item {
    flex-wrap: nowrap;
    white-space: nowrap;
  }
}

/* Mobile portrait */
@media (max-width: 575px) {
  .service-icon {
    width: 50px;
    height: 50px;
  }

  .service-icon i {
    font-size: 1.25rem !important;
  }

  .service-title {
    font-size: 1.25rem;
    margin-bottom: 0.75rem;
  }

  .service-meta {
    font-size: 0.8rem;
  }

  .next-change {
    display: block;
    margin-left: 1rem;
    margin-top: 0.125rem;
  }
}

/* Très petits écrans */
@media (max-width: 380px) {
  .service-title {
    font-size: 1.1rem;
  }

  .meta-item {
    align-items: flex-start;
    flex-direction: column;
  }

  .next-change {
    margin-left: 0;
  }
}
</style>
