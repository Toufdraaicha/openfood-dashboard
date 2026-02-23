# Dashboard Open Food Facts — Symfony 8

Dashboard personnalisable consommant l'API Open Food Facts, développé avec Symfony 8, DDD et Docker.

## Prérequis

- Docker >= 24
- Docker Compose >= 2.20

## Lancement rapide

```bash
# Cloner le projet
git clone <repo-url> off-dashboard && cd off-dashboard

# Lancer (tout-en-un)
docker compose up
```

L'application est disponible sur **http://localhost**

> Le hot reload est activé automatiquement en dev via FrankenPHP + Caddy `--watch`.

## Installation complète (première fois)

```bash
# Build + démarrage + migrations + fixtures
make install
```

Ou étape par étape :

```bash
docker compose up -d
docker compose exec app composer install
make jwt-keys      # génère les clés JWT
make migrate       # migrations Doctrine
make fixtures      # données de démo
```

## Lancer les tests

```bash
# Tous les tests
make test

# Tests unitaires uniquement
make test-unit

# Avec couverture de code (HTML dans var/coverage/)
make test-coverage
```

## Architecture

Ce projet suit une approche **DDD (Domain-Driven Design)** avec 4 couches strictement séparées :

```
src/
├── Domain/          # Entités, Agrégats, Events, Interfaces repo
├── Application/     # Use Cases, Commands, Queries, DTOs
├── Infrastructure/  # Doctrine, HttpClient OFF, Cache, Security
└── UI/              # Controllers, Forms, Twig, API endpoints
```

### Choix techniques

| Composant | Choix | Justification |
|---|---|---|
| Runtime | FrankenPHP | Hot reload natif, PHP 8.4, Caddy intégré |
| Auth | Symfony Security + SchebTwoFactor | Bundle mature, supporte 2FA email |
| 2FA | Code email 6 chiffres | Simple, pas d'app TOTP requise |
| API interne | API Platform 4 | OpenAPI auto-généré, JSON-LD |
| JWT | LexikJWT | Standard Symfony pour API stateless |
| Admin | EasyAdmin 4 | CRUD rapide, extensible |
| Cache | Redis | Sessions + cache API OFF + rate limiting |
| DB | PostgreSQL 16 | JSONB pour config widgets |
| Tests | PHPUnit 11 + Foundry | TDD sur la couche Domain |

## Sécurité

- **2FA obligatoire** par email (SchebTwoFactorBundle)
- **Blocage après 5 tentatives** — `LoginAttempt` entity + audit trail
- **Mots de passe** hashés en Argon2id
- **CSRF** sur tous les formulaires
- **JWT** pour l'API (Bearer token, TTL configurable)
- **Rate limiting** Redis sur `/login` et `/api`
- **Pas d'inscription publique** — création de comptes via admin uniquement

## API

Documentation interactive disponible sur **http://localhost/api/docs**

Endpoint principal :

```
GET  /api/products?search=...   Recherche de produits OFF
GET  /api/products/{barcode}    Détail d'un produit
GET  /api/widgets               Liste des widgets de l'utilisateur courant
POST /api/auth/token            Obtenir un token JWT
```

Authentification :
```
Authorization: Bearer <token>
```

## Accès dev

| Service | URL | Identifiants |
|---|---|---|
| Application | http://localhost | admin@example.com / admin |
| Mailpit (emails 2FA) | http://localhost:8025 | — |
| API Docs | http://localhost/api/docs | — |

## Variables d'environnement

Copier `.env.local.dist` en `.env.local` et renseigner :

```bash
cp .env.local.dist .env.local
```

Variables importantes :
- `APP_SECRET` — clé secrète Symfony
- `JWT_PASSPHRASE` — passphrase clés JWT
- `POSTGRES_PASSWORD` — mot de passe DB (prod)
