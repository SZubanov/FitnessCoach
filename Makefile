#!make
SERVICES=docker compose -f ./docker/docker-compose.yml

up:
	@make down
	$(SERVICES) up -d

start:
	@make down
	$(SERVICES) up

down:
	$(SERVICES) down

build:
	$(SERVICES) build

sh:
	$(SERVICES) exec fpm bash
