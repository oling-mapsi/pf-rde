# Architecture technique initiale (MVP)

## Vision
Le portail est construit en Symfony 7 (PHP 8.2.28) en rendu serveur Twig, avec enrichissement AJAX cible via Stimulus/fetch. L'architecture est modulaire (`Domain / Application / Infrastructure / UI`) pour isoler la logique metier, faciliter les tests et preparer l'interop lots 1/2.

## Couches
- `Domain`: entites metier, invariants de base, relations Doctrine.
- `Application`: services applicatifs explicites (catalogue cartotheque, homepage, healthchecks SIG), DTOs de flux.
- `Infrastructure`: repositories Doctrine, adaptateur mock SIG, logging/audit, securite technique.
- `UI`: controleurs web/API, formulaires, templates Twig, endpoints AJAX.
- `Security`: authentification locale (login form), RBAC Symfony.
- `Shared`: reserve pour enums/value objects transverses.

## Data et persistence
- PostgreSQL 16 + extension PostGIS activee en migration initiale.
- Doctrine ORM + migrations SQL versionnees.
- Entites MVP creees: `User`, `Role`, `Page`, `News`, `QuickLink`, `Partner`, `ContactMessage`, `StaticMap`, `StaticMapAsset`, `DatasetResource`, `MetadataRecord`, `InteractiveMap`, `MapLayer`, `MapServiceEndpoint`, `AgentRequest`, `AgentRequestType`, `AgentRequestAttachment`, `AuditLog`, `DashboardMetricSnapshot`, `TaxonomyTerm`.

## Securite
- Login local (form authenticator), provider `User` en base.
- RBAC: `ROLE_AGENT`, `ROLE_ADMIN` avec hierarchy.
- Segmentation d'acces: `/admin`, `/agents`, `/api/admin`.
- CSRF Forms + login, validation serveur stricte, headers de securite HTTP, journalisation audit.
- Strategie SSO prete via abstraction (provider local aujourd'hui, extension Entra ID ensuite).

## Cartographie et interop lots 1/2
- Entites `InteractiveMap`, `MapLayer`, `MapServiceEndpoint` pretes pour WMS/WFS/GeoJSON.
- Abstraction `MapServiceProviderInterface` + `MockMapServiceProvider` pour decoupler les endpoints reels.
- `SigHealthcheckService` pour supervision de disponibilite et mode degrade UI.

## UX dynamique (sans SPA)
- Cartotheque: filtres/recherche/pagination AJAX avec fallback serveur.
- Auto-completion AJAX sur la recherche cartotheque.
- Formulaire contact en soumission asynchrone (feedback inline).
- Dashboard admin: rafraichissement partiel des metriques via endpoint JSON.

## Theme/design tokens
- Tokens centralises (`assets/styles/theme.css`) + tokens semantiques (`primary`, `secondary`, `accent`, `success`, `warning`, `surface`, `border`, `text`).
- Composants decouples des couleurs brutes; surcharge future simple quand la charte officielle sera fournie.
