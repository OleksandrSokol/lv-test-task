<?php

return [
    'activity_event' => [
        'webhook.applied'      => 'Вебхук применён',
        'webhook.stale'        => 'Вебхук устарел',
        'webhook.ignored'      => 'Вебхук пропущен',
        'webhook.duplicate'    => 'Повтор вебхука',
        'order.created'        => 'Заказ создан',
        'order.updated'        => 'Заказ изменён',
        'order.status_changed' => 'Сменился статус заказа',
        'order.snapshot_stale' => 'Устаревший снимок заказа',
        'letter.queued'        => 'Письмо в очереди',
        'letter.sent'          => 'Письмо отправлено',
        'letter.failed'        => 'Ошибка отправки письма',
        'letter.skipped'       => 'Письмо не отправлено',
    ],

    'activity_source' => [
        'webhook' => 'Вебхук',
        'queue'   => 'Очередь',
    ],

    'order_status' => [
        'created'   => 'Оформлен',
        'paid'      => 'Оплачен',
        'shipped'   => 'Передан в доставку',
        'delivered' => 'Доставлен',
        'cancelled' => 'Отменён',
    ],

    'webhook_status' => [
        'received'  => 'Получен',
        'applied'   => 'Применён',
        'stale'     => 'Устарел',
        'ignored'   => 'Пропущен',
        'duplicate' => 'Повтор',
    ],

    'webhook_event_type' => [
        'order.created'   => 'Заказ оформлен',
        'order.updated'   => 'Заказ изменён',
        'order.paid'      => 'Заказ оплачен',
        'order.shipped'   => 'Заказ передан в доставку',
        'order.delivered' => 'Заказ доставлен',
        'order.cancelled' => 'Заказ отменён',
    ],

    'letter' => [
        'order_paid'      => 'Заказ оплачен',
        'order_shipped'   => 'Заказ передан в доставку',
        'order_cancelled' => 'Заказ отменён',
    ],

    'log_letter_status' => [
        'queued'  => 'В очереди',
        'sent'    => 'Отправлено',
        'failed'  => 'Ошибка',
        'skipped' => 'Не отправлено',
    ],
];
