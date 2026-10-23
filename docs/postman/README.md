# Коллекция Postman

`markethub.postman_collection.json` — вебхуки с подписью и API для админки. В Postman: **Import** → этот файл.

## Переменные коллекции

| Переменная | По умолчанию |
|---|---|
| `baseUrl` | `http://localhost:8080` |
| `webhookSecret` | `whsec_test_5f1d8c2a9b` — как в `.env.example` |
| `newOrderId` | заполняется сама |

Подпись считает pre-request скрипт коллекции: HMAC-SHA256 от `<timestamp>.<тело>` — ровно от того тела,
что уйдёт на сервер, и заполняет `X-Webhook-Id`, `X-Webhook-Timestamp`, `X-Webhook-Signature`.
Тело запроса можно править — подпись пересчитается.

## Папки

1. **Примеры из задания** — пять событий из `task/examples` байт в байт. Первый раз — `applied` / `stale`, потом `duplicate`.
2. **Новый заказ: порядок, повторы, коррекция** — запрос «Оформлен» каждый раз начинает новый заказ,
   дальше: доставка → опоздавшая оплата (`stale`) → повтор (`duplicate`) → неизвестный тип (`ignored`)
   → доставлен → ручная коррекция обратно в доставку; в конце карточка с историей.
3. **Ошибки доставки** — неверная подпись и просроченный timestamp (401), тело не по формату (422).
4. **API для админки** — список, фильтры, пагинация, 422, карточка, 404.

У каждого запроса есть тесты. Папки удобно запускать через **Runner** по порядку; письма — в Mailpit
(http://localhost:8025).

Из консоли, без Postman:

```bash
docker run --rm --network host -v "$PWD/docs/postman":/etc/newman \
    postman/newman:6-alpine run markethub.postman_collection.json
```
