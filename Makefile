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

fixtures:
	docker compose exec php php bin/console app:fixtures:load

logs:
	docker compose logs -f

build:
	docker compose build

rebuild:
	docker compose down -v && docker compose build && docker compose up -d

npm:
	docker compose run --rm php npm $(filter-out $@,$(MAKECMDGOALS))

.PHONY: up down bash console test fixtures logs build rebuild
