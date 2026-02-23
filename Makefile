

up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build --no-cache

shell:
	docker compose exec app bash

console:
	docker compose exec app php bin/console $(cmd)

migrate:
	docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction

fixtures:
	docker compose exec app php bin/console doctrine:fixtures:load --no-interaction

test:
	docker compose exec app php bin/phpunit

test-unit:
	docker compose exec app php bin/phpunit tests/Unit

cc:
	docker compose exec app rm -rf var/cache/*
docker compose exec app php bin/console cache:clear
    docker compose exec app php bin/console asset-map:compile

install:
	docker compose exec app composer install
	docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
	docker compose exec app php bin/console doctrine:fixtures:load --no-interaction

cs-fix: ## Fixer le code PHP
	docker compose exec app composer cs-fix

cs-check: ## Vérifier le code PHP
	docker compose exec app composer cs-check

phpstan: ## Analyser avec PHPStan
	docker compose exec app composer phpstan

twig-fix: ## Fixer les templates Twig
	docker compose exec app composer twig-fix

twig-check: ## Vérifier les templates Twig
	docker compose exec app composer twig-check

lint: ## Vérifier tout le code
	docker compose exec app composer lint

fix-all: ## Fixer tout le code
	docker compose exec app composer fix

quality: cs-check phpstan twig-check ## Vérifier la qualité du code
