# Fitness Coach

### Разворачивание проекта для разработки (локально)

Выполнить следующие шаги

- создать _.env_, скопировав _.env.example_
- создать _docker/.env_, содержащий переменную PROJECT_NAME

- поднять контейнеры

```shell
docker-compose -p coach up -d
```

- внутри fpm-контейнера установить библиотеки, выполнить миграции и сидеры

```shell
composer install --dev
TELESCOPE_ENABLED=false art migrate
```

Для лучшей поддержки IDE

```
art ide-helper:generate
```

Для установки Telescope (https://laravel.com/docs/8.x/telescope)

```shell
art telescope:install
art migrate
```
