# MarketHub — заказы

Сервис на Laravel 13 (PHP 8.4, PostgreSQL), который принимает вебхуки маркетплейса MarketHub,
хранит актуальное состояние заказов, пишет покупателю письма и отдаёт заказы через API для админки.
Задание — в [`task/README.md`](task/README.md), принятые решения — в [`DECISIONS.md`](DECISIONS.md).

## Требования

- Docker с Compose v2.
- Свободные порты: `8080` (приложение), `8025` и `1025` (Mailpit), `5432` (PostgreSQL), `6379` (Redis).
  Любой из них меняется в `.env` (`APP_PORT`, `FORWARD_*_PORT`).

## Запуск с нуля

```bash
git clone <url> markethub && cd markethub
cp .env.example .env

# Зависимости: PHP и Composer на хосте не нужны.
docker run --rm -u "$(id -u):$(id -g)" -v "$PWD":/var/www/html -w /var/www/html \
    laravelsail/php84-composer:latest composer install --ignore-platform-reqs

./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
```

Первый `sail up` собирает образ приложения — это несколько минут.
`migrate --seed` создаёт таблицы, шаблоны писем и пользователя админки.
Воркер очереди до `key:generate` и миграций перезапускается с ошибкой — это ожидаемо, дальше он работает сам.

Поднимаются контейнеры: приложение, воркер очереди `queue` (отправляет письма), PostgreSQL, Redis и Mailpit.

## Адреса

| Что | Адрес |
|---|---|
| Приложение и API | http://localhost:8080 (`APP_PORT`) |
| Вебхук | `POST http://localhost:8080/api/webhooks/marketplace` |
| API для админки | `GET http://localhost:8080/api/orders`, `GET http://localhost:8080/api/orders/{id}` |
| Админка | http://localhost:8080/admin — `admin@example.com` / `password` |
| Mailpit (письма) | http://localhost:8025 (`FORWARD_MAILPIT_DASHBOARD_PORT`) |

Секрет подписи — `MARKETPLACE_WEBHOOK_SECRET` в `.env`; в `.env.example` он совпадает с примером
из [`task/examples/signature-example.md`](task/examples/signature-example.md).

## Проверить руками

Отправить пример события скриптом из задания (изнутри контейнера, PHP на хосте не нужен):

```bash
./vendor/bin/sail exec -e MARKETPLACE_WEBHOOK_SECRET=whsec_test_5f1d8c2a9b laravel.test \
    php task/examples/send-event.php http://localhost/api/webhooks/marketplace task/examples/order-paid.json
```

или с хоста, если там есть PHP 8.2+ с curl:

```bash
MARKETPLACE_WEBHOOK_SECRET=whsec_test_5f1d8c2a9b \
    php task/examples/send-event.php http://localhost:8080/api/webhooks/marketplace task/examples/order-paid.json
```

Ответ — `{"status":"applied"}`; повторная отправка того же файла — `{"status":"duplicate"}`.
Дальше:

```bash
curl http://localhost:8080/api/orders/ord_7Q2KX9M4
```

письмо «Заказ №MH-1048213 оплачен» — в Mailpit, событие, заказ и письмо — в админке.

## Тесты

```bash
./vendor/bin/sail test
./vendor/bin/sail pint --test
```

Тесты идут на отдельной базе `testing` (её создаёт контейнер PostgreSQL при первом запуске).
`tests/Feature/Scenarios` — сквозные сценарии доставки через HTTP, включая параллельную доставку
из двух процессов.

## Остановить

```bash
./vendor/bin/sail down      # данные остаются
./vendor/bin/sail down -v   # вместе с базой
```

После правки кода воркер очереди нужно перезапустить: `./vendor/bin/sail restart queue`.

## Документация

Архитектура, модули и коллекция Postman — в [`docs/`](docs/README.md).
