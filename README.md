# Routes de Guadeloupe - Portail SIG (Lot 3)

Socle Symfony 7 industrialisable pour un portail public SIG + espace agents.

## Stack
- PHP 8.2.28
- Symfony 7.4
- Twig + Stimulus/fetch (progressive enhancement)
- Doctrine ORM + Migrations
- PostgreSQL 16 + PostGIS
- Security Symfony (RBAC)
- EasyAdmin (back-office minimal)
- PHPUnit

## Arborescence principale
- `src/Domain`
- `src/Application`
- `src/Infrastructure`
- `src/UI`
- `src/Security`
- `templates`
- `assets`
- `migrations`
- `tests`
- `docs`

## Installation rapide
```bash
composer install
cp .env .env.local
# adapter DATABASE_URL dans .env.local
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate -n
php bin/console doctrine:fixtures:load -n
symfony server:start -d
```

## Comptes de demo (fixtures)
- Admin: `admin@routesguadeloupe.local` / `Admin12345!`
- Agent: `agent@routesguadeloupe.local` / `Agent12345!`

## Commandes utiles
```bash
php bin/console doctrine:schema:validate --skip-sync
php bin/console debug:router --show-controllers
php bin/phpunit
```

## Adminer (PostgreSQL)
- Fichier installe: `public/adminer.php`
- Point d'entree preconfigure: `public/adminer-login.php`
- URL locale: `http://127.0.0.1:8000/adminer-login.php` (si serveur Symfony sur 8000)

Parametres PostgreSQL repris depuis `DATABASE_URL`:
- Systeme: PostgreSQL
- Serveur: `127.0.0.1:5432`
- Utilisateur: `app`
- Base: `app`

En local:
```bash
symfony server:start -d
```
Puis ouvrir `/adminer-login.php`.

En prod:
- Conserver `DATABASE_URL` configuree dans l'environnement de prod.
- Exposer Adminer de facon restreinte (IP allowlist, auth basique, ou acces temporaire).
- Supprimer/desactiver `adminer.php` apres usage si l'exposition publique n'est pas necessaire.

## Migrations
- Migration initiale: `migrations/Version20260423121500.php`
- Inclut activation `CREATE EXTENSION IF NOT EXISTS postgis`.

## AJAX MVP
- Cartotheque: `/cartotheque` + API `/api/static-maps`
- Auto-complete: `/api/static-maps/autocomplete`
- Health SIG mock: `/api/sig/health`
- Dashboard admin: `/api/admin/dashboard/metrics`
- Cartes interactives (MapLibre + mock complet):
  - `/api/interactive-maps/{slug}/bootstrap`
  - `/api/interactive-maps/{slug}/features`
  - `/api/interactive-maps/{slug}/legend`
  - `/api/interactive-maps/{slug}/feature-info`
  - assets MapLibre embarques en local (mode CSP): `public/vendor/maplibre/maplibre-gl-csp.js`, `public/vendor/maplibre/maplibre-gl-csp-worker.js`, `public/vendor/maplibre/maplibre-gl.css`
  - fond de carte local (sans tuiles externes): `public/data/basemap/guadeloupe_land.geojson` + `public/data/basemap/guadeloupe_context.geojson`

## Notes interop lots 1/2
- Interface: `App\Application\Interop\Sig\MapServiceProviderInterface`
- Adapter mock: `App\Infrastructure\Interop\Sig\Provider\MockMapServiceProvider`
- Healthcheck: `App\Application\Interop\Sig\SigHealthcheckService`

## Documentation
- Architecture: `docs/ARCHITECTURE.md`
- Backlog: `docs/BACKLOG.md`
