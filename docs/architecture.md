# Архитектура

Laravel 13, PHP 8.4, PostgreSQL, Redis (очередь и кэш), Mailpit. Код — лёгкая модульная структура:
каждый модуль в `app/Modules` держит свои модели, миграции, маршруты и события.
`App\Providers\ModuleServiceProvider` сам подключает маршруты (`Http/Routes/api.php`) и миграции
(`database/migrations`) каждого модуля; слушатели событий заданы в `AppServiceProvider`.

## Модули

```text
app/Modules/
├── Marketplace/    # приём вебхуков MarketHub
├── Order/          # заказы, применение снимков, API для админки, правило писем
├── Notification/   # шаблоны писем, журнал отправки, отправка через очередь
├── Core/           # базовое доменное событие и журнал действий
└── User/           # пользователь админки
```

| Модуль | Что делает | Главное |
|---|---|---|
| `Marketplace` | Принимает `POST /api/webhooks/marketplace`: проверяет подпись и окно времени, валидирует тело, записывает событие в inbox и передаёт снимок заказа в `Order` | `VerifyMarketplaceSignature`, `MarketplaceWebhookRequest`, `ReceiveWebhookAction`, модель `WebhookEvent` |
| `Order` | Хранит текущее состояние заказа, позиции и историю статусов. Применяет снимок, только если он новее сохранённого. Отдаёт `GET /api/orders` и `GET /api/orders/{id}`. Решает, какое письмо отправить | `ApplyOrderSnapshotAction`, `RecordStatusHistoryAction`, `SearchOrdersQuery`, `OrderResource`, `SendOrderStatusLetter` |
| `Notification` | Шаблоны писем с подстановками `{{ … }}`, журнал писем (`log_letters`, одно письмо каждого типа на заказ), отправка job'ом и снятие писем, которые стали противоречить заказу | `LetterService`, `SendLetterJob`, `LetterMail` |
| `Core` | `DomainEvent` — базовое событие, которое срабатывает после коммита и пишется в журнал действий слушателем `WriteActivityLog` | `DomainEvent`, `WriteActivityLog`, модель `Log`, трейт `HasEnumHelpers` |
| `User` | Модель пользователя и сидер администратора | `User`, `AdminUserSeeder` |

Событие идёт по модулям в порядке `Marketplace` → `Order` → `Notification`; `Core` используют все.
`Order` и `Notification` связаны через `log_letters.order_id`: карточка заказа показывает его письма.

## Как проходит вебхук

```text
POST /api/webhooks/marketplace
  │
  ├─ VerifyMarketplaceSignature   401 — подпись или окно ±5 мин
  ├─ MarketplaceWebhookRequest    422 — тело не по формату (правила — из Data-классов)
  └─ ReceiveWebhookAction                       ┐ одна транзакция
       ├─ inbox: createOrFirst по event_id      │  повтор → duplicate
       ├─ неизвестный тип → ignored             │
       └─ ApplyOrderSnapshotAction              │  блокировка строки заказа
            ├─ снимок новее → заказ, позиции    │  applied
            ├─ иначе состояние не трогаем       │  stale
            └─ RecordStatusHistoryAction        ┘  история — из любого снимка
  │
  после коммита:
  ├─ WriteActivityLog            → logs (все доменные события)
  └─ OrderStatusChanged
       └─ SendOrderStatusLetter (очередь) → LetterService → log_letters
            └─ SendLetterJob (очередь) → письмо в Mailpit
```

Ответ маркетплейсу — `{"status": "applied" | "stale" | "ignored" | "duplicate"}` с кодом 200.

## Данные

| Таблица | Что хранит |
|---|---|
| `orders` | Текущее состояние заказа. `external_id` — id маркетплейса (unique); `external_created_at` / `external_updated_at` — его время; `created_at` / `updated_at` — наше |
| `order_items` | Позиции из последнего применённого снимка |
| `order_status_history` | Статусы с моментом по данным маркетплейса; unique `(order_id, status, changed_at)` |
| `webhook_events` | Inbox: unique `event_id`, тип, результат, разобранное событие и сырое тело запроса |
| `letter_templates` | Тема и текст писем, редактируются в админке |
| `log_letters` | Письма заказа: получатель, текст, статус (`queued` / `sent` / `failed` / `skipped`); unique `(order_id, template_key)` |
| `logs` | Журнал действий: событие, объект, данные, источник (вебхук или очередь) |
| `users` | Пользователи админки |

Моменты — `timestamptz`, суммы — `numeric(12,2)`.

## Соглашения в коде

- **Actions** — запись (`ApplyOrderSnapshotAction`), **Queries** — чтение (`SearchOrdersQuery`).
- **DTO** — `spatie/laravel-data`, классы `*Data`. Правила валидации вебхука описаны в них и переиспользуются в FormRequest.
- **API Resources** и стандартная пагинация Laravel — формат ответа как в `task/openapi.yaml`.
- Тонкие контроллеры; побочные эффекты — доменными событиями после коммита.
- **Enum** с подписями: трейт `HasEnumHelpers` (`options()`), методы `label()` и `color()`, тексты — `lang/ru/enums.php`.
- Тесты — PHPUnit, имена `test_{что}_{условие}_{результат}`, по модулям в `tests/Feature` и `tests/Unit`;
  сквозные сценарии доставки — `tests/Feature/Scenarios`.

## Админка

Filament, `app/Filament`. Ресурсы подключены и меню собрано явно в `App\Providers\Filament\AdminPanelProvider`.

| Группа меню | Ресурс | Что можно |
|---|---|---|
| Заказы | Заказы | список, поиск, фильтр по статусу, карточка с позициями, историей, письмами |
| | Вебхуки | список, фильтры, просмотр с телом запроса |
| Письма | Шаблоны писем | редактирование |
| | Журнал писем | просмотр |
| Система | Журнал действий | фильтры, просмотр данных |
| | Пользователи | редактирование |
