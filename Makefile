

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
	docker compose exec app php bin/console cache:clear

install:
	docker compose exec app composer install
	docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
	docker compose exec app php bin/console doctrine:fixtures:load --no-interaction
