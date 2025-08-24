### 🏛️ CitoyenNote
CitoyenNote est une plateforme citoyenne d’évaluation des services publics locaux.
Elle vise à renforcer la transparence, améliorer la qualité des services, et faciliter un dialogue apaisé entre citoyens et collectivités.
---
### ⚙️ Objectif du projet
- Agréger et enrichir les avis citoyens (notation native + intégrations externes comme Google, TripAdvisor en V1+).
- Fournir des indicateurs clairs aux collectivités : satisfaction, évolution, top/bottom, tendances mensuelles.
- Permettre un droit de réponse officiel encadré, une modération visible (signalement d’avis), et distinguer les “avis vérifiés / non vérifiés”.
- Offrir un back-office cloisonné par collectivité (multi-tenant).
- Produire automatiquement des rapports mensuels pour les DGS/DGA.
- Proposer une interface simple, rapide, accessible RGAA, pensée pour tous les usagers.
---
### 🧱 Architecture technique (MVP enrichi)
- Frontend citoyen : VueJS 3 + PrimeVue + Pinia (PWA responsive, accessibilité RGAA intégrée).
- Backend : Symfony 6 LTS avec API REST sécurisée (JWT + refresh cookies).
- Base de données : PostgreSQL + PostGIS (géolocalisation).
- Multi-tenant : cloisonnement strict via champ collectivite_id + TenantContext + Doctrine Filter.
- Back-office collectivité : VueJS 3 / PrimeVue.
- CI/CD avec tests unitaires, Cypress, Lighthouse CI ; backups & rollback automatisés.
👉 Tous les détails techniques et fonctionnels sont décrits dans la notice ci-dessous.
---
### 📘 Documentation
📄 Notice Technique CitoyenNote (version 24/08/2025)

Contient :
- Architecture technique complète (frontend, backend, multi-tenant, accessibilité, QA).
- Fonctionnalités MVP en place (#1 → #73).
- Roadmap actualisée (MVP enrichi, V1+).
- Liste des tickets prioritaires (GitHub Projects).
---
### 📊 Suivi des tâches
Le développement est suivi via GitHub Projects (Kanban) :
🔗 Voir le tableau de bord (à adapter selon le lien réel)

Colonnes :
- À faire
- En cours
- À tester
- Fait
---
### 🔐 Accès et déploiement
- Plateforme de test (citoyen) : http://citoyen.anime-sanctuary.net/ (ou URL staging/prod à préciser).
- Accès back-office : réservé aux comptes collectivité (via l’équipe projet).
---
### 🧭 Contributeurs
🧠 Product & Coordination : @Sanou64

🛠️ Développement backend : @asfolken

💡 Conception fonctionnelle, stratégie, RGPD : @Ninou

♿ Accessibilité & QA : équipe QA interne (suivi #71)
---
### 📮 Contact
Pour toute question, proposition ou collaboration :
📬 contact@citoyennote.fr (à adapter)
