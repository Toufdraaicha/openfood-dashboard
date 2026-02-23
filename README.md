# 🥗 OFF Dashboard

Dashboard personnalisable pour visualiser les données nutritionnelles d'Open Food Facts.

## 🚀 Installation
```bash
# Cloner le projet
git clone <repo>
cd off-dashboard

# Démarrer Docker
docker compose up -d

# Installer les dépendances
docker compose exec app composer install

# Créer la base de données
docker compose exec app php bin/console doctrine:database:create
docker compose exec app php bin/console doctrine:migrations:migrate -n

# Charger les fixtures
docker compose exec app php bin/console doctrine:fixtures:load -n
```

## 🔑 Comptes de test

- **Admin** : `admin@example.com` / `admin123`
- **User** : `user@example.com` / `user123`

## 📱 URLs

- **Dashboard** : http://localhost:84
- **Admin** : http://localhost:84/admin
- **API** : http://localhost:84/api/dashboard
- **Mailpit** : http://localhost:8025

## 🏗️ Architecture

### Stack technique
- **Backend** : Symfony 7.2, PHP 8.3
- **Base de données** : PostgreSQL 17
- **Cache** : Redis 7
- **Serveur** : FrankenPHP (Caddy)
- **Frontend** : Stimulus, Tailwind CSS

### Structure DDD
```
src/
├── Domain/            # Logique métier pure
│   ├── User/
│   └── Dashboard/
├── Application/       # Use cases (CQRS)
│   ├── Query/
│   └── Port/
├── Infrastructure/    # Implémentations techniques
│   ├── Persistence/
│   ├── Http/
│   └── Security/
└── UI/               # Interfaces utilisateur
    ├── Controller/
    └── Admin/
```

## 🔐 Sécurité

- ✅ Authentification 2FA par email
- ✅ Blocage après 5 tentatives échouées
- ✅ Sessions sécurisées (httpOnly cookies)
- ✅ CSRF protection
- ✅ Rate limiting API
- ✅ Validation stricte côté serveur

## 📊 Widgets disponibles

1. **🔍 Recherche** : Trouver des produits par nom/marque
2. **📊 Nutri-Score** : Distribution statistique par catégorie
3. **🏆 Top catégorie** : Meilleurs produits d'une catégorie
4. **📦 Détail produit** : Fiche complète via code-barres

## 🧪 Tests
```bash
# Tests unitaires
docker compose exec app php bin/phpunit

# Linter
docker compose exec app php bin/console lint:twig templates
docker compose exec app php bin/console lint:yaml config
```

## 🛠️ Commandes utiles
```bash
# Vider le cache
make clear

# Voir les logs
docker compose logs -f app

# Accéder au container
docker compose exec app sh

# Base de données
docker compose exec database psql -U app -d app
```

## 📄 License

MIT
