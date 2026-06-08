up:
	docker compose up -d

down:
	docker compose down

bash:
	docker compose exec php bash

console:
	docker compose exec php php bin/console

test:
	docker compose exec php php bin/phpunit

migrate:
	docker compose exec php php bin/console doctrine:mongodb:schema:update

fixtures:
	docker compose exec php php bin/console doctrine:fixtures:load

logs:
	docker compose logs -f

build:
	docker compose build

rebuild:
	docker compose down -v && docker compose build && docker compose up -d

npm:
	docker compose run --rm php npm $(filter-out $@,$(MAKECMDGOALS))

.PHONY: up down bash console test migrate fixtures logs build rebuild
