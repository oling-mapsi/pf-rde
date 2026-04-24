# Backlog de demarrage (priorise)

## P1 - Socle / MVP public
1. Finaliser l'installation infra (PostgreSQL/PostGIS, secrets, CI).
2. Stabiliser les workflows DB (`database:create`, migrations, fixtures).
3. Completer les contenus admin (pages legales, presentation, actualites, quick links).
4. Finaliser cartotheque statique (assets reels, metadata normalisees, recherche affinee).
5. Durcir securite (politique mot de passe, rotation secrets, revues RBAC).

## P2 - Espace agents
1. Formulaire agent avec logique conditionnelle complete et workflow statuts.
2. Gestion uploads avancee (antivirus, quotas, retention, purge RGPD).
3. Historisation complete des demandes et audit des actions admin.
4. Notification interne (email / file de messages) lors des changements de statut.

## P3 - Cartes interactives / interop
1. Brancher Leaflet/MapLibre avec couches WMS/WFS reelles.
2. Implementer legendes dynamiques, GetFeatureInfo, filtres avancees.
3. Ajouter impression/export PDF et fallback degrade detaille.
4. Connecter endpoint metiers lots 1/2 via adapters securises.

## P4 - Observabilite / industrialisation
1. Abstraction analytics (Matomo provider + provider alternatif).
2. Tableaux de bord metriques admin complets + exports CSV/XLSX.
3. Pipeline CI/CD (tests, lint, securite, deploiement).
4. Recette accessibilite RGAA AA et hardening pre-prod.
